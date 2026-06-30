<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Defect;
use App\Models\Inspection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Land the user on the dashboard for their role.
     */
    public function index(Request $request): View|RedirectResponse
    {
        return match ($request->user()->role) {
            'quality_inspector' => redirect()->route('dashboard.inspector'),
            'product_manager' => redirect()->route('dashboard.manager'),
            'system_admin' => redirect()->route('dashboard.admin'),
            'shoe_constructor' => redirect()->route('dashboard.constructor'),
            default => abort(403, 'No dashboard is configured for your role.'),
        };
    }

    public function inspector(Request $request): View
    {
        $request->validate([
            'decision' => ['nullable', 'in:pass,rework,reject'],
            'ai_override' => ['nullable', 'in:0,1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $pendingInspections = Inspection::with('batch')
            ->whereNull('action')
            ->latest()
            ->limit(10)
            ->get();

        $reviewedInspections = Inspection::with('batch', 'inspector')
            ->whereNotNull('action')
            ->when($request->filled('decision'), fn ($query) => $query->where('action', $request->string('decision')))
            ->when($request->filled('ai_override'), fn ($query) => $query->where('ai_override', $request->boolean('ai_override')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('inspected_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('inspected_at', '<=', $request->date('date_to')))
            ->latest('inspected_at')
            ->paginate(10)
            ->withQueryString();

        return view('dashboards.inspector', compact('pendingInspections', 'reviewedInspections'));
    }

    public function showInspection(Inspection $inspection): View
    {
        $inspection->load('batch', 'defects');

        return view('dashboards.inspector-review', compact('inspection'));
    }

    public function updateInspection(Request $request, Inspection $inspection): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:pass,rework,reject'],
            'defects' => ['array'],
            'defects.*' => ['nullable', 'boolean'],
        ]);

        $confirmedStates = collect($inspection->defects)->mapWithKeys(
            fn (Defect $defect) => [$defect->id => $request->boolean("defects.{$defect->id}")]
        );

        $aiOverride = $confirmedStates->contains(false);

        foreach ($inspection->defects as $defect) {
            $defect->update(['confirmed' => $confirmedStates->get($defect->id, false)]);
        }

        $inspection->update([
            'inspector_id' => $request->user()->id,
            'action' => $validated['action'],
            'ai_override' => $aiOverride,
            'inspected_at' => now(),
        ]);

        AuditLog::record('inspection.validated', $request->user(), [
            'inspection_id' => $inspection->id,
            'action' => $validated['action'],
            'ai_override' => $aiOverride,
        ]);

        return redirect()->route('dashboard.inspector')
            ->with('status', "Inspection #{$inspection->id} marked as {$validated['action']}.");
    }

    public function manager(): View
    {
        $defectCounts = Defect::query()
            ->selectRaw('defect_type, count(*) as total')
            ->groupBy('defect_type')
            ->pluck('total', 'defect_type');

        $aiOverrideCounts = Defect::query()
            ->where('confirmed', false)
            ->selectRaw('defect_type, count(*) as total')
            ->groupBy('defect_type')
            ->pluck('total', 'defect_type');

        $reviewedCount = Inspection::whereNotNull('action')->count();
        $overriddenCount = Inspection::where('ai_override', true)->count();
        $overrideRate = $reviewedCount > 0 ? round(($overriddenCount / $reviewedCount) * 100, 1) : 0;

        $recentBatches = Batch::with('inspections')->latest()->limit(10)->get();

        return view('dashboards.manager', compact(
            'defectCounts',
            'aiOverrideCounts',
            'reviewedCount',
            'overriddenCount',
            'overrideRate',
            'recentBatches',
        ));
    }

    public function admin(Request $request): View
    {
        $request->validate([
            'action' => ['nullable', 'string'],
            'user_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $users = User::orderBy('role')->orderBy('name')->get();

        $auditLogs = AuditLog::with('user')
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $auditActions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('dashboards.admin', compact('users', 'auditLogs', 'auditActions'));
    }

    public function constructor(Request $request): View
    {
        $shift = $request->user()->shift;

        $reworkInspections = Inspection::with('batch', 'defects')
            ->where('action', 'rework')
            ->whereNull('reworked_at')
            ->when($shift, fn ($query) => $query->whereHas('batch', fn ($q) => $q->where('shift', $shift)))
            ->latest()
            ->limit(10)
            ->get();

        $resolvedReworks = Inspection::with('batch', 'defects')
            ->where('action', 'rework')
            ->whereNotNull('reworked_at')
            ->when($shift, fn ($query) => $query->whereHas('batch', fn ($q) => $q->where('shift', $shift)))
            ->latest('reworked_at')
            ->limit(10)
            ->get();

        return view('dashboards.constructor', compact('reworkInspections', 'resolvedReworks'));
    }

    public function showConstructorInspection(Inspection $inspection): View
    {
        $inspection->load('batch', 'defects');

        return view('dashboards.constructor-inspection', compact('inspection'));
    }

    public function resolveRework(Request $request, Inspection $inspection): RedirectResponse
    {
        $inspection->update(['reworked_at' => now()]);

        AuditLog::record('inspection.reworked', $request->user(), [
            'inspection_id' => $inspection->id,
        ]);

        return redirect()->route('dashboard.constructor')
            ->with('status', "Inspection #{$inspection->id} marked as resolved.");
    }
}
