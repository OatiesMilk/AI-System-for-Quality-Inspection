<div class="relative inline-block mb-6">
    @if ($inspection->image_path)
        <img src="{{ asset('storage/'.$inspection->image_path) }}" alt="Inspection image" class="max-w-full rounded border border-gray-200">
    @else
        <div class="w-full h-64 flex items-center justify-center bg-gray-100 text-gray-400 rounded">No image available</div>
    @endif

    @foreach ($inspection->defects as $defect)
        @php $box = $defect->bounding_box; @endphp
        @if ($box)
            <div class="absolute border-2 border-red-500"
                 style="left: {{ $box['x'] * 100 }}%; top: {{ $box['y'] * 100 }}%; width: {{ $box['width'] * 100 }}%; height: {{ $box['height'] * 100 }}%;">
                <span class="absolute -top-5 left-0 bg-red-500 text-white text-xs px-1 rounded">
                    {{ str_replace('_', ' ', $defect->defect_type) }} ({{ number_format($defect->confidence_score * 100, 1) }}%)
                </span>
            </div>
        @endif
    @endforeach
</div>
