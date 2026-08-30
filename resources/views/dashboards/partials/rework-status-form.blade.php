@php
    $current = $inspection->rework_status ?? 'not_started';
@endphp
<form method="POST" action="{{ route('constructor.inspections.update-status', $inspection) }}" class="flex items-center gap-2">
    @csrf
    @method('PATCH')
    <input type="hidden" name="tab" value="{{ request('tab') }}">
    <select name="rework_status" onchange="this.form.requestSubmit()"
        class="text-xs rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
        <option value="not_started" {{ $current === 'not_started' ? 'selected' : '' }}>{{ __('Not Started') }}</option>
        <option value="in_progress" {{ $current === 'in_progress' ? 'selected' : '' }}>{{ __('In Progress') }}</option>
        <option value="completed" {{ $current === 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
    </select>
</form>
