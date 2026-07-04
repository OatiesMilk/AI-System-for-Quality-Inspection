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
        $previousAction = $inspection->action;

        foreach ($inspection->defects as $defect) {
            $defect->update(['confirmed' => $confirmedStates->get($defect->id, false)]);
        }

        $inspection->update([
            'inspector_id' => $request->user()->id,
            'action' => $validated['action'],
            'ai_override' => $aiOverride,
            'inspected_at' => now(),
        ]);

        AuditLog::record($previousAction ? 'inspection.revalidated' : 'inspection.validated', $request->user(), [
            'inspection_id' => $inspection->id,
            'action' => $validated['action'],
            'previous_action' => $previousAction,
            'ai_override' => $aiOverride,
        ]);

        return redirect()->route('dashboard.inspector')
            ->with('status', "Inspection #{$inspection->id} marked as {$validated['action']}.");
    }

    public function manager(): View
    {
        // Defect distribution
        $defectCounts = Defect::query()
            ->selectRaw('defect_type, count(*) as total')
            ->groupBy('defect_type')
            ->pluck('total', 'defect_type');

        // Dismissed (unconfirmed) defects — inspector overrides of AI flags
        $dismissedDefectCounts = Defect::query()
            ->where('confirmed', false)
            ->selectRaw('defect_type, count(*) as total')
            ->groupBy('defect_type')
            ->pluck('total', 'defect_type');

        // AI override summary
        $reviewedCount = Inspection::whereNotNull('action')->count();
        $overriddenCount = Inspection::where('ai_override', true)->count();
        $overrideRate = $reviewedCount > 0 ? round(($overriddenCount / $reviewedCount) * 100, 1) : 0;

        // Batch pass/fail/rework rates
        $batchStats = Batch::with('inspections')->latest()->limit(10)->get()->map(function (Batch $batch) {
            $total  = $batch->inspections->whereNotNull('action')->count();
            $pass   = $batch->inspections->where('action', 'pass')->count();
            $rework = $batch->inspections->where('action', 'rework')->count();
            $reject = $batch->inspections->where('action', 'reject')->count();

            return [
                'batch_code' => $batch->batch_code,
                'shift'      => $batch->shift,
                'stage'      => $batch->manufacturing_stage,
                'total'      => $total,
                'pass'       => $pass,
                'rework'     => $rework,
                'reject'     => $reject,
                'pass_rate'  => $total > 0 ? round(($pass / $total) * 100, 1) : null,
            ];
        });

        // Defect trend — last 7 days
        $defectTrend = Defect::query()
            ->selectRaw('DATE(defects.created_at) as date, count(*) as total')
            ->where('defects.created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $trendLabels = collect();
        $trendValues = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $trendLabels->push(now()->subDays($i)->format('M j'));
            $trendValues->push($defectTrend->get($date, 0));
        }

        // Shift comparison — AM vs PM pass rate
        $shiftStats = Inspection::query()
            ->whereNotNull('action')
            ->join('batches', 'inspections.batch_id', '=', 'batches.id')
            ->whereNotNull('batches.shift')
            ->selectRaw('batches.shift, count(*) as total, sum(case when inspections.action = "pass" then 1 else 0 end) as pass_count')
            ->groupBy('batches.shift')
            ->get()
            ->mapWithKeys(fn ($row) => [
                strtoupper($row->shift) => [
                    'total'     => $row->total,
                    'pass'      => $row->pass_count,
                    'pass_rate' => $row->total > 0 ? round(($row->pass_count / $row->total) * 100, 1) : null,
                ],
            ]);

        // Checkpoint comparison — preparation vs finishing
        $checkpointStats = Inspection::query()
            ->whereNotNull('action')
            ->selectRaw('checkpoint, count(*) as total, sum(case when action = "pass" then 1 else 0 end) as pass_count')
            ->groupBy('checkpoint')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->checkpoint => [
                    'total'     => $row->total,
                    'pass'      => $row->pass_count,
                    'pass_rate' => $row->total > 0 ? round(($row->pass_count / $row->total) * 100, 1) : null,
                ],
            ]);

        // Inspector performance
        $inspectorStats = Inspection::query()
            ->whereNotNull('action')
            ->whereNotNull('inspector_id')
            ->with('inspector')
            ->selectRaw('inspector_id, count(*) as total, sum(case when action = "pass" then 1 else 0 end) as pass_count, sum(ai_override) as overrides')
            ->groupBy('inspector_id')
            ->get()
            ->map(fn ($row) => [
                'name'          => $row->inspector?->name ?? 'Unknown',
                'total'         => $row->total,
                'pass'          => $row->pass_count,
                'overrides'     => $row->overrides,
                'override_rate' => $row->total > 0 ? round(($row->overrides / $row->total) * 100, 1) : null,
            ])
            ->sortByDesc('total');

        return view('dashboards.manager', compact(
            'defectCounts',
            'dismissedDefectCounts',
            'reviewedCount',
            'overriddenCount',
            'overrideRate',
            'batchStats',
            'trendLabels',
            'trendValues',
            'shiftStats',
            'checkpointStats',
            'inspectorStats',
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

        // User activity stats
        $userActivityStats = [
            'total_users'         => User::count(),
            'logins_today'        => AuditLog::where('action', 'user.login')->whereDate('created_at', today())->count(),
            'logouts_today'       => AuditLog::where('action', 'user.logout')->whereDate('created_at', today())->count(),
            'accounts_created'    => AuditLog::where('action', 'user.created')->count(),
            'accounts_updated'    => AuditLog::where('action', 'user.updated')->count(),
            'active_today'        => AuditLog::whereDate('created_at', today())->distinct('user_id')->count('user_id'),
        ];

        // Per-user activity summary
        $perUserActivity = User::withCount([
            'auditLogs as total_actions',
            'auditLogs as logins' => fn ($q) => $q->where('action', 'user.login'),
            'auditLogs as logouts' => fn ($q) => $q->where('action', 'user.logout'),
        ])
        ->orderByDesc('total_actions')
        ->get();

        // Login activity trend — last 7 days
        $loginTrend = AuditLog::query()
            ->where('action', 'user.login')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as date, count(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $loginTrendLabels = collect();
        $loginTrendValues = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $loginTrendLabels->push(now()->subDays($i)->format('M j'));
            $loginTrendValues->push($loginTrend->get($date, 0));
        }

        // Recent user-related audit events only
        $userAuditLogs = AuditLog::with('user')
            ->whereIn('action', ['user.login', 'user.logout', 'user.created', 'user.updated', 'inspection.validated', 'inspection.reworked', 'batch.created'])
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('dashboards.admin', compact(
            'users',
            'auditLogs',
            'auditActions',
            'userActivityStats',
            'perUserActivity',
            'loginTrendLabels',
            'loginTrendValues',
            'userAuditLogs',
        ));
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
