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
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                                <th class="py-2 pr-4">Batch</th>
                                <th class="py-2 pr-4">Checkpoint</th>
                                <th class="py-2 pr-4">Captured</th>
                                <th class="py-2 pr-4">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($pendingInspections as $inspection)
                                <tr>
                                    <td class="py-2 pr-4">{{ $inspection->batch?->batch_code ?? '—' }}</td>
                                    <td class="py-2 pr-4 capitalize">{{ $inspection->checkpoint }}</td>
                                    <td class="py-2 pr-4">{{ $inspection->created_at->diffForHumans() }}</td>
                                    <td class="py-2 pr-4">
                                        <span class="text-indigo-600">Review</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
