<div class="space-y-6">
    <!-- Student Header -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-indigo-900 mb-2">{{ $siswa->nama_lengkap }}</h3>
                <p class="text-indigo-700">NISN: {{ $siswa->nisn }}</p>
                <p class="text-indigo-600">Kelas: {{ $kelas->nama_kelas ?? '-' }}</p>
                <p class="text-indigo-600">Tahun Ajaran: {{ $tahunAjaran->tahun ?? '-' }}</p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-blue-600">
                    {{ number_format($completionPercentage, 1) }}%
                </div>
                <p class="text-sm text-gray-600">Kelengkapan Data</p>
            </div>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Total Penilaian -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-green-600">Total Penilaian</p>
                    <p class="text-3xl font-bold text-green-900">{{ $totalAssessments }}</p>
                    <p class="text-sm text-green-700">Mata Pelajaran</p>
                </div>
            </div>
            
            <div class="mt-4">
                @if($totalAssessments > 0)
                    <div class="flex items-center">
                        <div class="flex-1 bg-green-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: 100%"></div>
                        </div>
                        <span class="ml-2 text-sm text-green-700 font-medium">Lengkap</span>
                    </div>
                @else
                    <div class="flex items-center">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-gray-400 h-2 rounded-full" style="width: 0%"></div>
                        </div>
                        <span class="ml-2 text-sm text-gray-500 font-medium">Kosong</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Total Pertumbuhan -->
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-10 w-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-purple-600">Data Pertumbuhan</p>
                    <p class="text-3xl font-bold text-purple-900">{{ $totalGrowths }}</p>
                    <p class="text-sm text-purple-700">Aspek Perkembangan</p>
                </div>
            </div>
            
            <div class="mt-4">
                @if($totalGrowths > 0)
                    <div class="flex items-center">
                        <div class="flex-1 bg-purple-200 rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full" style="width: 100%"></div>
                        </div>
                        <span class="ml-2 text-sm text-purple-700 font-medium">Tersedia</span>
                    </div>
                @else
                    <div class="flex items-center">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-gray-400 h-2 rounded-full" style="width: 0%"></div>
                        </div>
                        <span class="ml-2 text-sm text-gray-500 font-medium">Kosong</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Total Kehadiran -->
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-10 w-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-orange-600">Data Kehadiran</p>
                    <p class="text-3xl font-bold text-orange-900">{{ $totalAttendances }}</p>
                    <p class="text-sm text-orange-700">Bulan Tercatat</p>
                </div>
            </div>
            
            <div class="mt-4">
                @if($totalAttendances > 0)
                    <div class="flex items-center">
                        <div class="flex-1 bg-orange-200 rounded-full h-2">
                            <div class="bg-orange-500 h-2 rounded-full" style="width: 100%"></div>
                        </div>
                        <span class="ml-2 text-sm text-orange-700 font-medium">Tercatat</span>
                    </div>
                @else
                    <div class="flex items-center">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-gray-400 h-2 rounded-full" style="width: 0%"></div>
                        </div>
                        <span class="ml-2 text-sm text-gray-500 font-medium">Kosong</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Overall Progress -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h4 class="text-lg font-semibold text-gray-900 mb-6">Kelengkapan Raport</h4>
        
        @php
            $assessmentStatus = $totalAssessments > 0 ? 100 : 0;
            $growthStatus = $totalGrowths > 0 ? 100 : 0;
            $attendanceStatus = $totalAttendances > 0 ? 100 : 0;
        @endphp

        <div class="space-y-4">
            <!-- Assessment Progress -->
            <div>
                <div class="flex justify-between text-sm mb-2">
                    <span class="font-medium text-gray-700">Penilaian Mata Pelajaran</span>
                    <span class="text-gray-500">{{ $totalAssessments > 0 ? 'Lengkap' : 'Belum Ada' }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-green-500 h-3 rounded-full transition-all duration-500" style="width: {{ $assessmentStatus }}%"></div>
                </div>
            </div>

            <!-- Growth Progress -->
            <div>
                <div class="flex justify-between text-sm mb-2">
                    <span class="font-medium text-gray-700">Data Pertumbuhan</span>
                    <span class="text-gray-500">{{ $totalGrowths > 0 ? 'Tersedia' : 'Belum Ada' }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-purple-500 h-3 rounded-full transition-all duration-500" style="width: {{ $growthStatus }}%"></div>
                </div>
            </div>

            <!-- Attendance Progress -->
            <div>
                <div class="flex justify-between text-sm mb-2">
                    <span class="font-medium text-gray-700">Data Kehadiran</span>
                    <span class="text-gray-500">{{ $totalAttendances > 0 ? 'Tercatat' : 'Belum Ada' }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-orange-500 h-3 rounded-full transition-all duration-500" style="width: {{ $attendanceStatus }}%"></div>
                </div>
            </div>
        </div>

        <!-- Overall Status -->
        <div class="mt-6 p-4 border rounded-lg {{ $completionPercentage >= 90 ? 'bg-green-50 border-green-200' : ($completionPercentage >= 70 ? 'bg-yellow-50 border-yellow-200' : 'bg-red-50 border-red-200') }}">
            <div class="flex items-center justify-center">
                @if($completionPercentage >= 90)
                    <svg class="h-6 w-6 text-green-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-green-800 font-semibold text-lg">Raport Lengkap - Siap Dicetak</span>
                @elseif($completionPercentage >= 70)
                    <svg class="h-6 w-6 text-yellow-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-yellow-800 font-semibold text-lg">Raport Hampir Lengkap</span>
                @else
                    <svg class="h-6 w-6 text-red-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-red-800 font-semibold text-lg">Data Raport Belum Lengkap</span>
                @endif
            </div>
            
            @if($completionPercentage < 100)
                <div class="mt-3 text-center">
                    <p class="text-sm text-gray-600">
                        Hubungi wali kelas untuk informasi lebih lanjut tentang kelengkapan data raport Anda.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Information Notice -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Informasi</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Data statistik ini menunjukkan kelengkapan raport Anda. Jika ada data yang belum lengkap, silakan hubungi wali kelas untuk informasi lebih lanjut.</p>
                </div>
            </div>
        </div>
    </div>
</div>