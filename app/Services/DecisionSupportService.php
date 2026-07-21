<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Defect;
use App\Models\Inspection;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DecisionSupportService
{
    protected const DEFECT_CAUSES = [
        'scratch'      => ['Improper handling', 'Damaged conveyor or work surface', 'Mishandling during assembly', 'Contact with sharp objects'],
        'cut'          => ['Cutting machine misalignment', 'Worn cutting blade', 'Improper cutting procedure'],
        'hole'         => ['Needle damage', 'Incorrect stitching settings', 'Material puncture during processing'],
        'crease'       => ['Improper lasting process', 'Incorrect storage', 'Material folding during assembly'],
        'excess_glue'  => ['Excessive adhesive application', 'Glue nozzle calibration issue', 'Operator error during sole attachment'],
        'excess_stitch'=> ['Sewing machine tension', 'Needle wear', 'Improper stitching process'],
    ];

    // Minimum current-period defect count before a defect type is eligible to be flagged as spiking.
    protected const SPIKE_MIN_COUNT = 3;

    // A defect type spikes if its share of all defects rose by at least this many percentage points...
    protected const SPIKE_ABS_POINT_THRESHOLD = 8.0;

    // ...or grew by at least this relative multiple over its historical share (catches low-base spikes).
    protected const SPIKE_REL_MULTIPLIER = 1.5;

    // Minimum inspections in an hour bucket before its defect rate is trusted enough to flag.
    protected const HOUR_MIN_SAMPLE = 5;

    // An hour spikes if its per-inspection defect rate exceeds the period average by this multiple.
    protected const HOUR_REL_MULTIPLIER = 1.5;

    public function buildReport(Carbon $from, Carbon $to): array
    {
        $inspections = Inspection::with(['batch', 'defects', 'inspector'])
            ->whereNotNull('action')
            ->whereBetween('inspected_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get();

        $totalInspected  = $inspections->count();
        $passCount       = $inspections->where('action', 'pass')->count();
        $reworkCount     = $inspections->where('action', 'rework')->count();
        $rejectCount     = $inspections->where('action', 'reject')->count();
        $rejectedOrRework = $reworkCount + $rejectCount;

        $passRate   = $totalInspected > 0 ? round(($passCount / $totalInspected) * 100, 1) : null;
        $rejectRate = $totalInspected > 0 ? round(($rejectedOrRework / $totalInspected) * 100, 1) : null;

        $defects = $inspections->flatMap(fn (Inspection $i) => $i->defects);

        $defectDistribution = $defects->groupBy('defect_type')->map->count()->sortDesc();

        $defectDistributionPct = $defectDistribution->map(
            fn ($count) => $defects->count() > 0 ? round(($count / $defects->count()) * 100, 1) : 0
        );

        $hourly = $inspections
            ->groupBy(fn (Inspection $i) => $i->inspected_at?->format('H') ?? '??')
            ->map->count()
            ->sortKeys();

        $peakHour = $hourly->sortDesc()->keys()->first();

        // Per-hour defect rate (defects per inspection), used to predict *when* defects are likely to occur.
        $hourlyDefectStats = $inspections
            ->groupBy(fn (Inspection $i) => $i->inspected_at?->format('H') ?? '??')
            ->map(function (Collection $group) {
                $total       = $group->count();
                $defectCount = $group->sum(fn (Inspection $i) => $i->defects->count());
                return [
                    'inspections' => $total,
                    'defects'     => $defectCount,
                    'rate'        => $total > 0 ? round($defectCount / $total, 2) : 0.0,
                ];
            })
            ->sortKeys();

        $overallDefectRate = $totalInspected > 0 ? round($defects->count() / $totalInspected, 2) : 0.0;

        $spikeHours = $hourlyDefectStats->filter(
            fn ($stats) => $stats['inspections'] >= self::HOUR_MIN_SAMPLE
                && $overallDefectRate > 0
                && $stats['rate'] > $overallDefectRate * self::HOUR_REL_MULTIPLIER
        )->sortByDesc('rate');

        $lineStats = $inspections
            ->groupBy(fn (Inspection $i) => $i->batch?->manufacturing_stage ?? 'unknown')
            ->map(function (Collection $group) {
                $total = $group->count();
                $pass  = $group->where('action', 'pass')->count();
                return [
                    'total'     => $total,
                    'pass'      => $pass,
                    'pass_rate' => $total > 0 ? round(($pass / $total) * 100, 1) : null,
                ];
            });

        $batchStats = $inspections
            ->groupBy(fn (Inspection $i) => $i->batch?->batch_code ?? 'unknown')
            ->map(function (Collection $group) {
                $total   = $group->count();
                $rejects = $group->whereIn('action', ['reject', 'rework'])->count();
                return [
                    'total'       => $total,
                    'rejects'     => $rejects,
                    'reject_rate' => $total > 0 ? round(($rejects / $total) * 100, 1) : null,
                ];
            })
            ->sortByDesc('reject_rate');

        $overallAvgRejectRate = $batchStats->avg('reject_rate');

        $anomalousBatches = $batchStats->filter(
            fn ($stats) => $overallAvgRejectRate !== null
                && $stats['reject_rate'] !== null
                && $stats['reject_rate'] > $overallAvgRejectRate + 10
                && $stats['total'] >= 3
        );

        $historicalAvgRejectRate     = $this->historicalAverageRejectRate($from);
        $historicalDefectDistribution = $this->historicalDefectDistributionPct($from);
        $topDefects                  = $defectDistribution->take(3);
        $riskLevel                   = $this->determineRiskLevel($rejectRate, $historicalAvgRejectRate);

        $defectSpikes = $this->detectDefectSpikes($defectDistribution, $defectDistributionPct, $historicalDefectDistribution);

        $insights = $this->buildInsights(
            $riskLevel,
            $rejectRate,
            $historicalAvgRejectRate,
            $defectSpikes,
            $spikeHours,
            $overallDefectRate,
            $anomalousBatches,
            $lineStats,
        );

        return [
            'period'                   => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'totals'                   => [
                'inspected'   => $totalInspected,
                'pass'        => $passCount,
                'rework'      => $reworkCount,
                'reject'      => $rejectCount,
                'pass_rate'   => $passRate,
                'reject_rate' => $rejectRate,
            ],
            'defect_distribution'      => $defectDistribution,
            'defect_distribution_pct'  => $defectDistributionPct,
            'top_defects'              => $topDefects,
            'hourly_volume'            => $hourly,
            'peak_hour'                => $peakHour,
            'hourly_defect_stats'      => $hourlyDefectStats,
            'overall_defect_rate'      => $overallDefectRate,
            'spike_hours'              => $spikeHours,
            'line_stats'               => $lineStats,
            'batch_stats'              => $batchStats->take(10),
            'anomalous_batches'        => $anomalousBatches,
            'historical_avg_reject_rate' => $historicalAvgRejectRate,
            'historical_defect_distribution_pct' => $historicalDefectDistribution,
            'defect_spikes'            => $defectSpikes,
            'risk_level'               => $riskLevel,
            'confidence'               => $this->determineConfidence($totalInspected, $historicalAvgRejectRate),
            'possible_causes'          => $this->possibleCausesFor($topDefects),
            'recommendations'          => $this->recommendationsFor($topDefects, $anomalousBatches, $lineStats),
            'insights'                 => $insights,
            'has_data'                 => $totalInspected > 0,
        ];
    }

    protected function historicalAverageRejectRate(Carbon $beforeDate): ?float
    {
        $inspections = Inspection::whereNotNull('action')
            ->whereBetween('inspected_at', [
                $beforeDate->copy()->subDays(7)->startOfDay(),
                $beforeDate->copy()->subDay()->endOfDay(),
            ])
            ->get(['action']);

        if ($inspections->isEmpty()) {
            return null;
        }

        $rejected = $inspections->whereIn('action', ['reject', 'rework'])->count();

        return round(($rejected / $inspections->count()) * 100, 1);
    }

    /**
     * Defect-type distribution (as % of all defects) over the 7 days prior to $beforeDate.
     * Used as the baseline to detect defect-type spikes (e.g. a jump in 'cut' defects).
     */
    protected function historicalDefectDistributionPct(Carbon $beforeDate): Collection
    {
        $defects = Defect::query()
            ->join('inspections', 'defects.inspection_id', '=', 'inspections.id')
            ->whereBetween('inspections.inspected_at', [
                $beforeDate->copy()->subDays(7)->startOfDay(),
                $beforeDate->copy()->subDay()->endOfDay(),
            ])
            ->selectRaw('defects.defect_type, count(*) as total')
            ->groupBy('defects.defect_type')
            ->pluck('total', 'defect_type');

        $grandTotal = $defects->sum();

        if ($grandTotal === 0) {
            return collect();
        }

        return $defects->map(fn ($count) => round(($count / $grandTotal) * 100, 1));
    }

    /**
     * Compare current-period defect-type shares against the historical baseline and flag spikes.
     * A spike needs a minimum sample size so a single stray defect can't trigger an alert.
     */
    protected function detectDefectSpikes(Collection $defectDistribution, Collection $defectDistributionPct, Collection $historicalPct): Collection
    {
        return $defectDistributionPct
            ->filter(function ($currentPct, $type) use ($defectDistribution, $historicalPct) {
                if (($defectDistribution[$type] ?? 0) < self::SPIKE_MIN_COUNT) {
                    return false;
                }

                $historical = $historicalPct[$type] ?? 0.0;

                $absSpike = ($currentPct - $historical) >= self::SPIKE_ABS_POINT_THRESHOLD;
                $relSpike = $historical > 0 && $currentPct >= $historical * self::SPIKE_REL_MULTIPLIER;

                // With no historical data at all, only fall back to the absolute threshold against a zero baseline.
                return $absSpike || $relSpike;
            })
            ->map(fn ($currentPct, $type) => [
                'defect_type'    => $type,
                'current_count'  => $defectDistribution[$type] ?? 0,
                'current_pct'    => $currentPct,
                'historical_pct' => $historicalPct[$type] ?? 0.0,
                'point_change'   => round($currentPct - ($historicalPct[$type] ?? 0.0), 1),
            ])
            ->sortByDesc('point_change')
            ->values();
    }

    /**
     * Merge every rule-based signal (risk level, defect-type spikes, time-of-day spikes,
     * anomalous batches, weak stages) into a single, plain-language, severity-ranked list.
     */
    protected function buildInsights(
        string $riskLevel,
        ?float $rejectRate,
        ?float $historicalAvgRejectRate,
        Collection $defectSpikes,
        Collection $spikeHours,
        float $overallDefectRate,
        Collection $anomalousBatches,
        Collection $lineStats,
    ): array {
        $insights = [];

        if (in_array($riskLevel, ['CRITICAL', 'HIGH'], true)) {
            $vs = $historicalAvgRejectRate !== null ? " (historical average {$historicalAvgRejectRate}%)" : '';
            $insights[] = [
                'severity' => $riskLevel === 'CRITICAL' ? 'critical' : 'high',
                'category' => 'risk',
                'message'  => "Overall reject/rework rate is {$rejectRate}%{$vs} — risk level is {$riskLevel}.",
            ];
        }

        foreach ($defectSpikes as $spike) {
            $label   = str_replace('_', ' ', $spike['defect_type']);
            $causes  = self::DEFECT_CAUSES[$spike['defect_type']] ?? [];
            $causeText = $causes !== [] ? ' Likely cause: ' . $causes[0] . '.' : '';

            $insights[] = [
                'severity' => 'high',
                'category' => 'defect_spike',
                'message'  => "Spike in '{$label}' defects: {$spike['current_pct']}% of all defects this period vs {$spike['historical_pct']}% historically (+{$spike['point_change']} pts, {$spike['current_count']} occurrences).{$causeText}",
            ];
        }

        foreach ($spikeHours as $hour => $stats) {
            $upliftPct = $overallDefectRate > 0 ? round((($stats['rate'] - $overallDefectRate) / $overallDefectRate) * 100) : 0;
            $insights[] = [
                'severity' => 'medium',
                'category' => 'time_pattern',
                'message'  => "Defects are more likely to occur around {$hour}:00–" . str_pad((string) ((int) $hour + 1), 2, '0', STR_PAD_LEFT) . ":00 — defect rate is {$upliftPct}% above the period average ({$stats['defects']} defects across {$stats['inspections']} inspections).",
            ];
        }

        if ($anomalousBatches->isNotEmpty()) {
            $insights[] = [
                'severity' => 'high',
                'category' => 'batch_anomaly',
                'message'  => 'Unusually high reject rate detected in batch(es): ' . $anomalousBatches->keys()->implode(', ') . '. Cross-reference against raw materials and process records.',
            ];
        }

        $worstLine = $lineStats->sortBy('pass_rate')->first();
        $worstLineKey = $lineStats->sortBy('pass_rate')->keys()->first();
        if ($worstLine !== null && $worstLine['pass_rate'] !== null && $worstLine['pass_rate'] < 90) {
            $insights[] = [
                'severity' => 'medium',
                'category' => 'stage_risk',
                'message'  => "The '{$worstLineKey}' stage has the lowest pass rate ({$worstLine['pass_rate']}%) — consider increasing inspection frequency there.",
            ];
        }

        if (empty($insights)) {
            $insights[] = [
                'severity' => 'info',
                'category' => 'none',
                'message'  => 'No significant spikes or risk patterns detected for this period.',
            ];
        }

        $severityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'info' => 3];
        usort($insights, fn ($a, $b) => $severityOrder[$a['severity']] <=> $severityOrder[$b['severity']]);

        return $insights;
    }

    protected function determineRiskLevel(?float $rejectRate, ?float $historicalAvg): string
    {
        if ($rejectRate === null) return 'LOW';
        if ($rejectRate >= 25)   return 'CRITICAL';
        if ($rejectRate >= 15)   return 'HIGH';
        if ($historicalAvg !== null && $rejectRate > $historicalAvg + 5) return 'MEDIUM';
        if ($rejectRate >= 8)    return 'MEDIUM';
        return 'LOW';
    }

    protected function determineConfidence(int $totalInspected, ?float $historicalAvg): string
    {
        if ($totalInspected === 0)                                return 'Low';
        if ($totalInspected >= 30 && $historicalAvg !== null)    return 'High';
        if ($totalInspected >= 10)                               return 'Medium';
        return 'Low';
    }

    protected function possibleCausesFor(Collection $topDefects): array
    {
        return $topDefects->keys()
            ->mapWithKeys(fn ($type) => [$type => self::DEFECT_CAUSES[$type] ?? []])
            ->toArray();
    }

    protected function recommendationsFor(Collection $topDefects, Collection $anomalousBatches, Collection $lineStats): array
    {
        $recommendations = [];

        foreach ($topDefects->keys() as $type) {
            $recommendations[] = match ($type) {
                'scratch'       => 'Inspect handling procedures and work surfaces for the most affected process stage.',
                'cut'           => 'Inspect cutting machine calibration and blade condition.',
                'hole'          => 'Review stitching machine needle condition and settings.',
                'crease'        => 'Review lasting process and material storage conditions.',
                'excess_glue'   => 'Inspect glue nozzle calibration and adhesive application procedure.',
                'excess_stitch' => 'Review sewing machine tension and stitching procedure.',
                default         => "Investigate root cause for '{$type}' defects.",
            };
        }

        if ($anomalousBatches->isNotEmpty()) {
            $recommendations[] = 'Cross-reference anomalous batches ('
                . $anomalousBatches->keys()->implode(', ')
                . ') against raw material supplier and process records.';
        }

        $worstLine = $lineStats->sortBy('pass_rate')->first();
        if ($worstLine !== null && $worstLine['pass_rate'] !== null && $worstLine['pass_rate'] < 90) {
            $recommendations[] = 'Increase inspection frequency on the lowest-performing manufacturing stage.';
        }

        $recommendations[] = 'Schedule preventive maintenance review for equipment associated with top defect types.';

        return array_values(array_unique($recommendations));
    }
}
