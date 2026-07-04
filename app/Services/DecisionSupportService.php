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

        $historicalAvgRejectRate = $this->historicalAverageRejectRate($from);
        $topDefects              = $defectDistribution->take(3);
        $riskLevel               = $this->determineRiskLevel($rejectRate, $historicalAvgRejectRate);

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
            'line_stats'               => $lineStats,
            'batch_stats'              => $batchStats->take(10),
            'anomalous_batches'        => $anomalousBatches,
            'historical_avg_reject_rate' => $historicalAvgRejectRate,
            'risk_level'               => $riskLevel,
            'confidence'               => $this->determineConfidence($totalInspected, $historicalAvgRejectRate),
            'possible_causes'          => $this->possibleCausesFor($topDefects),
            'recommendations'          => $this->recommendationsFor($topDefects, $anomalousBatches, $lineStats),
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
