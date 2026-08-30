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

            {{-- User Activity Summary Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-4 border-l-4 border-indigo-500">
                    <div class="text-xs text-gray-500">Total Users</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $userActivityStats['total_users'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4 border-l-4 border-green-500">
                    <div class="text-xs text-gray-500">Logins Today</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $userActivityStats['logins_today'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4 border-l-4 border-yellow-500">
                    <div class="text-xs text-gray-500">Logouts Today</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $userActivityStats['logouts_today'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4 border-l-4 border-blue-500">
                    <div class="text-xs text-gray-500">Active Today</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $userActivityStats['active_today'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4 border-l-4 border-purple-500">
                    <div class="text-xs text-gray-500">Accounts Created</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $userActivityStats['accounts_created'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4 border-l-4 border-orange-500">
                    <div class="text-xs text-gray-500">Accounts Updated</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $userActivityStats['accounts_updated'] }}</div>
                </div>
            </div>

            {{-- Login Trend + Per-User Activity --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Login Activity (Last 7 Days)</h3>
                    <canvas id="loginTrendChart"
                        data-labels="{{ $loginTrendLabels->toJson() }}"
                        data-values="{{ $loginTrendValues->toJson() }}"></canvas>
                    @push('scripts')
                        <script>
                            document.addEventListener('DOMContentLoaded', () => {
                                const canvas = document.getElementById('loginTrendChart');
                                if (!canvas || !window.Chart) return;
                                new window.Chart(canvas, {
                                    type: 'bar',
                                    data: {
                                        labels: JSON.parse(canvas.dataset.labels),
                                        datasets: [{
                                            label: 'Logins',
                                            data: JSON.parse(canvas.dataset.values),
                                            backgroundColor: '#6366f1',
                                        }],
                                    },
                                    options: {
                                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                                        plugins: { legend: { display: false } },
                                    },
                                });
                            });
                        </script>
                    @endpush
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Per-User Activity</h3>
                    @if ($perUserActivity->isEmpty())
                        <p class="text-gray-500 text-sm">No user activity recorded yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                                        <th class="py-2 pr-4">User</th>
                                        <th class="py-2 pr-4">Role</th>
                                        <th class="py-2 pr-4">Logins</th>
                                        <th class="py-2 pr-4">Logouts</th>
                                        <th class="py-2 pr-4">Total Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($perUserActivity as $user)
                                        <tr>
                                            <td class="py-2 pr-4 font-medium text-gray-900">{{ $user->name }}</td>
                                            <td class="py-2 pr-4">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 capitalize">
                                                    {{ str_replace('_', ' ', $user->role) }}
                                                </span>
                                            </td>
                                            <td class="py-2 pr-4">{{ $user->logins }}</td>
                                            <td class="py-2 pr-4">{{ $user->logouts }}</td>
                                            <td class="py-2 pr-4 font-semibold">{{ $user->total_actions }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- User Accounts --}}
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

            {{-- User Activity Log --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">User Activity Log</h3>

                <form method="GET" action="{{ route('dashboard.admin') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
                    <div>
                        <x-input-label for="action" :value="__('Event')" class="text-xs" />
                        <select id="action" name="action" class="block mt-1 w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All events') }}</option>
                            @foreach ($auditActions as $action)
                                <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                                    {{ str_replace('.', ' → ', $action) }}
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

                @if ($userAuditLogs->isEmpty())
                    <p class="text-gray-500">No activity found for the selected filters.</p>
                @else
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-3">User</th>
                                    <th class="px-6 py-3">Role</th>
                                    <th class="px-6 py-3">Event</th>
                                    <th class="px-6 py-3">Details</th>
                                    <th class="px-6 py-3">When</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($userAuditLogs as $log)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $log->user?->name ?? 'System' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if ($log->user)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 capitalize">
                                                    {{ str_replace('_', ' ', $log->user->role) }}
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @php
                                                $badge = match($log->action) {
                                                    'user.login'            => ['bg-green-100 text-green-700',  'Login'],
                                                    'user.logout'           => ['bg-gray-100 text-gray-600',    'Logout'],
                                                    'user.created'          => ['bg-blue-100 text-blue-700',    'Account Created'],
                                                    'user.updated'          => ['bg-yellow-100 text-yellow-700','Account Updated'],
                                                    'inspection.validated'  => ['bg-indigo-100 text-indigo-700','Inspection Validated'],
                                                    'inspection.reworked'   => ['bg-orange-100 text-orange-700','Rework Resolved'],
                                                    'batch.created'         => ['bg-purple-100 text-purple-700','Batch Created'],
                                                    default                 => ['bg-gray-100 text-gray-600',    $log->action],
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge[0] }}">
                                                {{ $badge[1] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            @if (!empty($log->metadata))
                                                @foreach ($log->metadata as $key => $value)
                                                    <span class="text-xs">{{ str_replace('_', ' ', $key) }}: <strong>{{ $value }}</strong></span><br>
                                                @endforeach
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $log->created_at->format('M j, Y g:i A') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $userAuditLogs->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
