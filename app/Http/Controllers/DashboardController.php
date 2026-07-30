<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Defect;
use App\Models\Inspection;
use App\Models\User;
use App\Notifications\InspectionMarkedForRework;
use App\Services\DecisionSupportService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
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

        if ($validated['action'] === 'rework') {
            $shift = $inspection->batch?->shift;

            $constructors = User::where('role', 'shoe_constructor')
                ->when($shift, fn ($query) => $query->where('shift', $shift))
                ->get();

            Notification::send($constructors, new InspectionMarkedForRework($inspection));
        }

        return redirect()->route('dashboard.inspector')
            ->with('status', "Inspection #{$inspection->id} marked as {$validated['action']}.");
    }

    public function manager(Request $request, DecisionSupportService $decisionSupportService): View
    {
        $request->validate([
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'trend_batch_id' => ['nullable', 'integer', 'exists:batches,id'],
        ]);

        $batches = Batch::orderByDesc('production_date')->orderByDesc('id')->get(['id', 'batch_code']);
        $selectedBatchId = $request->integer('batch_id') ?: null;

        // Defect distribution — filterable by batch
        $defectCounts = Defect::query()
            ->join('inspections', 'defects.inspection_id', '=', 'inspections.id')
            ->when($selectedBatchId, fn ($query) => $query->where('inspections.batch_id', $selectedBatchId))
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

        // Defect trend — hourly pattern, either for a single batch or the overall historical pattern
        $selectedTrendBatchId = $request->integer('trend_batch_id') ?: null;
        $trendMode = $selectedTrendBatchId ? 'batch' : 'overall';

        $hourlyDefects = Defect::query()
            ->join('inspections', 'defects.inspection_id', '=', 'inspections.id')
            ->when($selectedTrendBatchId, fn ($query) => $query->where('inspections.batch_id', $selectedTrendBatchId))
            ->selectRaw('HOUR(defects.created_at) as hour, count(*) as total')
            ->groupBy('hour')
            ->pluck('total', 'hour');

        $trendLabels = collect();
        $trendValues = collect();
        for ($hour = 0; $hour < 24; $hour++) {
            $trendLabels->push(now()->setTime($hour, 0)->format('g A'));
            $trendValues->push($hourlyDefects->get($hour, 0));
        }

        // Overall mode: surface which hours historically spike and what kind of defect drives each spike
        $hourlySpikes = collect();
        $spikeHours = collect();
        if ($trendMode === 'overall' && $trendValues->sum() > 0) {
            $average = $trendValues->avg();
            $threshold = max(2, $average * 1.5);

            $hourlyByType = Defect::query()
                ->selectRaw('HOUR(created_at) as hour, defect_type, count(*) as total')
                ->groupBy('hour', 'defect_type')
                ->get()
                ->groupBy('hour');

            $hourlySpikes = collect(range(0, 23))
                ->filter(fn ($hour) => $trendValues[$hour] >= $threshold)
                ->map(function ($hour) use ($trendValues, $hourlyByType) {
                    $topType = optional($hourlyByType->get($hour))->sortByDesc('total')->first();

                    return [
                        'hour'          => now()->setTime($hour, 0)->format('g A'),
                        'total'         => $trendValues[$hour],
                        'dominant_type' => $topType ? str_replace('_', ' ', $topType->defect_type) : null,
                    ];
                })
                ->sortByDesc('total')
                ->values();

            $spikeHours = $hourlySpikes->pluck('hour');
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

        // Surface the single most urgent AI decision-support insight for the last 7 days.
        $decisionSupport = $decisionSupportService->buildReport(now()->subDays(6), now());
        $topInsight = collect($decisionSupport['insights'])->first(fn ($i) => $i['severity'] !== 'info');

        return view('dashboards.manager', compact(
            'batches',
            'selectedBatchId',
            'defectCounts',
            'dismissedDefectCounts',
            'reviewedCount',
            'overriddenCount',
            'overrideRate',
            'batchStats',
            'selectedTrendBatchId',
            'trendMode',
            'trendLabels',
            'trendValues',
            'hourlySpikes',
            'spikeHours',
            'shiftStats',
            'checkpointStats',
            'inspectorStats',
            'topInsight',
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
