<div class="max-h-[60vh] overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent">
    <div class="space-y-4">
        <!-- Header -->
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Statistik Raport Sekolah</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Ringkasan data raport dan penilaian siswa</p>
        </div>

        <!-- Statistics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Total Kelas -->
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 border border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Kelas</p>
                    <p class="text-3xl font-bold text-blue-700 dark:text-blue-300 mt-2">{{ $totalKelas }}</p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-800 rounded-full p-3">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
            <p class="text-xs text-blue-600 dark:text-blue-400 mt-3">Kelas yang terdaftar di sistem</p>
        </div>

        <!-- Total Siswa -->
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6 border border-green-200 dark:border-green-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-600 dark:text-green-400">Total Siswa</p>
                    <p class="text-3xl font-bold text-green-700 dark:text-green-300 mt-2">{{ $totalSiswa }}</p>
                </div>
                <div class="bg-green-100 dark:bg-green-800 rounded-full p-3">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xs text-green-600 dark:text-green-400 mt-3">Siswa aktif dalam sistem</p>
        </div>

        <!-- Total Penilaian -->
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-6 border border-purple-200 dark:border-purple-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Total Penilaian</p>
                    <p class="text-3xl font-bold text-purple-700 dark:text-purple-300 mt-2">{{ $totalPenilaian }}</p>
                </div>
                <div class="bg-purple-100 dark:bg-purple-800 rounded-full p-3">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
            </div>
            <p class="text-xs text-purple-600 dark:text-purple-400 mt-3">Record penilaian semester</p>
        </div>

        <!-- Total Pertumbuhan -->
        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-6 border border-orange-200 dark:border-orange-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-orange-600 dark:text-orange-400">Catatan Pertumbuhan</p>
                    <p class="text-3xl font-bold text-orange-700 dark:text-orange-300 mt-2">{{ $totalPertumbuhan }}</p>
                </div>
                <div class="bg-orange-100 dark:bg-orange-800 rounded-full p-3">
                    <svg class="w-8 h-8 text-orange-600 dark:text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
            <p class="text-xs text-orange-600 dark:text-orange-400 mt-3">Record pertumbuhan siswa</p>
        </div>
    </div>

    <!-- Additional Stats -->
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700 mt-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Kehadiran</p>
                <p class="text-2xl font-bold text-gray-700 dark:text-gray-300 mt-2">{{ $totalKehadiran }}</p>
            </div>
            <div class="bg-gray-100 dark:bg-gray-700 rounded-full p-3">
                <svg class="w-8 h-8 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-600 dark:text-gray-400 mt-3">Record kehadiran siswa yang tercatat</p>
    </div>

    <!-- Average Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Rata-rata Siswa/Kelas</p>
            <p class="text-xl font-bold text-gray-700 dark:text-gray-300 mt-2">
                @if($totalKelas > 0)
                    {{ round($totalSiswa / $totalKelas, 1) }}
                @else
                    0
                @endif
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Penilaian/Siswa</p>
            <p class="text-xl font-bold text-gray-700 dark:text-gray-300 mt-2">
                @if($totalSiswa > 0)
                    {{ round($totalPenilaian / $totalSiswa, 1) }}
                @else
                    0
                @endif
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Pertumbuhan/Siswa</p>
            <p class="text-xl font-bold text-gray-700 dark:text-gray-300 mt-2">
                @if($totalSiswa > 0)
                    {{ round($totalPertumbuhan / $totalSiswa, 1) }}
                @else
                    0
                @endif
            </p>
        </div>
    </div>

        <!-- Footer Info -->
        <div class="text-center mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Data diperbarui: {{ now()->format('d F Y, H:i') }}
            </p>
        </div>
    </div>
</div>
