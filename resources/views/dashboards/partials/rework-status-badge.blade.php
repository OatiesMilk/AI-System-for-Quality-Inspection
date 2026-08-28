@php
    $status = $status ?? 'not_started';
    $labels = ['not_started' => 'Not Started', 'in_progress' => 'In Progress', 'completed' => 'Completed'];
    $styles = [
        'not_started' => 'bg-gray-100 text-gray-700',
        'in_progress' => 'bg-blue-50 text-blue-700',
        'completed' => 'bg-green-50 text-green-700',
    ];
@endphp
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $styles[$status] ?? $styles['not_started'] }}">
    {{ $labels[$status] ?? 'Not Started' }}
</span>
