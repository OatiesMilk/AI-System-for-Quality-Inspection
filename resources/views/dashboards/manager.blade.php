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
                <a href="{{ route('manager.batches.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V4z" clip-rule="evenodd"/>
                    </svg>
                    {{ __('Create Batch') }}
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Defect Counts by Type (Descriptive Analytics)</h3>

                @if ($defectCounts->isEmpty())
                    <p class="text-gray-500">No defect data recorded yet.</p>
                @else
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-center">
                        <div class="max-w-xs mx-auto">
                            <canvas id="defectTypeChart"
                                data-labels="{{ $defectCounts->keys()->map(fn ($type) => str_replace('_', ' ', $type))->toJson() }}"
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
                                        datasets: [{
                                            data: JSON.parse(canvas.dataset.values),
                                            backgroundColor: ['#6366f1', '#f59e0b', '#ef4444', '#10b981', '#3b82f6', '#a855f7'],
                                        }],
                                    },
                                    options: {
                                        plugins: {
                                            legend: { position: 'bottom' },
                                        },
                                    },
                                });
                            });
                        </script>
                    @endpush
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Batches</h3>

                @if ($recentBatches->isEmpty())
                    <p class="text-gray-500">No batches recorded yet.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                                <th class="py-2 pr-4">Batch Code</th>
                                <th class="py-2 pr-4">Shift</th>
                                <th class="py-2 pr-4">Stage</th>
                                <th class="py-2 pr-4">Inspections</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($recentBatches as $batch)
                                <tr>
                                    <td class="py-2 pr-4">{{ $batch->batch_code }}</td>
                                    <td class="py-2 pr-4 uppercase">{{ $batch->shift ?? '—' }}</td>
                                    <td class="py-2 pr-4 capitalize">{{ $batch->manufacturing_stage }}</td>
                                    <td class="py-2 pr-4">{{ $batch->inspections->count() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
