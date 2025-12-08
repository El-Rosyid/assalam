<div class="space-y-6">
    <!-- Class Header -->
    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 rounded-lg p-6">
        <h3 class="text-xl font-bold text-indigo-900 mb-2">{{ $kelas->nama_kelas }}</h3>
        <p class="text-indigo-700">Wali Kelas: {{ $kelas->waliKelas->nama_lengkap ?? '-' }}</p>
    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-2 gap-4">
        <!-- Total Siswa -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-600">Total Siswa</p>
                    <p class="text-2xl font-semibold text-blue-900">{{ $totalSiswa }}</p>
                </div>
            </div>
        </div>

        <!-- Siswa dengan Penilaian -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-600">Ada Penilaian</p>
                    <p class="text-2xl font-semibold text-green-900">{{ $siswaWithAssessments }}</p>
                </div>
            </div>
        </div>

        <!-- Siswa dengan Pertumbuhan -->
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-purple-600">Ada Pertumbuhan</p>
                    <p class="text-2xl font-semibold text-purple-900">{{ $siswaWithGrowth }}</p>
                </div>
            </div>
        </div>

        <!-- Siswa dengan Kehadiran -->
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-orange-600">Ada Kehadiran</p>
                    <p class="text-2xl font-semibold text-orange-900">{{ $siswaWithAttendance }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Section -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h4 class="text-lg font-semibold text-gray-900 mb-4">Kelengkapan Data Kelas</h4>
        
        @if($totalSiswa > 0)
            <div class="space-y-4">
                <!-- Penilaian Progress -->
                @php
                    $assessmentPercentage = ($siswaWithAssessments / $totalSiswa) * 100;
                @endphp
                <div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="font-medium text-gray-700">Penilaian</span>
                        <span class="text-gray-500">{{ $siswaWithAssessments }}/{{ $totalSiswa }} ({{ number_format($assessmentPercentage, 1) }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-green-500 h-3 rounded-full transition-all duration-500" style="width: {{ $assessmentPercentage }}%"></div>
                    </div>
                </div>

                <!-- Pertumbuhan Progress -->
                @php
                    $growthPercentage = ($siswaWithGrowth / $totalSiswa) * 100;
                @endphp
                <div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="font-medium text-gray-700">Pertumbuhan</span>
                        <span class="text-gray-500">{{ $siswaWithGrowth }}/{{ $totalSiswa }} ({{ number_format($growthPercentage, 1) }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-purple-500 h-3 rounded-full transition-all duration-500" style="width: {{ $growthPercentage }}%"></div>
                    </div>
                </div>

                <!-- Kehadiran Progress -->
                @php
                    $attendancePercentage = ($siswaWithAttendance / $totalSiswa) * 100;
                @endphp
                <div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="font-medium text-gray-700">Kehadiran</span>
                        <span class="text-gray-500">{{ $siswaWithAttendance }}/{{ $totalSiswa }} ({{ number_format($attendancePercentage, 1) }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-orange-500 h-3 rounded-full transition-all duration-500" style="width: {{ $attendancePercentage }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Overall Status -->
            @php
                $overallPercentage = ($assessmentPercentage + $growthPercentage + $attendancePercentage) / 3;
            @endphp
            <div class="mt-6 p-4 border rounded-lg {{ $overallPercentage >= 90 ? 'bg-green-50 border-green-200' : ($overallPercentage >= 70 ? 'bg-yellow-50 border-yellow-200' : 'bg-red-50 border-red-200') }}">
                <div class="flex items-center">
                    @if($overallPercentage >= 90)
                        <svg class="h-5 w-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-green-800 font-medium">Kelas Siap - {{ number_format($overallPercentage, 1) }}% lengkap</span>
                    @elseif($overallPercentage >= 70)
                        <svg class="h-5 w-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-yellow-800 font-medium">Perlu Perhatian - {{ number_format($overallPercentage, 1) }}% lengkap</span>
                    @else
                        <svg class="h-5 w-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-red-800 font-medium">Perlu Tindakan - {{ number_format($overallPercentage, 1) }}% lengkap</span>
                    @endif
                </div>
            </div>
        @else
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada siswa</h3>
                <p class="mt-1 text-sm text-gray-500">Belum ada siswa yang terdaftar di kelas ini.</p>
            </div>
        @endif
    </div>
</div>