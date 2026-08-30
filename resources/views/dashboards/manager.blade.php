<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Product Manager Dashboard') }}
        </h2>
    </x-slot>

    @php $tab = request('tab', 'overview'); @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 rounded-lg p-4">
                    {{ session('status') }}
                </div>
            @endif

            @if ($topInsight)
                @php
                    $bannerStyles = [
                        'critical' => 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700 text-red-800 dark:text-red-300',
                        'high'     => 'bg-orange-50 dark:bg-orange-900/20 border-orange-300 dark:border-orange-700 text-orange-800 dark:text-orange-300',
                        'medium'   => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-300 dark:border-yellow-700 text-yellow-800 dark:text-yellow-300',
                    ];
                @endphp
                <a href="{{ route('decision-support.index') }}" class="block border rounded-lg p-4 hover:opacity-90 transition {{ $bannerStyles[$topInsight['severity']] ?? 'bg-gray-50 dark:bg-gray-700/50 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-gray-800 dark:text-gray-100' }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-semibold uppercase bg-white dark:bg-gray-800/60">
                                {{ __('AI Insight') }}
                            </span>
                            <p class="text-sm font-medium">{{ $topInsight['summary'] ?? $topInsight['message'] }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-semibold whitespace-nowrap">{{ __('View details →') }}</span>
                    </div>
                </a>
            @endif

            {{-- Tab Navigation --}}
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Tabs">
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'overview']) }}"
                        class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition {{ $tab === 'overview' ? 'border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-500' }}">
                        {{ __('Overview') }}
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'quality']) }}"
                        class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition {{ $tab === 'quality' ? 'border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-500' }}">
                        {{ __('Defects & Quality') }}
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'team']) }}"
                        class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition {{ $tab === 'team' ? 'border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-500' }}">
                        {{ __('Team & Rework') }}
                    </a>
                </nav>
            </div>

            @if ($tab === 'overview')
                <div class="space-y-6">
                    {{-- Leather Yield: Expected vs. Actual After Filtration --}}
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Leather Yield</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="border rounded-lg p-4">
                                <div class="text-sm text-gray-500 dark:text-gray-400">Expected Pieces</div>
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $totalExpectedPieces }}</div>
                            </div>
                            <div class="border rounded-lg p-4">
                                <div class="text-sm text-gray-500 dark:text-gray-400">Produced</div>
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $totalProducedPieces }}</div>
                            </div>
                            <div class="border rounded-lg p-4">
                                <div class="text-sm text-gray-500 dark:text-gray-400">Passed After Filtration</div>
                                <div class="text-2xl font-semibold text-green-600 dark:text-green-400">{{ $totalPassedPieces }}</div>
                            </div>
                            <div class="border-2 rounded-lg p-4 @if(($overallYieldRate ?? 0) >= 90) border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 @elseif(($overallYieldRate ?? 0) >= 75) border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20 @else border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 @endif">
                                <div class="text-sm text-gray-500 dark:text-gray-400">Yield</div>
                                <div class="text-3xl font-bold @if(($overallYieldRate ?? 0) >= 90) text-green-600 dark:text-green-400 @elseif(($overallYieldRate ?? 0) >= 75) text-yellow-600 dark:text-yellow-400 @else text-red-600 dark:text-red-400 @endif">
                                    {{ $overallYieldRate !== null ? $overallYieldRate.'%' : '—' }}
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">{{ __('Yield = pieces that passed inspection ÷ total expected pieces, across all batches.') }}</p>
                    </div>

                    {{-- Batch Pass/Fail Rates --}}
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Batch Pass / Rework / Reject Rates</h3>
                        @if ($batchStats->isEmpty())
                            <p class="text-gray-500 dark:text-gray-400">No batch data recorded yet.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            <th class="py-2 pr-4">Batch</th>
                                            <th class="py-2 pr-4">Stage</th>
                                            <th class="py-2 pr-4">Expected</th>
                                            <th class="py-2 pr-4">Produced</th>
                                            <th class="py-2 pr-4">Pass</th>
                                            <th class="py-2 pr-4">Rework</th>
                                            <th class="py-2 pr-4">Reject</th>
                                            <th class="py-2 pr-4">Pass Rate</th>
                                            <th class="py-2 pr-4">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        @foreach ($batchStats as $batch)
                                            <tr>
                                                <td class="py-2 pr-4 font-medium text-gray-900 dark:text-white">{{ $batch['batch_code'] }}</td>
                                                <td class="py-2 pr-4 capitalize">{{ str_replace('_', ' ', $batch['stage']) }}</td>
                                                <td class="py-2 pr-4">{{ $batch['expected_pieces'] ?? '—' }}</td>
                                                <td class="py-2 pr-4">{{ $batch['produced'] }}</td>
                                                <td class="py-2 pr-4 text-green-600 dark:text-green-400">{{ $batch['pass'] }}</td>
                                                <td class="py-2 pr-4 text-yellow-600 dark:text-yellow-400">{{ $batch['rework'] }}</td>
                                                <td class="py-2 pr-4 text-red-600 dark:text-red-400">{{ $batch['reject'] }}</td>
                                                <td class="py-2 pr-4">
                                                    @if ($batch['pass_rate'] !== null)
                                                        <span class="font-semibold @if($batch['pass_rate'] >= 90) text-green-600 dark:text-green-400 @elseif($batch['pass_rate'] >= 75) text-yellow-600 dark:text-yellow-400 @else text-red-600 dark:text-red-400 @endif">
                                                            {{ $batch['pass_rate'] }}%
                                                        </span>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td class="py-2 pr-4">
                                                    <a href="{{ route('manager.batches.edit', $batch['id']) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium hover:underline">Edit</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @elseif ($tab === 'quality')
                <div class="space-y-6">
                    {{-- Defect Distribution --}}
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Defect Distribution</h3>
                            <form method="GET" action="{{ route('dashboard.manager') }}" class="flex flex-wrap items-center gap-2">
                                <input type="hidden" name="tab" value="quality">
                                <input type="hidden" name="trend_batch_id" value="{{ $selectedTrendBatchId }}">
                                <select name="batch_id" onchange="this.form.submit()" class="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="">All batches</option>
                                    @foreach ($batches as $batch)
                                        <option value="{{ $batch->id }}" @selected($selectedBatchId == $batch->id)>{{ $batch->batch_code }}</option>
                                    @endforeach
                                </select>
                                @if ($selectedBatchId)
                                    <a href="{{ request()->fullUrlWithQuery(['batch_id' => null]) }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Clear</a>
                                @endif
                            </form>
                        </div>
                        @if ($defectCounts->isEmpty())
                            <p class="text-gray-500 dark:text-gray-400">No defect data recorded yet.</p>
                        @else
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-center">
                                <div class="max-w-xs mx-auto">
                                    <canvas id="defectTypeChart"
                                        data-labels="{{ $defectCounts->keys()->map(fn ($t) => str_replace('_', ' ', $t))->toJson() }}"
                                        data-values="{{ $defectCounts->values()->toJson() }}"></canvas>
                                </div>
                                <ul class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-2 gap-4">
                                    @foreach ($defectCounts as $type => $total)
                                        <li class="border rounded-lg p-4">
                                            <div class="text-sm text-gray-500 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $type) }}</div>
                                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $total }}</div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            @push('scripts')
                                <script>
                                    document.addEventListener('DOMContentLoaded', () => {
                                        const canvas = document.getElementById('defectTypeChart');
                                        if (!canvas || !window.Chart) return;
                                        new window.Chart(canvas, {
                                            type: 'doughnut',
                                            data: {
                                                labels: JSON.parse(canvas.dataset.labels),
                                                datasets: [{ data: JSON.parse(canvas.dataset.values), backgroundColor: ['#6366f1','#f59e0b','#ef4444','#10b981','#3b82f6','#a855f7'] }],
                                            },
                                            options: { plugins: { legend: { position: 'bottom' } } },
                                        });
                                    });
                                </script>
                            @endpush
                        @endif
                    </div>

                    {{-- Defect Trend --}}
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Defect Trend by Hour</h3>
                            <form method="GET" action="{{ route('dashboard.manager') }}" class="flex flex-wrap items-center gap-2">
                                <input type="hidden" name="tab" value="quality">
                                <input type="hidden" name="batch_id" value="{{ $selectedBatchId }}">
                                <select name="trend_batch_id" onchange="this.form.submit()" class="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="" @selected($trendMode === 'overall')>Overall (historical pattern)</option>
                                    @foreach ($batches as $batch)
                                        <option value="{{ $batch->id }}" @selected($selectedTrendBatchId == $batch->id)>{{ $batch->batch_code }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            @if ($trendMode === 'overall')
                                Defect count for each hour of the day, across all recorded history.
                            @else
                                Defect count for each hour of the day, for the selected batch only.
                            @endif
                        </p>
                        @if ($trendValues->sum() === 0)
                            <p class="text-gray-500 dark:text-gray-400">No defect data recorded yet.</p>
                        @else
                            <div class="w-full">
                                <canvas id="defectTrendChart"
                                    data-labels="{{ $trendLabels->toJson() }}"
                                    data-values="{{ $trendValues->toJson() }}"
                                    data-spikes="{{ $spikeHours->toJson() }}"></canvas>
                            </div>
                            @if ($trendMode === 'overall' && $hourlySpikes->isNotEmpty())
                                <div class="mt-4 border-t pt-4">
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Historical spike hours</h4>
                                    <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @foreach ($hourlySpikes as $spike)
                                            <li class="flex items-center gap-2 text-sm bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md px-3 py-2">
                                                <span class="font-semibold text-red-700 dark:text-red-400">{{ $spike['hour'] }}</span>
                                                <span class="text-gray-600 dark:text-gray-400">{{ $spike['total'] }} defects</span>
                                                @if ($spike['dominant_type'])
                                                    <span class="ml-auto text-xs text-gray-500 dark:text-gray-400 capitalize">mostly {{ $spike['dominant_type'] }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @push('scripts')
                                <script>
                                    document.addEventListener('DOMContentLoaded', () => {
                                        const canvas = document.getElementById('defectTrendChart');
                                        if (!canvas || !window.Chart) return;
                                        const labels = JSON.parse(canvas.dataset.labels);
                                        const values = JSON.parse(canvas.dataset.values);
                                        const spikes = new Set(JSON.parse(canvas.dataset.spikes));
                                        const pointColors = labels.map(label => spikes.has(label) ? '#ef4444' : '#6366f1');
                                        const pointRadii = labels.map(label => spikes.has(label) ? 5 : 3);
                                        new window.Chart(canvas, {
                                            type: 'line',
                                            data: {
                                                labels,
                                                datasets: [{
                                                    label: 'Defects Detected',
                                                    data: values,
                                                    borderColor: '#6366f1',
                                                    backgroundColor: 'rgba(99,102,241,0.1)',
                                                    pointBackgroundColor: pointColors,
                                                    pointBorderColor: pointColors,
                                                    pointRadius: pointRadii,
                                                    fill: true,
                                                    tension: 0.3,
                                                }],
                                            },
                                            options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } },
                                        });
                                    });
                                </script>
                            @endpush
                        @endif
                    </div>

                    {{-- Checkpoint Comparison --}}
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Checkpoint Comparison</h3>
                        @if ($checkpointStats->isEmpty())
                            <p class="text-gray-500 dark:text-gray-400 text-sm">No checkpoint data available.</p>
                        @else
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach ($checkpointStats as $checkpoint => $stats)
                                    <li class="border rounded-lg p-4">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-semibold text-gray-800 dark:text-gray-100 capitalize">{{ str_replace('_', ' ', $checkpoint) }}</span>
                                            <span class="text-sm font-medium @if(($stats['pass_rate'] ?? 0) >= 90) text-green-600 dark:text-green-400 @elseif(($stats['pass_rate'] ?? 0) >= 75) text-yellow-600 dark:text-yellow-400 @else text-red-600 dark:text-red-400 @endif">
                                                {{ $stats['pass_rate'] ?? '—' }}% pass
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-100 dark:bg-gray-900 rounded-full h-2">
                                            <div class="h-2 rounded-full @if(($stats['pass_rate'] ?? 0) >= 90) bg-green-500 @elseif(($stats['pass_rate'] ?? 0) >= 75) bg-yellow-500 @else bg-red-500 @endif"
                                                style="width: {{ $stats['pass_rate'] ?? 0 }}%"></div>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $stats['total'] }} inspections</div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @else
                <div class="space-y-6">
                    {{-- Rework Load & Turnaround by Station --}}
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Rework Load & Turnaround by Station</h3>
                        @if ($reworkStationStats->isEmpty())
                            <p class="text-gray-500 dark:text-gray-400 text-sm">No rework data recorded yet.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            <th class="py-2 pr-4">Station</th>
                                            <th class="py-2 pr-4">Assigned</th>
                                            <th class="py-2 pr-4">Resolved</th>
                                            <th class="py-2 pr-4">Avg. Turnaround</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        @foreach ($reworkStationStats as $station => $stats)
                                            <tr>
                                                <td class="py-2 pr-4 font-medium text-gray-900 dark:text-white capitalize">{{ str_replace('_', ' ', $station) }}</td>
                                                <td class="py-2 pr-4">{{ $stats['total'] }}</td>
                                                <td class="py-2 pr-4">{{ $stats['resolved'] }}</td>
                                                <td class="py-2 pr-4">{{ $stats['avg_turnaround'] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- Inspector Performance --}}
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Inspector Performance</h3>
                        @if ($inspectorStats->isEmpty())
                            <p class="text-gray-500 dark:text-gray-400 text-sm">No inspector activity recorded yet.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            <th class="py-2 pr-4">Inspector</th>
                                            <th class="py-2 pr-4">Total Reviewed</th>
                                            <th class="py-2 pr-4">Passed</th>
                                            <th class="py-2 pr-4">AI Overrides</th>
                                            <th class="py-2 pr-4">Override Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        @foreach ($inspectorStats as $inspector)
                                            <tr>
                                                <td class="py-2 pr-4 font-medium text-gray-900 dark:text-white">{{ $inspector['name'] }}</td>
                                                <td class="py-2 pr-4">{{ $inspector['total'] }}</td>
                                                <td class="py-2 pr-4 text-green-600 dark:text-green-400">{{ $inspector['pass'] }}</td>
                                                <td class="py-2 pr-4">{{ $inspector['overrides'] }}</td>
                                                <td class="py-2 pr-4">
                                                    <span class="font-medium @if(($inspector['override_rate'] ?? 0) > 30) text-red-600 dark:text-red-400 @elseif(($inspector['override_rate'] ?? 0) > 15) text-yellow-600 dark:text-yellow-400 @else text-gray-700 dark:text-gray-300 @endif">
                                                        {{ $inspector['override_rate'] ?? '—' }}%
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- AI Override Analytics --}}
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">AI Override Analytics</h3>
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="border rounded-lg p-4">
                                <div class="text-sm text-gray-500 dark:text-gray-400">Reviewed Inspections</div>
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $reviewedCount }}</div>
                            </div>
                            <div class="border rounded-lg p-4">
                                <div class="text-sm text-gray-500 dark:text-gray-400">AI Overridden</div>
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $overriddenCount }}</div>
                            </div>
                            <div class="border rounded-lg p-4">
                                <div class="text-sm text-gray-500 dark:text-gray-400">Override Rate</div>
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $overrideRate }}%</div>
                            </div>
                        </div>
                        @if ($dismissedDefectCounts->isNotEmpty())
                            <div class="max-w-xl">
                                <canvas id="aiOverrideChart"
                                    data-labels="{{ $dismissedDefectCounts->keys()->map(fn ($t) => str_replace('_', ' ', $t))->toJson() }}"
                                    data-values="{{ $dismissedDefectCounts->values()->toJson() }}"></canvas>
                            </div>
                            @push('scripts')
                                <script>
                                    document.addEventListener('DOMContentLoaded', () => {
                                        const canvas = document.getElementById('aiOverrideChart');
                                        if (!canvas || !window.Chart) return;
                                        new window.Chart(canvas, {
                                            type: 'bar',
                                            data: {
                                                labels: JSON.parse(canvas.dataset.labels),
                                                datasets: [{ label: 'Dismissed AI flags by defect type', data: JSON.parse(canvas.dataset.values), backgroundColor: '#ef4444' }],
                                            },
                                            options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } },
                                        });
                                    });
                                </script>
                            @endpush
                        @else
                            <p class="text-gray-500 dark:text-gray-400">No dismissed defect flags recorded yet.</p>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
