<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Shoe Constructor Dashboard') }}
        </h2>
    </x-slot>

    @php $tab = request('tab', 'active'); @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Tab Navigation --}}
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Tabs">
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'active']) }}"
                        class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition {{ $tab === 'active' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        {{ __('Rework Notifications') }}
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'past']) }}"
                        class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition {{ $tab === 'past' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        {{ __('Past Reworks') }}
                    </a>
                </nav>
            </div>

            @if ($tab === 'active')
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
                                        <span class="text-xs text-gray-500 capitalize">{{ str_replace('_', ' ', $inspection->checkpoint) }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500">Inspection #{{ $inspection->id }}</p>
                                    @if ($inspection->rework_station)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 capitalize">
                                            {{ str_replace('_', ' ', $inspection->rework_station) }}
                                        </span>
                                    @endif
                                    <p class="text-sm text-gray-600 capitalize">
                                        {{ $inspection->defects->pluck('defect_type')->unique()->map(fn ($t) => str_replace('_', ' ', $t))->join(', ') }}
                                    </p>
                                    <p class="text-xs text-gray-500">Flagged {{ $inspection->created_at->diffForHumans() }}</p>
                                    <div class="flex items-center justify-between pt-1">
                                        <a href="{{ route('constructor.inspections.show', $inspection) }}" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium hover:underline">View</a>
                                    </div>
                                    @include('dashboards.partials.rework-status-form', ['inspection' => $inspection])
                                </div>
                            @endforeach
                        </div>

                        {{-- Table layout: medium screens and up --}}
                        <div class="hidden md:block overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        <th class="px-6 py-3 whitespace-nowrap">Inspection #</th>
                                        <th class="px-6 py-3 whitespace-nowrap">Batch</th>
                                        <th class="px-6 py-3 whitespace-nowrap">Checkpoint</th>
                                        <th class="px-6 py-3 whitespace-nowrap">Station</th>
                                        <th class="px-6 py-3">Defects</th>
                                        <th class="px-6 py-3 whitespace-nowrap">Flagged</th>
                                        <th class="px-6 py-3 whitespace-nowrap">Image</th>
                                        <th class="px-6 py-3 whitespace-nowrap">Progress</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($reworkInspections as $inspection)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $inspection->id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $inspection->batch?->batch_code ?? '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $inspection->checkpoint) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 capitalize">{{ $inspection->rework_station ? str_replace('_', ' ', $inspection->rework_station) : '—' }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-600 capitalize">
                                                {{ $inspection->defects->pluck('defect_type')->unique()->map(fn ($t) => str_replace('_', ' ', $t))->join(', ') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $inspection->created_at->diffForHumans() }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <a href="{{ route('constructor.inspections.show', $inspection) }}" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">View</a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @include('dashboards.partials.rework-status-form', ['inspection' => $inspection])
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @else
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
                                        <span class="text-xs text-gray-500 capitalize">{{ str_replace('_', ' ', $inspection->checkpoint) }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500">Inspection #{{ $inspection->id }}</p>
                                    @if ($inspection->rework_station)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 capitalize">
                                            {{ str_replace('_', ' ', $inspection->rework_station) }}
                                        </span>
                                    @endif
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
                                    <p class="text-xs text-gray-500 mt-1">{{ __('Marked complete by mistake?') }}</p>
                                    @include('dashboards.partials.rework-status-form', ['inspection' => $inspection])
                                </div>
                            @endforeach
                        </div>

                        {{-- Table layout: medium screens and up --}}
                        <div class="hidden md:block overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        <th class="px-6 py-3 whitespace-nowrap">Inspection #</th>
                                        <th class="px-6 py-3 whitespace-nowrap">Batch</th>
                                        <th class="px-6 py-3 whitespace-nowrap">Checkpoint</th>
                                        <th class="px-6 py-3 whitespace-nowrap">Station</th>
                                        <th class="px-6 py-3">Defects</th>
                                        <th class="px-6 py-3 whitespace-nowrap">Flagged</th>
                                        <th class="px-6 py-3 whitespace-nowrap">Image</th>
                                        <th class="px-6 py-3 whitespace-nowrap">Resolved</th>
                                        <th class="px-6 py-3 whitespace-nowrap">Progress</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($resolvedReworks as $inspection)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $inspection->id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $inspection->batch?->batch_code ?? '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $inspection->checkpoint) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 capitalize">{{ $inspection->rework_station ? str_replace('_', ' ', $inspection->rework_station) : '—' }}</td>
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
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @include('dashboards.partials.rework-status-form', ['inspection' => $inspection])
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
