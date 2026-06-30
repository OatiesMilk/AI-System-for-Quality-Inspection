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

    public function inspector(): View
    {
        $pendingInspections = Inspection::with('batch')
            ->whereNull('action')
            ->latest()
            ->limit(10)
            ->get();

        $reviewedInspections = Inspection::with('batch', 'inspector')
            ->whereNotNull('action')
            ->latest('inspected_at')
            ->limit(10)
            ->get();

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

        $recentBatches = Batch::with('inspections')->latest()->limit(10)->get();

        return view('dashboards.manager', compact('defectCounts', 'recentBatches'));
    }

    public function admin(): View
    {
        $users = User::orderBy('role')->orderBy('name')->get();

        $auditLogs = AuditLog::with('user')
            ->latest()
            ->limit(20)
            ->get();

        return view('dashboards.admin', compact('users', 'auditLogs'));
    }

    public function constructor(): View
    {
        $reworkInspections = Inspection::with('batch', 'defects')
            ->where('action', 'rework')
            ->whereNull('reworked_at')
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboards.constructor', compact('reworkInspections'));
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
