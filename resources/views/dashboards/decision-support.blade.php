<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('AI Support Suggestions') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-wrap justify-between items-center gap-3">
                <form method="GET" action="{{ route('decision-support.index') }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label for="date_from" class="block text-xs font-medium text-gray-600 mb-1">{{ __('From') }}</label>
                        <input type="date" id="date_from" name="date_from" value="{{ $report['period']['from'] }}"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <label for="date_to" class="block text-xs font-medium text-gray-600 mb-1">{{ __('To') }}</label>
                        <input type="date" id="date_to" name="date_to" value="{{ $report['period']['to'] }}"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md shadow-sm hover:bg-indigo-700 transition">
                        {{ __('Apply') }}
                    </button>
                </form>

                <a href="{{ route('dashboard.manager') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    &larr; {{ __('Back to Dashboard') }}
                </a>
            </div>

            @if (! $report['has_data'])
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-gray-500">{{ __('No inspection data recorded for this period.') }}</p>
                </div>
            @else
                {{-- Risk banner --}}
                @php
                    $riskColors = [
                        'CRITICAL' => 'bg-red-50 border-red-300 text-red-800',
                        'HIGH'     => 'bg-orange-50 border-orange-300 text-orange-800',
                        'MEDIUM'   => 'bg-yellow-50 border-yellow-300 text-yellow-800',
                        'LOW'      => 'bg-green-50 border-green-300 text-green-800',
                    ];
                @endphp
                <div class="border rounded-lg p-4 flex flex-wrap items-center justify-between gap-4 {{ $riskColors[$report['risk_level']] ?? 'bg-gray-50 border-gray-300 text-gray-800' }}">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide opacity-70">{{ __('Risk Level') }}</div>
                        <div class="text-2xl font-bold">{{ $report['risk_level'] }}</div>
                    </div>
                    <div class="text-sm">
                        <div><span class="font-semibold">{{ __('Reject/Rework Rate:') }}</span> {{ $report['totals']['reject_rate'] ?? '—' }}%</div>
                        <div><span class="font-semibold">{{ __('Historical Avg:') }}</span> {{ $report['historical_avg_reject_rate'] ?? '—' }}%</div>
                        <div><span class="font-semibold">{{ __('Confidence:') }}</span> {{ $report['confidence'] }}</div>
                    </div>
                </div>

                {{-- AI Insights --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('AI Insights') }}</h3>
                    @php
                        $severityStyles = [
                            'critical' => ['badge' => 'bg-red-100 text-red-800', 'border' => 'border-red-200'],
                            'high'     => ['badge' => 'bg-orange-100 text-orange-800', 'border' => 'border-orange-200'],
                            'medium'   => ['badge' => 'bg-yellow-100 text-yellow-800', 'border' => 'border-yellow-200'],
                            'info'     => ['badge' => 'bg-gray-100 text-gray-700', 'border' => 'border-gray-200'],
                        ];
                    @endphp
                    <ul class="space-y-3">
                        @foreach ($report['insights'] as $insight)
                            @php $style = $severityStyles[$insight['severity']] ?? $severityStyles['info']; @endphp
                            <li class="border rounded-lg p-4 {{ $style['border'] }}">
                                <div class="flex items-start gap-3">
                                    <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-semibold uppercase {{ $style['badge'] }}">
                                        {{ $insight['severity'] }}
                                    </span>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $insight['summary'] ?? $insight['message'] }}</p>
                                        @if (isset($insight['summary']))
                                            <p class="text-xs text-gray-500 mt-1">{{ $insight['message'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Defect Distribution: current vs historical --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Defect Type: Current vs Historical Share') }}</h3>
                        @if ($report['defect_distribution_pct']->isEmpty())
                            <p class="text-gray-500">{{ __('No defects recorded in this period.') }}</p>
                        @else
                            @php
                                $defectTypes = $report['defect_distribution_pct']->keys();
                                $currentVals = $report['defect_distribution_pct']->values();
                                $historicalVals = $defectTypes->map(fn ($t) => $report['historical_defect_distribution_pct'][$t] ?? 0);
                            @endphp
                            <canvas id="defectSpikeChart"
                                data-labels="{{ $defectTypes->map(fn ($t) => str_replace('_', ' ', $t))->toJson() }}"
                                data-current="{{ $currentVals->toJson() }}"
                                data-historical="{{ $historicalVals->toJson() }}"></canvas>
                            @push('scripts')
                                <script>
                                    document.addEventListener('DOMContentLoaded', () => {
                                        const canvas = document.getElementById('defectSpikeChart');
                                        if (!canvas || !window.Chart) return;
                                        new window.Chart(canvas, {
                                            type: 'bar',
                                            data: {
                                                labels: JSON.parse(canvas.dataset.labels),
                                                datasets: [
                                                    { label: 'Current period %', data: JSON.parse(canvas.dataset.current), backgroundColor: '#6366f1' },
                                                    { label: 'Historical avg %', data: JSON.parse(canvas.dataset.historical), backgroundColor: '#d1d5db' },
                                                ],
                                            },
                                            options: { scales: { y: { beginAtZero: true } }, plugins: { legend: { position: 'bottom' } } },
                                        });
                                    });
                                </script>
                            @endpush
                        @endif
                    </div>

                    {{-- Hourly defect rate --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Defect Rate by Hour of Day') }}</h3>
                        @if ($report['hourly_defect_stats']->isEmpty())
                            <p class="text-gray-500">{{ __('No inspection activity in this period.') }}</p>
                        @else
                            @php
                                $hours = $report['hourly_defect_stats']->keys();
                                $rates = $report['hourly_defect_stats']->map(fn ($s) => $s['rate'])->values();
                                $spikeHourKeys = $report['spike_hours']->keys();
                            @endphp
                            <canvas id="hourlyDefectChart"
                                data-labels="{{ $hours->map(fn ($h) => $h . ':00')->toJson() }}"
                                data-values="{{ $rates->toJson() }}"
                                data-spikes="{{ $hours->map(fn ($h) => $spikeHourKeys->contains($h))->toJson() }}"></canvas>
                            <p class="text-xs text-gray-500 mt-2">{{ __('Bars in red are flagged as time-of-day spikes.') }}</p>
                            @push('scripts')
                                <script>
                                    document.addEventListener('DOMContentLoaded', () => {
                                        const canvas = document.getElementById('hourlyDefectChart');
                                        if (!canvas || !window.Chart) return;
                                        const spikes = JSON.parse(canvas.dataset.spikes);
                                        const colors = spikes.map(isSpike => isSpike ? '#ef4444' : '#6366f1');
                                        new window.Chart(canvas, {
                                            type: 'bar',
                                            data: {
                                                labels: JSON.parse(canvas.dataset.labels),
                                                datasets: [{ label: 'Defects per inspection', data: JSON.parse(canvas.dataset.values), backgroundColor: colors }],
                                            },
                                            options: { scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } },
                                        });
                                    });
                                </script>
                            @endpush
                        @endif
                    </div>
                </div>

                {{-- Anomalous Batches --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Anomalous Batches') }}</h3>
                    @if ($report['anomalous_batches']->isEmpty())
                        <p class="text-gray-500">{{ __('No batches with abnormally high reject rates in this period.') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                                        <th class="py-2 pr-4">{{ __('Batch') }}</th>
                                        <th class="py-2 pr-4">{{ __('Total') }}</th>
                                        <th class="py-2 pr-4">{{ __('Rejects') }}</th>
                                        <th class="py-2 pr-4">{{ __('Reject Rate') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($report['anomalous_batches'] as $batchCode => $stats)
                                        <tr>
                                            <td class="py-2 pr-4 font-medium text-gray-900">{{ $batchCode }}</td>
                                            <td class="py-2 pr-4">{{ $stats['total'] }}</td>
                                            <td class="py-2 pr-4 text-red-600">{{ $stats['rejects'] }}</td>
                                            <td class="py-2 pr-4 font-semibold text-red-600">{{ $stats['reject_rate'] }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Possible Causes --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Possible Causes (Top Defects)') }}</h3>
                        @if (empty($report['possible_causes']))
                            <p class="text-gray-500">{{ __('No top defects identified.') }}</p>
                        @else
                            <div class="space-y-4">
                                @foreach ($report['possible_causes'] as $type => $causes)
                                    <div>
                                        <div class="font-semibold text-gray-800 capitalize mb-1">{{ str_replace('_', ' ', $type) }}</div>
                                        <ul class="list-disc list-inside text-sm text-gray-600 space-y-0.5">
                                            @foreach ($causes as $cause)
                                                <li>{{ $cause }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Recommendations --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Recommendations') }}</h3>
                        @if (empty($report['recommendations']))
                            <p class="text-gray-500">{{ __('No recommendations for this period.') }}</p>
                        @else
                            <ul class="list-disc list-inside text-sm text-gray-700 space-y-1.5">
                                @foreach ($report['recommendations'] as $recommendation)
                                    <li>{{ $recommendation }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
