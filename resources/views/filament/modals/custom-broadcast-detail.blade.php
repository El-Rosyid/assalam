@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="space-y-4">
    {{-- Status Badge --}}
    <div class="flex items-center justify-between border-b pb-3">
        <h3 class="text-lg font-semibold">{{ $record->title }}</h3>
        <x-filament::badge :color="match($record->status) {
            'draft' => 'gray',
            'sending' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
            default => 'gray'
        }">
            {{ $record->status_badge }}
        </x-filament::badge>
    </div>

    {{-- Info Grid --}}
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <p class="text-gray-500 dark:text-gray-400">Dibuat Oleh</p>
            <p class="font-medium">{{ $record->user->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-gray-500 dark:text-gray-400">Tanggal Dibuat</p>
            <p class="font-medium">{{ $record->created_at->format('d M Y, H:i') }}</p>
        </div>
        <div>
            <p class="text-gray-500 dark:text-gray-400">Target</p>
            <p class="font-medium">{{ $record->target_type_text }}</p>
        </div>
        <div>
            <p class="text-gray-500 dark:text-gray-400">Dikirim Pada</p>
            <p class="font-medium">{{ $record->sent_at ? $record->sent_at->format('d M Y, H:i') : '-' }}</p>
        </div>
    </div>

    {{-- Statistics --}}
    <div class="grid grid-cols-4 gap-3 py-3 border-y">
        <div class="text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $record->total_recipients }}</p>
            <p class="text-xs text-gray-500">Total</p>
        </div>
        <div class="text-center">
            <p class="text-2xl font-bold text-green-600">{{ $record->sent_count }}</p>
            <p class="text-xs text-gray-500">Terkirim</p>
        </div>
        <div class="text-center">
            <p class="text-2xl font-bold text-yellow-600">{{ $record->pending_count }}</p>
            <p class="text-xs text-gray-500">Pending</p>
        </div>
        <div class="text-center">
            <p class="text-2xl font-bold text-red-600">{{ $record->failed_count }}</p>
            <p class="text-xs text-gray-500">Gagal</p>
        </div>
    </div>

    {{-- Message Content --}}
    <div>
        <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">Isi Pesan</p>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border">
            <p class="whitespace-pre-wrap">{{ $record->message }}</p>
        </div>
    </div>

    {{-- Progress Bar --}}
    @if($record->status !== 'draft')
    <div>
        <div class="flex justify-between text-sm mb-1">
            <span class="text-gray-500">Progress Pengiriman</span>
            <span class="font-medium">{{ $record->progress_percentage }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
            <div class="bg-green-600 h-2.5 rounded-full" style="width: {{ $record->progress_percentage }}%"></div>
        </div>
    </div>
    @endif
</div>
