@php
    $initials = collect(explode(' ', Auth::user()->name))
        ->map(fn ($word) => mb_substr($word, 0, 1))
        ->take(2)
        ->implode('');
@endphp

<div class="h-16 flex items-center px-6 border-b border-gray-200 dark:border-gray-700 shrink-0">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
        <x-application-logo class="h-8 w-8 rounded object-cover" />
        <span class="font-semibold text-gray-900 dark:text-white">{{ config('app.name') }}</span>
    </a>
</div>

<nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
    <p class="px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">{{ __('Navigation') }}</p>

    <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard*')">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        {{ __('Dashboard') }}
    </x-sidebar-link>

    @if (Auth::user()->role === 'product_manager')
        <x-sidebar-link :href="route('manager.batches.create')" :active="request()->routeIs('manager.batches.*')">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            {{ __('Create Batch') }}
        </x-sidebar-link>
        <x-sidebar-link :href="route('decision-support.index')" :active="request()->routeIs('decision-support.*')">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.121 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            {{ __('AI Insights') }}
        </x-sidebar-link>
    @elseif (Auth::user()->role === 'system_admin')
        <x-sidebar-link :href="route('admin.users.create')" :active="request()->routeIs('admin.users.*')">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            {{ __('Create Account') }}
        </x-sidebar-link>
    @endif
</nav>

<div class="border-t border-gray-200 dark:border-gray-700 p-3 space-y-1 shrink-0">
    <x-theme-toggle />

    <x-sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        {{ __('Profile') }}
    </x-sidebar-link>

    <div class="flex items-center gap-3 px-3 py-2 mt-1">
        <div class="h-9 w-9 shrink-0 rounded-full bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center text-indigo-700 dark:text-indigo-400 font-semibold text-sm">
            {{ $initials }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ Auth::user()->name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/50 dark:hover:text-gray-100 transition w-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            {{ __('Log Out') }}
        </button>
    </form>
</div>
