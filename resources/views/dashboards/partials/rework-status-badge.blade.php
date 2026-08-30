@php
    $status = $status ?? 'not_started';
    $labels = ['not_started' => 'Not Started', 'in_progress' => 'In Progress', 'completed' => 'Completed'];
    $styles = [
        'not_started' => 'bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300',
        'in_progress' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400',
        'completed' => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400',
    ];
@endphp
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $styles[$status] ?? $styles['not_started'] }}">
    {{ $labels[$status] ?? 'Not Started' }}
</span>
