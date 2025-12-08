@php
    $photos = $getState();
    $photoCount = is_array($photos) ? count($photos) : 0;
@endphp

@if ($photoCount > 0)
    <div class="flex items-center gap-2">
        <div class="flex -space-x-2">
            @foreach (array_slice($photos, 0, 3) as $index => $photo)
                <img 
                    src="{{ \Storage::url($photo) }}" 
                    alt="Foto {{ $index + 1 }}"
                    class="w-8 h-8 rounded-full border-2 border-white dark:border-gray-800 object-cover"
                    title="Foto {{ $index + 1 }}"
                >
            @endforeach
        </div>
        
        @if ($photoCount > 3)
            <span class="text-xs text-gray-500 dark:text-gray-400">
                +{{ $photoCount - 3 }} lainnya
            </span>
        @endif
        
        <span class="text-xs font-medium text-gray-600 dark:text-gray-300 ml-1">
            ({{ $photoCount }} foto)
        </span>
    </div>
@else
    <span class="text-xs text-gray-400 dark:text-gray-600 italic">
        Belum ada foto
    </span>
@endif
