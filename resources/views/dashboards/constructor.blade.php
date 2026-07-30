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
                    {{-- Card layout: phones and small tablets --}}
                    <div class="space-y-3 md:hidden">
                        @foreach ($reworkInspections as $inspection)
                            <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-900">{{ $inspection->batch?->batch_code ?? '-' }}</span>
                                    <span class="text-xs text-gray-500 capitalize">{{ $inspection->checkpoint }}</span>
                                </div>
                                <p class="text-xs text-gray-500">Inspection #{{ $inspection->id }}</p>
                                <p class="text-sm text-gray-600 capitalize">
                                    {{ $inspection->defects->pluck('defect_type')->unique()->map(fn ($t) => str_replace('_', ' ', $t))->join(', ') }}
                                </p>
                                <p class="text-xs text-gray-500">Flagged {{ $inspection->created_at->diffForHumans() }}</p>
                                <div class="flex items-center gap-4 pt-1">
                                    <a href="{{ route('constructor.inspections.show', $inspection) }}" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium hover:underline">View</a>
                                    <button
                                        type="button"
                                        x-data=""
                                        x-on:click="$dispatch('open-modal', 'confirm-resolve-mobile-{{ $inspection->id }}')"
                                        class="flex-1 px-3 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700"
                                    >
                                        {{ __('Mark Resolved') }}
                                    </button>
                                </div>

                                <x-modal name="confirm-resolve-mobile-{{ $inspection->id }}" focusable>
                                    <form method="POST" action="{{ route('constructor.inspections.resolve', $inspection) }}" class="p-6">
                                        @csrf
                                        @method('PATCH')

                                        <h2 class="text-lg font-medium text-gray-900">
                                            {{ __('Mark this rework as resolved?') }}
                                        </h2>

                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ __('Batch :batch — this confirms the flagged defects have been fixed.', ['batch' => $inspection->batch?->batch_code ?? '-']) }}
                                        </p>

                                        <div class="mt-6 flex justify-end gap-3">
                                            <x-secondary-button x-on:click="$dispatch('close')">
                                                {{ __('Cancel') }}
                                            </x-secondary-button>

                                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                                                {{ __('Mark Resolved') }}
                                            </button>
                                        </div>
                                    </form>
                                </x-modal>
                            </div>
                        @endforeach
                    </div>

                    {{-- Table layout: medium screens and up --}}
                    <div class="hidden md:block overflow-hidden border border-gray-200 rounded-lg">
                        <table class="w-full table-fixed divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-3 w-[10%]">Inspection #</th>
                                    <th class="px-6 py-3 w-[15%]">Batch</th>
                                    <th class="px-6 py-3 w-[10%]">Checkpoint</th>
                                    <th class="px-6 py-3 w-[24%]">Defects</th>
                                    <th class="px-6 py-3 w-[14%]">Flagged</th>
                                    <th class="px-6 py-3 w-[10%]">Image</th>
                                    <th class="px-6 py-3 w-[22%]">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($reworkInspections as $inspection)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $inspection->id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $inspection->batch?->batch_code ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 capitalize">{{ $inspection->checkpoint }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600 capitalize">
                                            {{ $inspection->defects->pluck('defect_type')->unique()->map(fn ($t) => str_replace('_', ' ', $t))->join(', ') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $inspection->created_at->diffForHumans() }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('constructor.inspections.show', $inspection) }}" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">View</a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <button
                                                type="button"
                                                x-data=""
                                                x-on:click="$dispatch('open-modal', 'confirm-resolve-desktop-{{ $inspection->id }}')"
                                                class="inline-block px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700"
                                            >
                                                {{ __('Mark Resolved') }}
                                            </button>

                                            <x-modal name="confirm-resolve-desktop-{{ $inspection->id }}" focusable>
                                                <form method="POST" action="{{ route('constructor.inspections.resolve', $inspection) }}" class="p-6">
                                                    @csrf
                                                    @method('PATCH')

                                                    <h2 class="text-lg font-medium text-gray-900">
                                                        {{ __('Mark this rework as resolved?') }}
                                                    </h2>

                                                    <p class="mt-1 text-sm text-gray-600">
                                                        {{ __('Batch :batch - this confirms the flagged defects have been fixed.', ['batch' => $inspection->batch?->batch_code ?? '-']) }}
                                                    </p>

                                                    <div class="mt-6 flex justify-end gap-3">
                                                        <x-secondary-button x-on:click="$dispatch('close')">
                                                            {{ __('Cancel') }}
                                                        </x-secondary-button>

                                                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                                                            {{ __('Mark Resolved') }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </x-modal>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Past Reworks</h3>

                @if ($resolvedReworks->isEmpty())
                    <p class="text-gray-500">No resolved rework items yet.</p>
                @else
                    {{-- Card layout: phones and small tablets --}}
                    <div class="space-y-3 md:hidden">
                        @foreach ($resolvedReworks as $inspection)
                            <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-900">{{ $inspection->batch?->batch_code ?? '-' }}</span>
                                    <span class="text-xs text-gray-500 capitalize">{{ $inspection->checkpoint }}</span>
                                </div>
                                <p class="text-xs text-gray-500">Inspection #{{ $inspection->id }}</p>
                                <p class="text-sm text-gray-600 capitalize">
                                    {{ $inspection->defects->pluck('defect_type')->unique()->map(fn ($t) => str_replace('_', ' ', $t))->join(', ') }}
                                </p>
                                <p class="text-xs text-gray-500">Flagged {{ $inspection->created_at->diffForHumans() }}</p>
                                <div class="flex items-center justify-between pt-1">
                                    <a href="{{ route('constructor.inspections.show', $inspection) }}" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium hover:underline">View</a>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">
                                        Resolved {{ $inspection->reworked_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Table layout: medium screens and up --}}
                    <div class="hidden md:block overflow-hidden border border-gray-200 rounded-lg">
                        <table class="w-full table-fixed divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-3 w-[8%]">Inspection #</th>
                                    <th class="px-6 py-3 w-[12%]">Batch</th>
                                    <th class="px-6 py-3 w-[10%]">Checkpoint</th>
                                    <th class="px-6 py-3 w-[24%]">Defects</th>
                                    <th class="px-6 py-3 w-[14%]">Flagged</th>
                                    <th class="px-6 py-3 w-[10%]">Image</th>
                                    <th class="px-6 py-3 w-[22%]">Resolved</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($resolvedReworks as $inspection)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $inspection->id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $inspection->batch?->batch_code ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 capitalize">{{ $inspection->checkpoint }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600 capitalize">
                                            {{ $inspection->defects->pluck('defect_type')->unique()->map(fn ($t) => str_replace('_', ' ', $t))->join(', ') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $inspection->created_at->diffForHumans() }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('constructor.inspections.show', $inspection) }}" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">View</a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">
                                                {{ $inspection->reworked_at->diffForHumans() }}
                                            </span>
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
