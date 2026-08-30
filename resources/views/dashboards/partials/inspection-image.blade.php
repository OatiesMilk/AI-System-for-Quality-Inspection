<div class="flex justify-center mb-6">
    <div class="relative inline-block">
        @if ($inspection->hasStoredImage())
            <img src="{{ route('inspections.image', $inspection) }}" alt="Inspection image" class="max-w-full max-h-[32rem] rounded border border-gray-200 dark:border-gray-700">
        @elseif ($inspection->image_path)
            <img src="{{ asset('storage/'.$inspection->image_path) }}" alt="Inspection image" class="max-w-full max-h-[32rem] rounded border border-gray-200 dark:border-gray-700">
        @else
            <div class="w-full h-64 flex items-center justify-center bg-gray-100 dark:bg-gray-900 text-gray-400 dark:text-gray-500 rounded">No image available</div>
        @endif

        @foreach ($inspection->defects as $defect)
            @php $box = $defect->bounding_box; @endphp
            @if ($box)
                <div class="absolute border-2 border-red-500 dark:border-red-600"
                     style="left: {{ $box['x'] * 100 }}%; top: {{ $box['y'] * 100 }}%; width: {{ $box['width'] * 100 }}%; height: {{ $box['height'] * 100 }}%;">
                    <span class="absolute -top-5 left-0 bg-red-500 text-white text-xs px-1 rounded">
                        {{ str_replace('_', ' ', $defect->defect_type) }} ({{ number_format($defect->confidence_score * 100, 1) }}%)
                    </span>
                </div>
            @endif
        @endforeach
    </div>
</div>
