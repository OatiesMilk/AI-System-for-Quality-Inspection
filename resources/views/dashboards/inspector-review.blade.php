<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Inspection #'.$inspection->id) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <a href="{{ route('dashboard.inspector') }}" class="inline-flex items-center text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 hover:underline">
                &larr; Back to dashboard
            </a>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-3 gap-4 text-sm mb-6">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Batch</dt>
                        <dd class="font-medium">{{ $inspection->batch?->batch_code ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Checkpoint</dt>
                        <dd class="font-medium capitalize">{{ str_replace('_', ' ', $inspection->checkpoint) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Captured</dt>
                        <dd class="font-medium">{{ $inspection->created_at->format('M j, Y g:i A') }}</dd>
                    </div>
                </dl>

                @include('dashboards.partials.inspection-image', ['inspection' => $inspection])

                <form method="POST" action="{{ route('inspector.inspections.update', $inspection) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">AI-Detected Defects</h3>
                        @if ($inspection->defects->isEmpty())
                            <p class="text-gray-500 dark:text-gray-400">No defects detected.</p>
                        @else
                            <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                                        <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            <th class="px-6 py-3">Type</th>
                                            <th class="px-6 py-3">Confidence</th>
                                            <th class="px-6 py-3">Confirm Defect</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($inspection->defects as $defect)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white capitalize">{{ str_replace('_', ' ', $defect->defect_type) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ number_format($defect->confidence_score * 100, 1) }}%</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <label class="inline-flex items-center gap-2">
                                                        <input type="checkbox" name="defects[{{ $defect->id }}]" value="1" {{ $defect->confirmed ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-indigo-600 dark:text-indigo-400 focus:ring-indigo-500">
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">Uncheck to dismiss as a false positive</span>
                                                    </label>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Final Decision</h3>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" name="action" value="pass" required onchange="document.getElementById('rework-station-field').classList.add('hidden')" {{ $inspection->action === 'pass' ? 'checked' : '' }}> Pass
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" name="action" value="rework" onchange="document.getElementById('rework-station-field').classList.remove('hidden')" {{ $inspection->action === 'rework' ? 'checked' : '' }}> Rework
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" name="action" value="reject" onchange="document.getElementById('rework-station-field').classList.add('hidden')" {{ $inspection->action === 'reject' ? 'checked' : '' }}> Reject
                            </label>
                        </div>
                    </div>

                    <div id="rework-station-field" class="{{ $inspection->action === 'rework' ? '' : 'hidden' }}">
                        <x-input-label for="rework_station" :value="__('Responsible Station')" />
                        <select id="rework_station" name="rework_station"
                            class="block mt-1 w-full max-w-xs border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="" disabled {{ $inspection->rework_station ? '' : 'selected' }}>{{ __('Select a station') }}</option>
                            <option value="cutting" {{ $inspection->rework_station === 'cutting' ? 'selected' : '' }}>{{ __('Cutting') }}</option>
                            <option value="marking" {{ $inspection->rework_station === 'marking' ? 'selected' : '' }}>{{ __('Marking') }}</option>
                            <option value="skiving" {{ $inspection->rework_station === 'skiving' ? 'selected' : '' }}>{{ __('Skiving') }}</option>
                            <option value="upper_making" {{ $inspection->rework_station === 'upper_making' ? 'selected' : '' }}>{{ __('Upper Making') }}</option>
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Which station is responsible for fixing this.') }}</p>
                        <x-input-error :messages="$errors->get('rework_station')" class="mt-2" />
                    </div>

                    <x-primary-button>Submit Validation</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
