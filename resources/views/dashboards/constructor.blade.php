<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Shoe Constructor Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Rework Notifications</h3>

                @if ($reworkInspections->isEmpty())
                    <p class="text-gray-500">No rework items assigned to you right now.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                                <th class="py-2 pr-4">Batch</th>
                                <th class="py-2 pr-4">Checkpoint</th>
                                <th class="py-2 pr-4">Defects</th>
                                <th class="py-2 pr-4">Flagged</th>
                                <th class="py-2 pr-4">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($reworkInspections as $inspection)
                                <tr>
                                    <td class="py-2 pr-4">{{ $inspection->batch?->batch_code ?? '—' }}</td>
                                    <td class="py-2 pr-4 capitalize">{{ $inspection->checkpoint }}</td>
                                    <td class="py-2 pr-4">
                                        {{ $inspection->defects->pluck('defect_type')->map(fn ($t) => str_replace('_', ' ', $t))->join(', ') }}
                                    </td>
                                    <td class="py-2 pr-4">{{ $inspection->created_at->diffForHumans() }}</td>
                                    <td class="py-2 pr-4">
                                        <form method="POST" action="{{ route('constructor.inspections.resolve', $inspection) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-block px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                                                {{ __('Mark Resolved') }}
                                            </button>
                                        </form>
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
