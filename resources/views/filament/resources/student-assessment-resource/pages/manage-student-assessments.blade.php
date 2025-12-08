<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Alert Banner for New Students -->
        @if($this->newStudentsCount > 0)
        <div class="bg-warning-50 dark:bg-warning-900/20 border-2 border-warning-300 dark:border-warning-700 rounded-lg p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-start gap-3 flex-1">
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="w-7 h-7 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-warning-900 dark:text-warning-100">
                            Siswa Baru Ditemukan
                        </h3>
                        <p class="mt-1.5 text-sm text-warning-800 dark:text-warning-200">
                            Ada <strong class="font-bold">{{ $this->newStudentsCount }} siswa baru</strong> di kelas Anda yang belum memiliki penilaian untuk semester ini.
                        </p>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <button 
                        wire:click="syncNewStudents"
                        type="button"
                        class="inline-flex items-center gap-2 px-5 py-3 bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white text-sm font-bold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Sinkronkan Sekarang
                    </button>
                </div>
            </div>
        </div>
        @endif
    
        <!-- Header Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Semester</h3>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                        Semester {{ $this->semester }} ({{ $this->semester == 1 ? 'Ganjil' : 'Genap' }})
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Penilaian</h3>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $this->getTableQuery() ? $this->getTableQuery()->count() : 0 }} Siswa
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Keterangan</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            Belum Dinilai
                        </span>
                        tampil di atas
                    </p>
                </div>
            </div>
        </div>

        <!-- Table -->
        {{ $this->table }}
    </div>
</x-filament-panels::page>
