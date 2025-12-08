<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Class Info Widget with Dark Mode Support --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Kelas</h3>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $record->nama_kelas }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Wali Kelas</h3>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $record->waliKelas->nama_lengkap }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Jumlah Siswa</h3>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $record->siswa()->count() }} siswa</p>
                </div>
            </div>
        </div>

        {{-- Instructions with Dark Mode Support --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400 dark:border-blue-500 p-4 rounded-r-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400 dark:text-blue-300" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700 dark:text-blue-200">
                        <strong class="font-semibold">Petunjuk:</strong> Klik langsung pada kolom Alfa, Ijin, atau Sakit untuk mengedit data kehadiran siswa. 
                        Data akan tersimpan otomatis saat Anda selesai mengedit.
                    </p>
                </div>
            </div>
        </div>

        {{-- Table with Dark Mode Support --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>