<div class="space-y-4">
    <!-- Student Info -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-blue-900 mb-2">Informasi Siswa</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium text-blue-700">Nama:</span>
                <span class="text-blue-900">{{ $siswa->nama_lengkap }}</span>
            </div>
            <div>
                <span class="font-medium text-blue-700">NIS:</span>
                <span class="text-blue-900">{{ $siswa->nis }}</span>
            </div>
            <div>
                <span class="font-medium text-blue-700">Kelas:</span>
                <span class="text-blue-900">{{ $siswa->dataKelas->nama_kelas ?? '-' }}</span>
            </div>
            <div>
                <span class="font-medium text-blue-700">Wali Kelas:</span>
                <span class="text-blue-900">{{ $siswa->dataKelas->waliKelas->nama_lengkap ?? '-' }}</span>
            </div>
        </div>
    </div>

    <!-- Data Summary -->
    <div class="grid grid-cols-3 gap-4">
        <!-- Penilaian -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-700">{{ $assessments }}</div>
            <div class="text-sm text-green-600">Penilaian</div>
            @if($assessments > 0)
                <div class="text-xs text-green-500 mt-1">✓ Tersedia</div>
            @else
                <div class="text-xs text-red-500 mt-1">⚠ Belum ada</div>
            @endif
        </div>

        <!-- Pertumbuhan -->
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-purple-700">{{ $growth }}</div>
            <div class="text-sm text-purple-600">Record Pertumbuhan</div>
            @if($growth > 0)
                <div class="text-xs text-green-500 mt-1">✓ Tersedia</div>
            @else
                <div class="text-xs text-red-500 mt-1">⚠ Belum ada</div>
            @endif
        </div>

        <!-- Kehadiran -->
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 text-center">
            @if($attendance)
                @php
                    $totalAbsen = ($attendance->alfa ?? 0) + ($attendance->ijin ?? 0) + ($attendance->sakit ?? 0);
                @endphp
                <div class="text-2xl font-bold text-orange-700">{{ $totalAbsen }}</div>
                <div class="text-sm text-orange-600">Total Absen</div>
                @if($totalAbsen == 0)
                    <div class="text-xs text-green-500 mt-1">✓ Hadir sempurna</div>
                @else
                    <div class="text-xs text-orange-500 mt-1">
                        A:{{ $attendance->alfa ?? 0 }} | I:{{ $attendance->ijin ?? 0 }} | S:{{ $attendance->sakit ?? 0 }}
                    </div>
                @endif
            @else
                <div class="text-2xl font-bold text-gray-400">-</div>
                <div class="text-sm text-gray-500">Kehadiran</div>
                <div class="text-xs text-red-500 mt-1">⚠ Belum ada data</div>
            @endif
        </div>
    </div>

    <!-- Status Kelengkapan -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h4 class="text-md font-semibold text-gray-700 mb-3">Status Kelengkapan Raport</h4>
        
        @php
            $isComplete = $assessments > 0 && $growth > 0 && $attendance;
            $completionPercentage = 0;
            if ($assessments > 0) $completionPercentage += 33;
            if ($growth > 0) $completionPercentage += 33;
            if ($attendance) $completionPercentage += 34;
        @endphp

        <div class="flex items-center space-x-3 mb-2">
            @if($isComplete)
                <div class="flex-shrink-0 w-6 h-6 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <span class="text-green-700 font-medium">Raport Lengkap - Siap Dicetak</span>
            @else
                <div class="flex-shrink-0 w-6 h-6 bg-yellow-100 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <span class="text-yellow-700 font-medium">Data Belum Lengkap</span>
            @endif
        </div>

        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full transition-all duration-300" style="width: {{ $completionPercentage }}%"></div>
        </div>
        <div class="text-xs text-gray-500 mt-1">{{ $completionPercentage }}% lengkap</div>

        <!-- Missing Data Warning -->
        @if(!$isComplete)
            <div class="mt-3 text-sm text-yellow-700 bg-yellow-50 border border-yellow-200 rounded p-3">
                <div class="font-medium mb-1">Data yang belum lengkap:</div>
                <ul class="list-disc list-inside space-y-1 text-xs">
                    @if($assessments == 0)
                        <li>Penilaian siswa belum diinput</li>
                    @endif
                    @if($growth == 0)
                        <li>Data pertumbuhan belum diinput</li>
                    @endif
                    @if(!$attendance)
                        <li>Data kehadiran belum diinput</li>
                    @endif
                </ul>
            </div>
        @endif
    </div>
</div>