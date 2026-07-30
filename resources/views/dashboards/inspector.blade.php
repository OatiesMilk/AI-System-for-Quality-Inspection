<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Quality Inspector Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Pending Inspections</h3>

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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $inspection->batch?->batch_code ?? '-' }}</td>
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

                <form method="GET" action="{{ route('dashboard.inspector') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
                    <div>
                        <x-input-label for="decision" :value="__('Decision')" class="text-xs" />
                        <select id="decision" name="decision" class="block mt-1 w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All decisions') }}</option>
                            <option value="pass" {{ request('decision') === 'pass' ? 'selected' : '' }}>{{ __('Pass') }}</option>
                            <option value="rework" {{ request('decision') === 'rework' ? 'selected' : '' }}>{{ __('Rework') }}</option>
                            <option value="reject" {{ request('decision') === 'reject' ? 'selected' : '' }}>{{ __('Reject') }}</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="ai_override" :value="__('AI Override')" class="text-xs" />
                        <select id="ai_override" name="ai_override" class="block mt-1 w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('Any') }}</option>
                            <option value="1" {{ request('ai_override') === '1' ? 'selected' : '' }}>{{ __('Yes') }}</option>
                            <option value="0" {{ request('ai_override') === '0' ? 'selected' : '' }}>{{ __('No') }}</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="date_from" :value="__('From')" class="text-xs" />
                        <x-text-input id="date_from" class="block mt-1 w-full text-sm" type="date" name="date_from" :value="request('date_from')" />
                    </div>

                    <div>
                        <x-input-label for="date_to" :value="__('To')" class="text-xs" />
                        <x-text-input id="date_to" class="block mt-1 w-full text-sm" type="date" name="date_to" :value="request('date_to')" />
                    </div>

                    <div class="flex items-end justify-end gap-2">
                        <x-primary-button>{{ __('Filter') }}</x-primary-button>
                        @if (request()->anyFilled(['decision', 'ai_override', 'date_from', 'date_to']))
                            <a href="{{ route('dashboard.inspector') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </form>

                @if ($reviewedInspections->isEmpty())
                    <p class="text-gray-500">No inspections found for the selected filters.</p>
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $inspection->batch?->batch_code ?? '-' }}</td>
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $inspection->inspector?->name ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $inspection->inspected_at?->format('M j, Y g:i A') ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('inspector.inspections.show', $inspection) }}" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $reviewedInspections->links() }}
                    </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Resolved by Constructors</h3>

                <form method="GET" action="{{ route('dashboard.inspector') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-4">
                    <div>
                        <x-input-label for="resolved_by" :value="__('Constructor')" class="text-xs" />
                        <select id="resolved_by" name="resolved_by" class="block mt-1 w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All constructors') }}</option>
                            @foreach ($constructors as $constructor)
                                <option value="{{ $constructor->id }}" {{ (string) request('resolved_by') === (string) $constructor->id ? 'selected' : '' }}>{{ $constructor->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="resolved_date_from" :value="__('From')" class="text-xs" />
                        <x-text-input id="resolved_date_from" class="block mt-1 w-full text-sm" type="date" name="resolved_date_from" :value="request('resolved_date_from')" />
                    </div>

                    <div>
                        <x-input-label for="resolved_date_to" :value="__('To')" class="text-xs" />
                        <x-text-input id="resolved_date_to" class="block mt-1 w-full text-sm" type="date" name="resolved_date_to" :value="request('resolved_date_to')" />
                    </div>

                    <div class="flex items-end justify-end gap-2">
                        <x-primary-button>{{ __('Filter') }}</x-primary-button>
                        @if (request()->anyFilled(['resolved_by', 'resolved_date_from', 'resolved_date_to']))
                            <a href="{{ route('dashboard.inspector') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </form>

                @if ($resolvedReworks->isEmpty())
                    <p class="text-gray-500">No resolved reworks found for the selected filters.</p>
                @else
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-3">Inspection #</th>
                                    <th class="px-6 py-3">Batch</th>
                                    <th class="px-6 py-3">Checkpoint</th>
                                    <th class="px-6 py-3">Resolved By</th>
                                    <th class="px-6 py-3">Resolved At</th>
                                    <th class="px-6 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($resolvedReworks as $inspection)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $inspection->id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $inspection->batch?->batch_code ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 capitalize">{{ $inspection->checkpoint }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $inspection->resolvedBy?->name ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $inspection->reworked_at?->format('M j, Y g:i A') ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('inspector.inspections.show', $inspection) }}" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $resolvedReworks->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
