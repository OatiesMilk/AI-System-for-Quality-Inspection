<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('System Admin Dashboard') }}
        </h2>
    </x-slot>

    @php $tab = request('tab', 'overview'); @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 rounded-lg p-4">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Tab Navigation --}}
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Tabs">
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'overview']) }}"
                        class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition {{ $tab === 'overview' ? 'border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-500' }}">
                        {{ __('Overview') }}
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'accounts']) }}"
                        class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition {{ $tab === 'accounts' ? 'border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-500' }}">
                        {{ __('User Accounts') }}
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'activity']) }}"
                        class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition {{ $tab === 'activity' ? 'border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-500' }}">
                        {{ __('Activity Log') }}
                    </a>
                </nav>
            </div>

            @if ($tab === 'overview')
                <div class="space-y-6">
                    {{-- User Activity Summary Cards --}}
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 border-l-4 border-indigo-500 dark:border-indigo-400">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Total Users</div>
                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $userActivityStats['total_users'] }}</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 border-l-4 border-green-500 dark:border-green-600">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Logins Today</div>
                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $userActivityStats['logins_today'] }}</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 border-l-4 border-yellow-500 dark:border-yellow-600">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Logouts Today</div>
                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $userActivityStats['logouts_today'] }}</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 border-l-4 border-blue-500 dark:border-blue-600">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Active Today</div>
                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $userActivityStats['active_today'] }}</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 border-l-4 border-purple-500 dark:border-purple-600">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Accounts Created</div>
                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $userActivityStats['accounts_created'] }}</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 border-l-4 border-orange-500 dark:border-orange-600">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Accounts Updated</div>
                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $userActivityStats['accounts_updated'] }}</div>
                        </div>
                    </div>

                    {{-- Login Trend + Per-User Activity --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Login Activity (Last 7 Days)</h3>
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

                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Per-User Activity</h3>
                            @if ($perUserActivity->isEmpty())
                                <p class="text-gray-500 dark:text-gray-400 text-sm">No user activity recorded yet.</p>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                        <thead>
                                            <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                <th class="py-2 pr-4">User</th>
                                                <th class="py-2 pr-4">Role</th>
                                                <th class="py-2 pr-4">Logins</th>
                                                <th class="py-2 pr-4">Logouts</th>
                                                <th class="py-2 pr-4">Total Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                            @foreach ($perUserActivity as $user)
                                                <tr>
                                                    <td class="py-2 pr-4 font-medium text-gray-900 dark:text-white">{{ $user->name }}</td>
                                                    <td class="py-2 pr-4">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 capitalize">
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
                </div>
            @elseif ($tab === 'accounts')
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">User Accounts</h3>
                    @if ($users->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400">No user accounts found.</p>
                    @else
                        <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <th class="px-6 py-3">Name</th>
                                        <th class="px-6 py-3">Email</th>
                                        <th class="px-6 py-3">Role</th>
                                        <th class="px-6 py-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($users as $user)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 capitalize">
                                                    {{ str_replace('_', ' ', $user->role) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium hover:underline">Edit</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">User Activity Log</h3>

                    <form method="GET" action="{{ route('dashboard.admin') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
                        <input type="hidden" name="tab" value="activity">
                        <div>
                            <x-input-label for="action" :value="__('Event')" class="text-xs" />
                            <select id="action" name="action" class="block mt-1 w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
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
                            <select id="user_id" name="user_id" class="block mt-1 w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
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
                                <a href="{{ request()->fullUrlWithQuery(['tab' => 'activity', 'action' => null, 'user_id' => null, 'date_from' => null, 'date_to' => null]) }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white underline">{{ __('Clear') }}</a>
                            @endif
                        </div>
                    </form>

                    @if ($userAuditLogs->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400">No activity found for the selected filters.</p>
                    @else
                        <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <th class="px-6 py-3">User</th>
                                        <th class="px-6 py-3">Role</th>
                                        <th class="px-6 py-3">Event</th>
                                        <th class="px-6 py-3">Details</th>
                                        <th class="px-6 py-3">When</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($userAuditLogs as $log)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $log->user?->name ?? 'System' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @if ($log->user)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 capitalize">
                                                        {{ str_replace('_', ' ', $log->user->role) }}
                                                    </span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @php
                                                    $badge = match($log->action) {
                                                        'user.login'            => ['bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400',  'Login'],
                                                        'user.logout'           => ['bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400',    'Logout'],
                                                        'user.created'          => ['bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-400',    'Account Created'],
                                                        'user.updated'          => ['bg-yellow-100 dark:bg-yellow-500/20 text-yellow-700 dark:text-yellow-400','Account Updated'],
                                                        'inspection.validated'  => ['bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-400','Inspection Validated'],
                                                        'inspection.reworked'   => ['bg-orange-100 dark:bg-orange-500/20 text-orange-700 dark:text-orange-400','Rework Resolved'],
                                                        'batch.created'         => ['bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-400','Batch Created'],
                                                        default                 => ['bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400',    $log->action],
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge[0] }}">
                                                    {{ $badge[1] }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                @if (!empty($log->metadata))
                                                    @foreach ($log->metadata as $key => $value)
                                                        <span class="text-xs">{{ str_replace('_', ' ', $key) }}: <strong>{{ $value }}</strong></span><br>
                                                    @endforeach
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
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
            @endif

        </div>
    </div>
</x-app-layout>
