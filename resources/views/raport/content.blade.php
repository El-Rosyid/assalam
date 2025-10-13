@php
    // Get all data needed for raport
    $sekolah = $sekolah ?? \App\Models\sekolah::first();
    $kelasInfo = $kelasInfo ?? \App\Models\data_kelas::find($siswa->kelas);
    $waliKelasInfo = $waliKelasInfo ?? ($kelasInfo ? \App\Models\data_guru::find($kelasInfo->walikelas_id) : null);
    
    // Get assessment data if not provided
    $assessmentDetails = $assessmentDetails ?? \App\Models\student_assessment_detail::whereHas('studentAssessment', function($query) use ($siswa) {
            $query->where('data_siswa_id', $siswa->id);
        })
        ->with('assessmentVariable')
        ->whereNotNull('rating')
        ->orderBy('created_at', 'desc')
        ->get();
    
    // Get growth records if not provided
    $growthRecords = $growthRecords ?? \App\Models\GrowthRecord::where('data_siswa_id', $siswa->id)
        ->orderBy('month', 'desc')
        ->take(6)
        ->get();
    
    // Get attendance record if not provided
    $attendance = $attendance ?? \App\Models\AttendanceRecord::where('data_siswa_id', $siswa->id)->first();
@endphp

<!-- Header Sekolah -->
<div class="header">
    @if($sekolah)
        <h1>{{ $sekolah->nama_sekolah }}</h1>
        @if($sekolah->alamat)
            <p>{{ $sekolah->alamat }}</p>
        @endif
        @if($sekolah->no_telp)
            <p>Telp: {{ $sekolah->no_telp }}</p>
        @endif
    @endif
</div>

<!-- Title -->
<div class="title">RAPORT SISWA</div>

<!-- Data Siswa -->
<div class="section">
    <div class="section-title">Data Siswa</div>
    <table class="info-table">
        <tr>
            <td>Nama Lengkap</td>
            <td>:</td>
            <td>{{ $siswa->nama_lengkap }}</td>
        </tr>
        <tr>
            <td>NIS</td>
            <td>:</td>
            <td>{{ $siswa->nis }}</td>
        </tr>
        <tr>
            <td>Kelas</td>
            <td>:</td>
            <td>{{ $kelasInfo->nama_kelas ?? '-' }}</td>
        </tr>
        <tr>
            <td>Wali Kelas</td>
            <td>:</td>
            <td>{{ $waliKelasInfo->nama_lengkap ?? '-' }}</td>
        </tr>
    </table>
</div>

<!-- Penilaian -->
<div class="section assessment-section">
    <div class="section-title">Penilaian</div>
    @if($assessmentDetails->count() > 0)
        <table class="data-table assessment-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Aspek Penilaian</th>
                    <th>Rating</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assessmentDetails as $index => $detail)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $detail->assessmentVariable->name ?? 'Tidak diketahui' }}</td>
                        <td>{{ $detail->rating ?? '-' }}</td>
                        <td>{{ $detail->description ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Belum ada data penilaian.</p>
    @endif
</div>

<!-- Pertumbuhan -->
<div class="section growth-section" style="margin-top: 40px;">
    <div class="section-title">Pertumbuhan</div>
    @if($growthRecords->count() > 0)
        <table class="data-table growth-table">
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Berat (kg)</th>
                    <th>Tinggi (cm)</th>
                    <th>L. Kepala (cm)</th>
                    <th>L. Lengan (cm)</th>
                    <th>BMI</th>
                </tr>
            </thead>
            <tbody>
                @foreach($growthRecords as $record)
                    <tr>
                        <td class="text-center">{{ $record->bulan_tahun }}</td>
                        <td class="text-center">{{ $record->berat_badan ?? '-' }}</td>
                        <td class="text-center">{{ $record->tinggi_badan ?? '-' }}</td>
                        <td class="text-center">{{ $record->lingkar_kepala ?? '-' }}</td>
                        <td class="text-center">{{ $record->lingkar_lengan ?? '-' }}</td>
                        <td class="text-center">{{ $record->bmi ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Belum ada data pertumbuhan.</p>
    @endif
</div>

<!-- Kehadiran -->
<div class="section">
    <div class="section-title">Kehadiran</div>
    @if($attendance)
        <table class="info-table">
            <tr>
                <td>Alfa (tanpa keterangan)</td>
                <td>:</td>
                <td>{{ $attendance->alfa ?? 0 }} hari</td>
            </tr>
            <tr>
                <td>Ijin</td>
                <td>:</td>
                <td>{{ $attendance->ijin ?? 0 }} hari</td>
            </tr>
            <tr>
                <td>Sakit</td>
                <td>:</td>
                <td>{{ $attendance->sakit ?? 0 }} hari</td>
            </tr>
            <tr style="font-weight: bold;">
                <td>Total Absen</td>
                <td>:</td>
                <td>{{ ($attendance->alfa ?? 0) + ($attendance->ijin ?? 0) + ($attendance->sakit ?? 0) }} hari</td>
            </tr>
        </table>
    @else
        <p class="no-data">Belum ada data kehadiran.</p>
    @endif
</div>

<!-- Refleksi Orang Tua -->
<div class="section" style="margin-top: 50px;">
    <div class="section-title">Refleksi Orang Tua</div>
    <p style="font-size: 12pt; margin-bottom: 5mm;">Catatan dan masukan dari orang tua:</p>
    <div class="reflection-box"></div>
</div>

<!-- Tanda Tangan -->
<div class="signature-section" style="margin-top: 60px; padding-top: 30px; border-top: 2px solid #f0f0f0;">
    <div class="signature-box">
        <p>Wali Kelas</p>
        <div class="signature-line"></div>
        <p class="signature-text">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</p>
    </div>
    <div class="signature-box">
        <p>Orang Tua/Wali</p>
        <div class="signature-line"></div>
        <p class="signature-text">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</p>
    </div>
</div>

<!-- Tanggal -->
<div class="date">
    Tanggal: {{ date('d F Y') }}
</div>