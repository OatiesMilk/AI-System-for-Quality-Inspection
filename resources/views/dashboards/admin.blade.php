<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('System Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
                    {{ session('status') }}
                </div>
            @endif

            {{-- System Activity Summary --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-4 border-l-4 border-indigo-500">
                    <div class="text-xs text-gray-500">Total Inspections</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $activitySummary['total_inspections'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4 border-l-4 border-green-500">
                    <div class="text-xs text-gray-500">Today's Inspections</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $activitySummary['today_inspections'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4 border-l-4 border-blue-500">
                    <div class="text-xs text-gray-500">Total Batches</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $activitySummary['total_batches'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4 border-l-4 border-red-500">
                    <div class="text-xs text-gray-500">Total Defects</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $activitySummary['total_defects'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4 border-l-4 border-yellow-500">
                    <div class="text-xs text-gray-500">Pending Reworks</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $activitySummary['pending_reworks'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4 border-l-4 border-purple-500">
                    <div class="text-xs text-gray-500">Total Users</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $activitySummary['total_users'] }}</div>
                </div>
            </div>

            {{-- Inspection Activity Trend & Audit Event Distribution --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Inspection Activity — Last 7 Days</h3>
                    <canvas id="activityTrendChart"
                        data-labels="{{ $activityLabels->toJson() }}"
                        data-values="{{ $activityValues->toJson() }}"></canvas>
                    @push('scripts')
                        <script>
                            document.addEventListener('DOMContentLoaded', () => {
                                const canvas = document.getElementById('activityTrendChart');
                                if (!canvas || !window.Chart) return;
                                new window.Chart(canvas, {
                                    type: 'bar',
                                    data: {
                                        labels: JSON.parse(canvas.dataset.labels),
                                        datasets: [{
                                            label: 'Inspections',
                                            data: JSON.parse(canvas.dataset.values),
                                            backgroundColor: '#6366f1',
                                        }],
                                    },
                                    options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } },
                                });
                            });
                        </script>
                    @endpush
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Audit Event Distribution</h3>
                    @if ($auditEventCounts->isEmpty())
                        <p class="text-gray-500 text-sm">No audit events recorded yet.</p>
                    @else
                        <canvas id="auditEventChart"
                            data-labels="{{ $auditEventCounts->keys()->toJson() }}"
                            data-values="{{ $auditEventCounts->values()->toJson() }}"></canvas>
                        @push('scripts')
                            <script>
                                document.addEventListener('DOMContentLoaded', () => {
                                    const canvas = document.getElementById('auditEventChart');
                                    if (!canvas || !window.Chart) return;
                                    new window.Chart(canvas, {
                                        type: 'doughnut',
                                        data: {
                                            labels: JSON.parse(canvas.dataset.labels),
                                            datasets: [{ data: JSON.parse(canvas.dataset.values), backgroundColor: ['#6366f1','#10b981','#f59e0b','#ef4444','#3b82f6','#a855f7'] }],
                                        },
                                        options: { plugins: { legend: { position: 'bottom' } } },
                                    });
                                });
                            </script>
                        @endpush
                    @endif
                </div>
            </div>

            <div class="flex justify-end">
                <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 9a3 3 0 100-6 3 3 0 000 6zM6 8a2 2 0 11-4 0 2 2 0 014 0zM1.49 15.326a.78.78 0 01-.358-.442 3 3 0 014.308-3.516 6.484 6.484 0 00-1.905 3.959c-.023.222-.014.442.025.654a4.97 4.97 0 01-2.07-.655zM16.44 15.98a4.97 4.97 0 002.07-.654.78.78 0 00.357-.442 3 3 0 00-4.308-3.517 6.484 6.484 0 011.907 3.96 2.32 2.32 0 01-.026.654zM18 8a2 2 0 11-4 0 2 2 0 014 0zM5.304 16.19a.844.844 0 01-.277-.71 5 5 0 019.947 0 .843.843 0 01-.277.71A6.975 6.975 0 0110 18a6.974 6.974 0 01-4.696-1.81z"/>
                    </svg>
                    {{ __('Create User Account') }}
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">User Accounts</h3>

                @if ($users->isEmpty())
                    <p class="text-gray-500">No user accounts found.</p>
                @else
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-3">Name</th>
                                    <th class="px-6 py-3">Email</th>
                                    <th class="px-6 py-3">Role</th>
                                    <th class="px-6 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($users as $user)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $user->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $user->email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 capitalize">
                                                {{ str_replace('_', ' ', $user->role) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">Edit</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Audit Log</h3>

                <form method="GET" action="{{ route('dashboard.admin') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
                    <div>
                        <x-input-label for="action" :value="__('Action')" class="text-xs" />
                        <select id="action" name="action" class="block mt-1 w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All actions') }}</option>
                            @foreach ($auditActions as $action)
                                <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                                    {{ $action }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="user_id" :value="__('User')" class="text-xs" />
                        <select id="user_id" name="user_id" class="block mt-1 w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All users') }}</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ (string) request('user_id') === (string) $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
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

                    <div class="flex items-end gap-2">
                        <x-primary-button>{{ __('Filter') }}</x-primary-button>
                        @if (request()->anyFilled(['action', 'user_id', 'date_from', 'date_to']))
                            <a href="{{ route('dashboard.admin') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </form>

                @if ($auditLogs->isEmpty())
                    <p class="text-gray-500">No activity found for the selected filters.</p>
                @else
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-3">User</th>
                                    <th class="px-6 py-3">Action</th>
                                    <th class="px-6 py-3">When</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($auditLogs as $log)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $log->user?->name ?? 'System' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $log->action }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $log->created_at->format('M j, Y g:i A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $auditLogs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
