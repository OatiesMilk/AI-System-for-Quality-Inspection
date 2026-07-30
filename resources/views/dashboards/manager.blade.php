<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Product Manager Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="flex justify-end gap-3">
                <a href="{{ route('decision-support.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-md shadow-sm hover:bg-indigo-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                    </svg>
                    {{ __('AI Support Suggestions') }}
                </a>
                <a href="{{ route('manager.batches.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-md shadow-sm hover:bg-gray-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V4z" clip-rule="evenodd"/>
                    </svg>
                    {{ __('Create Batch') }}
                </a>
            </div>

            @if ($topInsight)
                @php
                    $bannerStyles = [
                        'critical' => 'bg-red-50 border-red-300 text-red-800',
                        'high'     => 'bg-orange-50 border-orange-300 text-orange-800',
                        'medium'   => 'bg-yellow-50 border-yellow-300 text-yellow-800',
                    ];
                @endphp
                <a href="{{ route('decision-support.index') }}" class="block border rounded-lg p-4 hover:opacity-90 transition {{ $bannerStyles[$topInsight['severity']] ?? 'bg-gray-50 border-gray-300 text-gray-800' }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-semibold uppercase bg-white/60">
                                {{ __('AI Insight') }}
                            </span>
                            <p class="text-sm font-medium">{{ $topInsight['summary'] ?? $topInsight['message'] }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-semibold whitespace-nowrap">{{ __('View details →') }}</span>
                    </div>
                </a>
            @endif

            {{-- Defect Distribution --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Defect Distribution</h3>
                    <form method="GET" action="{{ route('dashboard.manager') }}" class="flex flex-wrap items-center gap-2">
                        <input type="hidden" name="trend_batch_id" value="{{ $selectedTrendBatchId }}">
                        <select name="batch_id" onchange="this.form.submit()" class="text-sm rounded-md border-gray-300">
                            <option value="">All batches</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch->id }}" @selected($selectedBatchId == $batch->id)>{{ $batch->batch_code }}</option>
                            @endforeach
                        </select>
                        @if ($selectedBatchId)
                            <a href="{{ route('dashboard.manager', ['trend_batch_id' => $selectedTrendBatchId]) }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                        @endif
                    </form>
                </div>
                @if ($defectCounts->isEmpty())
                    <p class="text-gray-500">No defect data recorded yet.</p>
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
                                    <div class="text-sm text-gray-500 capitalize">{{ str_replace('_', ' ', $type) }}</div>
                                    <div class="text-2xl font-semibold text-gray-900">{{ $total }}</div>
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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-1">
                    <h3 class="text-lg font-medium text-gray-900">Defect Trend by Hour</h3>
                    <form method="GET" action="{{ route('dashboard.manager') }}" class="flex flex-wrap items-center gap-2">
                        <input type="hidden" name="batch_id" value="{{ $selectedBatchId }}">
                        <select name="trend_batch_id" onchange="this.form.submit()" class="text-sm rounded-md border-gray-300">
                            <option value="" @selected($trendMode === 'overall')>Overall (historical pattern)</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch->id }}" @selected($selectedTrendBatchId == $batch->id)>{{ $batch->batch_code }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <p class="text-sm text-gray-500 mb-4">
                    @if ($trendMode === 'overall')
                        Defect count for each hour of the day, across all recorded history.
                    @else
                        Defect count for each hour of the day, for the selected batch only.
                    @endif
                </p>
                @if ($trendValues->sum() === 0)
                    <p class="text-gray-500">No defect data recorded yet.</p>
                @else
                    <div class="w-full">
                        <canvas id="defectTrendChart"
                            data-labels="{{ $trendLabels->toJson() }}"
                            data-values="{{ $trendValues->toJson() }}"
                            data-spikes="{{ $spikeHours->toJson() }}"></canvas>
                    </div>
                    @if ($trendMode === 'overall' && $hourlySpikes->isNotEmpty())
                        <div class="mt-4 border-t pt-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Historical spike hours</h4>
                            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach ($hourlySpikes as $spike)
                                    <li class="flex items-center gap-2 text-sm bg-red-50 border border-red-200 rounded-md px-3 py-2">
                                        <span class="font-semibold text-red-700">{{ $spike['hour'] }}</span>
                                        <span class="text-gray-600">{{ $spike['total'] }} defects</span>
                                        @if ($spike['dominant_type'])
                                            <span class="ml-auto text-xs text-gray-500 capitalize">mostly {{ $spike['dominant_type'] }}</span>
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

            {{-- Batch Pass/Fail Rates --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Batch Pass / Rework / Reject Rates</h3>
                @if ($batchStats->isEmpty())
                    <p class="text-gray-500">No batch data recorded yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                                    <th class="py-2 pr-4">Batch</th>
                                    <th class="py-2 pr-4">Shift</th>
                                    <th class="py-2 pr-4">Stage</th>
                                    <th class="py-2 pr-4">Total</th>
                                    <th class="py-2 pr-4">Pass</th>
                                    <th class="py-2 pr-4">Rework</th>
                                    <th class="py-2 pr-4">Reject</th>
                                    <th class="py-2 pr-4">Pass Rate</th>
                                    <th class="py-2 pr-4">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($batchStats as $batch)
                                    <tr>
                                        <td class="py-2 pr-4 font-medium text-gray-900">{{ $batch['batch_code'] }}</td>
                                        <td class="py-2 pr-4 uppercase">{{ $batch['shift'] ?? '—' }}</td>
                                        <td class="py-2 pr-4 capitalize">{{ str_replace('_', ' ', $batch['stage']) }}</td>
                                        <td class="py-2 pr-4">{{ $batch['total'] }}</td>
                                        <td class="py-2 pr-4 text-green-600">{{ $batch['pass'] }}</td>
                                        <td class="py-2 pr-4 text-yellow-600">{{ $batch['rework'] }}</td>
                                        <td class="py-2 pr-4 text-red-600">{{ $batch['reject'] }}</td>
                                        <td class="py-2 pr-4">
                                            @if ($batch['pass_rate'] !== null)
                                                <span class="font-semibold @if($batch['pass_rate'] >= 90) text-green-600 @elseif($batch['pass_rate'] >= 75) text-yellow-600 @else text-red-600 @endif">
                                                    {{ $batch['pass_rate'] }}%
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="py-2 pr-4">
                                            <a href="{{ route('manager.batches.edit', $batch['id']) }}" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">Edit</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Shift & Checkpoint Comparison --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Shift Comparison</h3>
                    @if ($shiftStats->isEmpty())
                        <p class="text-gray-500 text-sm">No shift data available.</p>
                    @else
                        <ul class="space-y-3">
                            @foreach ($shiftStats as $shift => $stats)
                                <li class="border rounded-lg p-4">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="font-semibold text-gray-800">{{ $shift }} Shift</span>
                                        <span class="text-sm font-medium @if(($stats['pass_rate'] ?? 0) >= 90) text-green-600 @elseif(($stats['pass_rate'] ?? 0) >= 75) text-yellow-600 @else text-red-600 @endif">
                                            {{ $stats['pass_rate'] ?? '—' }}% pass
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div class="h-2 rounded-full @if(($stats['pass_rate'] ?? 0) >= 90) bg-green-500 @elseif(($stats['pass_rate'] ?? 0) >= 75) bg-yellow-500 @else bg-red-500 @endif"
                                            style="width: {{ $stats['pass_rate'] ?? 0 }}%"></div>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">{{ $stats['total'] }} inspections</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Checkpoint Comparison</h3>
                    @if ($checkpointStats->isEmpty())
                        <p class="text-gray-500 text-sm">No checkpoint data available.</p>
                    @else
                        <ul class="space-y-3">
                            @foreach ($checkpointStats as $checkpoint => $stats)
                                <li class="border rounded-lg p-4">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="font-semibold text-gray-800 capitalize">{{ str_replace('_', ' ', $checkpoint) }}</span>
                                        <span class="text-sm font-medium @if(($stats['pass_rate'] ?? 0) >= 90) text-green-600 @elseif(($stats['pass_rate'] ?? 0) >= 75) text-yellow-600 @else text-red-600 @endif">
                                            {{ $stats['pass_rate'] ?? '—' }}% pass
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div class="h-2 rounded-full @if(($stats['pass_rate'] ?? 0) >= 90) bg-green-500 @elseif(($stats['pass_rate'] ?? 0) >= 75) bg-yellow-500 @else bg-red-500 @endif"
                                            style="width: {{ $stats['pass_rate'] ?? 0 }}%"></div>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">{{ $stats['total'] }} inspections</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            {{-- Inspector Performance --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Inspector Performance</h3>
                @if ($inspectorStats->isEmpty())
                    <p class="text-gray-500 text-sm">No inspector activity recorded yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                                    <th class="py-2 pr-4">Inspector</th>
                                    <th class="py-2 pr-4">Total Reviewed</th>
                                    <th class="py-2 pr-4">Passed</th>
                                    <th class="py-2 pr-4">AI Overrides</th>
                                    <th class="py-2 pr-4">Override Rate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($inspectorStats as $inspector)
                                    <tr>
                                        <td class="py-2 pr-4 font-medium text-gray-900">{{ $inspector['name'] }}</td>
                                        <td class="py-2 pr-4">{{ $inspector['total'] }}</td>
                                        <td class="py-2 pr-4 text-green-600">{{ $inspector['pass'] }}</td>
                                        <td class="py-2 pr-4">{{ $inspector['overrides'] }}</td>
                                        <td class="py-2 pr-4">
                                            <span class="font-medium @if(($inspector['override_rate'] ?? 0) > 30) text-red-600 @elseif(($inspector['override_rate'] ?? 0) > 15) text-yellow-600 @else text-gray-700 @endif">
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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">AI Override Analytics</h3>
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="border rounded-lg p-4">
                        <div class="text-sm text-gray-500">Reviewed Inspections</div>
                        <div class="text-2xl font-semibold text-gray-900">{{ $reviewedCount }}</div>
                    </div>
                    <div class="border rounded-lg p-4">
                        <div class="text-sm text-gray-500">AI Overridden</div>
                        <div class="text-2xl font-semibold text-gray-900">{{ $overriddenCount }}</div>
                    </div>
                    <div class="border rounded-lg p-4">
                        <div class="text-sm text-gray-500">Override Rate</div>
                        <div class="text-2xl font-semibold text-gray-900">{{ $overrideRate }}%</div>
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
                    <p class="text-gray-500">No dismissed defect flags recorded yet.</p>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
