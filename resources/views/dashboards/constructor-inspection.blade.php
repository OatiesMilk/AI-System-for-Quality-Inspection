<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Inspection #'.$inspection->id.' - Defect Locations') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-sm mb-6">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Batch</dt>
                        <dd class="font-medium">{{ $inspection->batch?->batch_code ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Checkpoint</dt>
                        <dd class="font-medium capitalize">{{ str_replace('_', ' ', $inspection->checkpoint) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Station</dt>
                        <dd class="font-medium capitalize">{{ $inspection->rework_station ? str_replace('_', ' ', $inspection->rework_station) : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Flagged</dt>
                        <dd class="font-medium">{{ $inspection->created_at->format('M j, Y g:i A') }}</dd>
                    </div>
                </dl>

                @if ($inspection->action === 'rework')
                    <div class="flex items-center gap-3 mb-6">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Progress:</span>
                        @include('dashboards.partials.rework-status-form', ['inspection' => $inspection])
                    </div>
                @endif

                @include('dashboards.partials.inspection-image', ['inspection' => $inspection])

                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Flagged Defects</h3>
                    @if ($inspection->defects->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400">No defects recorded for this inspection.</p>
                    @else
                        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <th class="px-6 py-3 whitespace-nowrap">Type</th>
                                        <th class="px-6 py-3 whitespace-nowrap">Confidence</th>
                                        <th class="px-6 py-3 whitespace-nowrap">Inspector Confirmed</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($inspection->defects as $defect)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white capitalize">{{ str_replace('_', ' ', $defect->defect_type) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ number_format($defect->confidence_score * 100, 1) }}%</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $defect->confirmed === null ? 'Pending' : ($defect->confirmed ? 'Yes' : 'No') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="mt-6">
                    <a href="{{ route('dashboard.constructor') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 hover:underline">&larr; Back to dashboard</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
