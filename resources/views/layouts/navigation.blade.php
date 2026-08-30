{{-- Desktop sidebar --}}
<aside class="hidden md:flex md:flex-col md:w-64 md:shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
    @include('layouts.partials.sidebar-content')
</aside>

{{-- Mobile slide-over sidebar --}}
<div
    x-data="{ open: false }"
    x-on:open-mobile-sidebar.window="open = true"
    x-show="open"
    x-cloak
    class="md:hidden fixed inset-0 z-50"
>
    <div
        x-show="open"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        @click="open = false"
        class="absolute inset-0 bg-gray-900/50"
    ></div>
    <div
        x-show="open"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
        class="relative flex flex-col w-72 max-w-[80%] h-full bg-white dark:bg-gray-800 shadow-xl"
        @click.outside="open = false"
    >
        @include('layouts.partials.sidebar-content')
    </div>
</div>
