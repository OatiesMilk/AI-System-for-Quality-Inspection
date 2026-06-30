<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inspection #'.$inspection->id.' - Defect Locations') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
                        <dt class="text-gray-500">Flagged</dt>
                        <dd class="font-medium">{{ $inspection->created_at->format('M j, Y g:i A') }}</dd>
                    </div>
                </dl>

                @include('dashboards.partials.inspection-image', ['inspection' => $inspection])

                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Flagged Defects</h3>
                    @if ($inspection->defects->isEmpty())
                        <p class="text-gray-500">No defects recorded for this inspection.</p>
                    @else
                        <div class="overflow-hidden border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        <th class="px-6 py-3">Type</th>
                                        <th class="px-6 py-3">Confidence</th>
                                        <th class="px-6 py-3">Inspector Confirmed</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($inspection->defects as $defect)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $defect->defect_type) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ number_format($defect->confidence_score * 100, 1) }}%</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $defect->confirmed === null ? 'Pending' : ($defect->confirmed ? 'Yes' : 'No') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="mt-6">
                    <a href="{{ route('dashboard.constructor') }}" class="text-sm text-indigo-600 hover:text-indigo-900 hover:underline">&larr; Back to dashboard</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
