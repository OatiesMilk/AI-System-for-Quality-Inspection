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

            <div class="flex justify-end">
                <a href="{{ route('manager.users.create') }}" class="inline-block px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    {{ __('Create User Account') }}
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Defect Counts by Type (Descriptive Analytics)</h3>

                @if ($defectCounts->isEmpty())
                    <p class="text-gray-500">No defect data recorded yet.</p>
                @else
                    <ul class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach ($defectCounts as $type => $total)
                            <li class="border rounded-lg p-4">
                                <div class="text-sm text-gray-500 capitalize">{{ str_replace('_', ' ', $type) }}</div>
                                <div class="text-2xl font-semibold text-gray-900">{{ $total }}</div>
                            </li>
                        @endforeach
                    </ul>
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
                                <th class="py-2 pr-4">Stage</th>
                                <th class="py-2 pr-4">Volume</th>
                                <th class="py-2 pr-4">Inspections</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($recentBatches as $batch)
                                <tr>
                                    <td class="py-2 pr-4">{{ $batch->batch_code }}</td>
                                    <td class="py-2 pr-4 capitalize">{{ $batch->manufacturing_stage }}</td>
                                    <td class="py-2 pr-4">{{ $batch->volume }}</td>
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
