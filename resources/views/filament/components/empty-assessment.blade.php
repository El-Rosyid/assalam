<div class="p-6">
    {{-- Header Information --}}
    <div class="bg-gradient-to-r from-blue-50 to-green-50 p-6 rounded-lg border border-blue-200 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $student->nama_lengkap }}</h3>
                <p class="text-sm text-gray-600">NIS: {{ $student->nis }}</p>
            </div>
        </div>
    </div>

    {{-- Empty State --}}
    <div class="text-center py-12">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
        </div>
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $message }}</h3>
        <p class="mt-1 text-sm text-gray-500">Silakan lakukan input penilaian terlebih dahulu untuk melihat hasil.</p>
    </div>
</div>