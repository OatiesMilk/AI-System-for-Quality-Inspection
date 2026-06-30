<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Quality Inspector Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Pending Inspections (HITL Validation)</h3>

                @if ($pendingInspections->isEmpty())
                    <p class="text-gray-500">No inspections awaiting validation.</p>
                @else
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-3">Batch</th>
                                    <th class="px-6 py-3">Checkpoint</th>
                                    <th class="px-6 py-3">Captured</th>
                                    <th class="px-6 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($pendingInspections as $inspection)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $inspection->batch?->batch_code ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 capitalize">{{ $inspection->checkpoint }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $inspection->created_at->format('M j, Y g:i A') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('inspector.inspections.show', $inspection) }}" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">Review</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Reviewed Inspections</h3>

                @if ($reviewedInspections->isEmpty())
                    <p class="text-gray-500">No inspections have been reviewed yet.</p>
                @else
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-3">Batch</th>
                                    <th class="px-6 py-3">Checkpoint</th>
                                    <th class="px-6 py-3">Decision</th>
                                    <th class="px-6 py-3">AI Override</th>
                                    <th class="px-6 py-3">Inspector</th>
                                    <th class="px-6 py-3">Reviewed</th>
                                    <th class="px-6 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($reviewedInspections as $inspection)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $inspection->batch?->batch_code ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 capitalize">{{ $inspection->checkpoint }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span @class([
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize',
                                                'bg-green-50 text-green-700' => $inspection->action === 'pass',
                                                'bg-amber-50 text-amber-700' => $inspection->action === 'rework',
                                                'bg-red-50 text-red-700' => $inspection->action === 'reject',
                                            ])>
                                                {{ $inspection->action }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $inspection->ai_override ? 'Yes' : 'No' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $inspection->inspector?->name ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $inspection->inspected_at?->format('M j, Y g:i A') ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('inspector.inspections.show', $inspection) }}" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
