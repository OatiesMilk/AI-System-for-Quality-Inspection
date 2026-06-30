<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inspection #'.$inspection->id) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <a href="{{ route('dashboard.inspector') }}" class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-900 hover:underline">
                &larr; Back to dashboard
            </a>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-3 gap-4 text-sm mb-6">
                    <div>
                        <dt class="text-gray-500">Batch</dt>
                        <dd class="font-medium">{{ $inspection->batch?->batch_code ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Checkpoint</dt>
                        <dd class="font-medium capitalize">{{ $inspection->checkpoint }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Captured</dt>
                        <dd class="font-medium">{{ $inspection->created_at->format('M j, Y g:i A') }}</dd>
                    </div>
                </dl>

                @include('dashboards.partials.inspection-image', ['inspection' => $inspection])

                <form method="POST" action="{{ route('inspector.inspections.update', $inspection) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">AI-Detected Defects</h3>
                        @if ($inspection->defects->isEmpty())
                            <p class="text-gray-500">No defects detected.</p>
                        @else
                            <div class="overflow-hidden border border-gray-200 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            <th class="px-6 py-3">Type</th>
                                            <th class="px-6 py-3">Confidence</th>
                                            <th class="px-6 py-3">Confirm Defect</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($inspection->defects as $defect)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $defect->defect_type) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ number_format($defect->confidence_score * 100, 1) }}%</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <label class="inline-flex items-center gap-2">
                                                        <input type="checkbox" name="defects[{{ $defect->id }}]" value="1" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                        <span class="text-xs text-gray-500">Uncheck to dismiss as a false positive</span>
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
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Final Decision</h3>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" name="action" value="pass" required> Pass
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" name="action" value="rework"> Rework
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" name="action" value="reject"> Reject
                            </label>
                        </div>
                    </div>

                    <x-primary-button>Submit Validation</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
