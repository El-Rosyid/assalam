<div class="space-y-6">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6">
        <div class="flex items-center space-x-4">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-xl font-bold text-blue-900">{{ $siswa->nama_lengkap }}</h3>
                <p class="text-blue-700">NISN: {{ $siswa->nisn }}</p>
                @php
                    $kelasInfo = $siswa->kelasInfo ?? \App\Models\data_kelas::find($siswa->kelas);
                @endphp
                    <p class="text-blue-600">Kelas: {{ $kelasInfo?->nama_kelas ?? '-' }}</p>
                    @if($kelasInfo && $kelasInfo->waliKelas)
                        <p class="text-blue-600">Wali Kelas: {{ $kelasInfo->waliKelas->nama_lengkap }}</p>
                    @endif
            </div> 
            <div class="text-right">
                @php
                    $statusColor = $completionPercentage >= 90 ? 'green' : ($completionPercentage >= 70 ? 'yellow' : 'red');
                @endphp
                <div class="text-2xl font-bold text-{{ $statusColor }}-600">
                    {{ number_format($completionPercentage, 1) }}%
                </div>
                <p class="text-sm text-gray-600">Kelengkapan</p>
            </div>
        </div>
    </div>

    <!-- Data Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Penilaian -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-600">Penilaian</p>
                    <p class="text-2xl font-semibold text-green-900">{{ $assessments->count() }}</p>
                    <p class="text-xs text-green-700">Mata Pelajaran</p>
                </div>
            </div>
        </div>

        <!-- Pertumbuhan -->
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-purple-600">Pertumbuhan</p>
                    <p class="text-2xl font-semibold text-purple-900">{{ $growthRecords->count() }}</p>
                    <p class="text-xs text-purple-700">Aspek Perkembangan</p>
                </div>
            </div>
        </div>

        <!-- Kehadiran -->
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-orange-600">Kehadiran</p>
                    <p class="text-2xl font-semibold text-orange-900">{{ $attendanceRecords->count() }}</p>
                    <p class="text-xs text-orange-700">Bulan Tercatat</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Information -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Penilaian Detail -->
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Penilaian Mata Pelajaran
            </h4>
            @if($assessments->count() > 0)
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($assessments->take(10) as $assessment)
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900">{{ $assessment->mapel->nama_mapel ?? 'Mata Pelajaran' }}</p>
                                <p class="text-sm text-gray-600">{{ $assessment->guru->nama_lengkap ?? 'Guru' }}</p>
                            </div>
                            <div class="text-right">
                                @if($assessment->nilai_pengetahuan)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $assessment->nilai_pengetahuan }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Belum ada nilai
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    @if($assessments->count() > 10)
                        <p class="text-sm text-gray-500 text-center">Dan {{ $assessments->count() - 10 }} mata pelajaran lainnya...</p>
                    @endif
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Belum Ada Penilaian</h3>
                    <p class="mt-1 text-sm text-gray-500">Penilaian mata pelajaran belum tersedia.</p>
                </div>
            @endif
        </div>

        <!-- Pertumbuhan Detail -->
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                </svg>
                Perkembangan & Pertumbuhan
            </h4>
            @if($growthRecords->count() > 0)
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($growthRecords->take(8) as $growth)
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="font-medium text-gray-900">{{ $growth->aspek->nama_aspek ?? 'Aspek Perkembangan' }}</p>
                            @if($growth->deskripsi)
                                <p class="text-sm text-gray-600 mt-1">{{ Str::limit($growth->deskripsi, 100) }}</p>
                            @endif
                            @if($growth->nilai)
                                <div class="mt-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        {{ $growth->nilai }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                    @if($growthRecords->count() > 8)
                        <p class="text-sm text-gray-500 text-center">Dan {{ $growthRecords->count() - 8 }} aspek lainnya...</p>
                    @endif
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Belum Ada Data Pertumbuhan</h3>
                    <p class="mt-1 text-sm text-gray-500">Data perkembangan dan pertumbuhan belum tersedia.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Kehadiran Summary -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Rekap Kehadiran
        </h4>
        @if($attendanceRecords->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @php
                    $totalHadir = $attendanceRecords->sum('hadir');
                    $totalSakit = $attendanceRecords->sum('sakit');
                    $totalIzin = $attendanceRecords->sum('izin');
                    $totalAlfa = $attendanceRecords->sum('alfa');
                    $totalHari = $totalHadir + $totalSakit + $totalIzin + $totalAlfa;
                @endphp
                
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <p class="text-2xl font-bold text-green-600">{{ $totalHadir }}</p>
                    <p class="text-sm text-green-700">Hadir</p>
                    @if($totalHari > 0)
                        <p class="text-xs text-green-600">{{ number_format(($totalHadir / $totalHari) * 100, 1) }}%</p>
                    @endif
                </div>
                
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <p class="text-2xl font-bold text-blue-600">{{ $totalSakit }}</p>
                    <p class="text-sm text-blue-700">Sakit</p>
                    @if($totalHari > 0)
                        <p class="text-xs text-blue-600">{{ number_format(($totalSakit / $totalHari) * 100, 1) }}%</p>
                    @endif
                </div>
                
                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                    <p class="text-2xl font-bold text-yellow-600">{{ $totalIzin }}</p>
                    <p class="text-sm text-yellow-700">Izin</p>
                    @if($totalHari > 0)
                        <p class="text-xs text-yellow-600">{{ number_format(($totalIzin / $totalHari) * 100, 1) }}%</p>
                    @endif
                </div>
                
                <div class="text-center p-4 bg-red-50 rounded-lg">
                    <p class="text-2xl font-bold text-red-600">{{ $totalAlfa }}</p>
                    <p class="text-sm text-red-700">Alfa</p>
                    @if($totalHari > 0)
                        <p class="text-xs text-red-600">{{ number_format(($totalAlfa / $totalHari) * 100, 1) }}%</p>
                    @endif
                </div>
            </div>
            
            @if($totalHari > 0)
                <div class="mt-4 text-center">
                    <p class="text-lg font-semibold text-gray-900">
                        Total Hari Efektif: <span class="text-blue-600">{{ $totalHari }}</span>
                    </p>
                    <p class="text-sm text-gray-600">
                        Persentase Kehadiran: 
                        <span class="font-semibold {{ $totalHadir/$totalHari >= 0.8 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format(($totalHadir / $totalHari) * 100, 1) }}%
                        </span>
                    </p>
                </div>
            @endif
        @else
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Belum Ada Data Kehadiran</h3>
                <p class="mt-1 text-sm text-gray-500">Data kehadiran belum tersedia.</p>
            </div>
        @endif
    </div>
</div>