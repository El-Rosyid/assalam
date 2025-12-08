<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Raport - {{ $siswa->nama_lengkap ?? 'Siswa' }}</title>
    <style>
        @page {
            size: A4;
            margin: 33mm;
        }
        body {
            font-family: "DejaVu Sans", sans-serif;
            margin: 0;
            color: #000;
            font-size: 12pt;
            line-height: 1.5;
        }

        .cover {
            text-align: center;
        }

        /* Logo */
        .logo {
            margin: 5px auto 15px auto;
            width: 90px;
            height: 90px;
            object-fit: contain;
        }

        /* Title */
        .title {
            font-weight: bold;
            font-size: 12pt;
            letter-spacing: 1px;
            margin-bottom: 25px;
        }

        /* Info grid */
        .info-table {
            width: 85%;
            margin: 0 auto 20px auto;
            text-align: left;
            font-size: 11pt;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .info-label {
            
            width: 180px;
        }
        .info-colon {
            width: 15px;
            text-align: center;
        }
        .info-value {
            white-space: nowrap;
        }

        /* Student name box */
        .student-name-box {
            width: 50%;
            margin: 4px auto 6px auto;
            padding: 6px 10px;
            border: 2px solid #000;
            text-align: center;
        }
        .student-name-box .label {
            font-weight: bold;
            margin-bottom: 4px;
        }
        .student-name-box .name {
            font-weight: bold;
            font-size: 12pt;
        }

        .student-id {
            margin-top: 6px;
            font-weight: bold;
            font-size: 11pt;
        }

        /* Footer school */
        .footer-school {
            margin-top: 25px;
            font-weight: bold;
            font-size: 12pt;
        }
        .label-bold {
            font-size: 10t;
            font-weight: bold;
        }

        /* Helpers */
        .muted { opacity: .95; }

        /* New header page styles */
        .page-break { page-break-after: always; }
        .page-header {
    font-size: 9pt;
    position: relative;
    margin-bottom: 0;
    padding-bottom: 8px;
    border-bottom: 1px solid #000;
    line-height: 1.2;
    height: 35px;
}

/* Kolom kiri */
.header-left {
    position: absolute;
    left: 0;
    top: 0;
}

/* Kolom kanan */
.header-right {
    position: absolute;
    right: 0;
    top: 0;
}

/* Pastikan tabel tidak melebihi lebar */
.header-table {
    border-collapse: collapse;
    width: auto;
}

.header-table td {
    padding: 1px 3px;
    vertical-align: top;
    font-size: 9pt;
    line-height: 1.2;
}

.hl-label {
    width: 45px;
    font-weight: bold;
    white-space: nowrap;
}

.hl-colon {
    width: 6px;
    text-align: center;
}

.hl-value {
    white-space: normal;
    font-weight: bold;
}

/* Assessment table styles */
.assessment-section {
    margin-top: 0;
}

.assessment-title {
    font-weight: bold;
    font-size: 11pt;
    margin-bottom: 8px;
    padding: 6px 10px;
    background-color: #f0f0f0;
    border-left: 4px solid #000;
}

.assessment-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
    font-size: 10pt;
    page-break-inside: avoid;
}

.assessment-table td {
    border: 2px solid #000;
    padding: 8px;
    vertical-align: top;
}

.assessment-photo-section {
    width: 25%;
    text-align: center;
    vertical-align: middle;
    padding: 5px;
    max-height: 200px;
}

.photo-grid {
    display: block;
    margin: 0 auto;
    text-align: center;
}

.photo-grid img {
    max-width: 60px;
    max-height: 60px;
    width: 60px;
    height: 60px;
    object-fit: contain;
    border: 1px solid #ccc;
    margin: 1px;
    display: inline-block;
    vertical-align: middle;
}

.photo-single {
    max-width: 120px;
    max-height: 180px;
    object-fit: contain;
    border: 1px solid #ccc;
    display: block;
    margin: 0 auto;
}

.assessment-description {
    line-height: 1.4;
    font-size: 10pt;
    text-align: justify;
    max-height: 250px;
    overflow-y: auto;
    word-wrap: break-word;
    word-break: break-word;
}

.assessment-description strong {
    display: block;
    margin-bottom: 4px;
    font-size: 11pt;
}

/* Responsive assessment styles */
@media print {
    .assessment-table {
        page-break-inside: avoid;
    }
    
    .assessment-description {
        orphans: 3;
        widows: 3;
    }
}
    </style>
</head>
<body>
    <div class="cover">
        <!-- Emblem / Logo (optional) -->
        @php
            $logoPath = $sekolah->logo_sekolah ?? null;
            $logoSrc = null;
            if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                // DomPDF requires an absolute file path for local images
                $logoSrc = storage_path('app/public/' . $logoPath);
            }
        @endphp
        @if(!empty($logoSrc))
            <img class="logo" src="{{ $logoSrc }}" alt="Logo Sekolah">
        @endif

        <!-- Title -->
        <div class="title">LAPORAN CAPAIAN PERKEMBANGAN ANAK DIDIK</div>

        <!-- School and location info -->
        <table class="info-table">
            <tr>
                <td class="info-label">NAMA SEKOLAH</td>
                <td class="info-colon">:</td>
                <td class="info-value">{{ $sekolah->nama_sekolah ?? 'TK ABA ASSALAM' }}</td>
            </tr>
            <tr>
                <td class="info-label">NPSN</td>
                <td class="info-colon">:</td>
                <td class="info-value">{{ $sekolah->npsn ?? '20349308' }}</td>
            </tr>
            <tr>
                <td class="info-label">ALAMAT</td>
                <td class="info-colon">:</td>
                <td class="info-value">{{ $sekolah->alamat ?? 'DUKUH SANGANIJAYA' }}</td>
            </tr>
            <tr>
                <td class="info-label">DESA</td>
                <td class="info-colon">:</td>
                <td class="info-value">{{ $sekolah->kelurahan ?? $sekolah->desa ?? 'MANGGIS' }}</td>
            </tr>
            <tr>
                <td class="info-label">KECAMATAN</td>
                <td class="info-colon">:</td>
                <td class="info-value">{{ $sekolah->kecamatan ?? 'SIRAMPOG' }}</td>
            </tr>
            <tr>
                <td class="info-label">KOTA/KABUPATEN</td>
                <td class="info-colon">:</td>
                <td class="info-value">{{ $sekolah->kabupaten ?? 'BREBES' }}</td>
            </tr>
            <tr>
                <td class="info-label">PROVINSI</td>
                <td class="info-colon">:</td>
                <td class="info-value">{{ $sekolah->provinsi ?? 'JAWA TENGAH' }}</td>
            </tr>
        </table>

        <!-- Highlighted student name box -->
        <div class="label-bold">NAMA MURID</div>
        <div class="student-name-box">            
            <div class="name">{{ strtoupper($siswa->nama_lengkap ?? '-') }}</div>
        </div>
        <div class="student-id">NOMOR INDUK / NISN: {{ ($siswa->nis ?? ($siswa->nisn ?? '-')) }}</div>

        <!-- Bottom school text -->
        <div class="footer-school">
            {{ $sekolah->nama_sekolah ?? 'TK ABA ASSALAM' }}<br>
            {{ ($sekolah->kabupaten ?? 'BREBES') }}
        </div>
    </div>
<div>
    <div class="page-break"></div>

<div class="page-header">
    <div class="header-left">
        <table class="header-table">
            <tr>
                <td class="hl-label">Nama</td>
                <td class="hl-colon">:</td>
                <td class="hl-value">{{ $siswa->nama_lengkap ?? '-' }}</td>
            </tr>
            <tr>
                <td class="hl-label">NIP</td>
                <td class="hl-colon">:</td>
                <td class="hl-value">{{ $siswa->nis ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="header-right">
        <table class="header-table">
            <tr>
                <td class="hl-label">Kelas</td>
                <td class="hl-colon">:</td>
                <td class="hl-value">{{ $kelasInfo->nama_kelas ?? ($siswa->kelasInfo->nama_kelas ?? '-') }}</td>
            </tr>
            <tr>
                <td class="hl-label">Semester</td>
                <td class="hl-colon">:</td>
                <td class="hl-value">{{ $semester ?? '2 (Genap)' }}</td>
            </tr>
        </table>
    </div>
</div>

<div style="margin-bottom: 12px;"></div>

<div class="assessment-section">
        
    @if(!empty($assessmentVariables) && $assessmentVariables->count() > 0)
        @foreach($assessmentVariables as $index => $variable)
            @php
                // Cari detail assessment untuk variabel ini
                $detail = null;
                if (!empty($assessments) && $assessments->count() > 0) {
                    foreach ($assessments as $assessment) {
                        $foundDetail = $assessment->details->firstWhere('variabel_id', $variable->id);
                        if ($foundDetail) {
                            $detail = $foundDetail;
                            break;
                        }
                    }
                }
                
                // Logika page break:
                // - Halaman 1: max 2 assessment (index 0-1)
                // - Halaman 2+: max 3 assessment per halaman
                $itemsOnFirstPage = 2;
                $itemsPerPage = 3;
                
                // Tentukan kapan page break
                $shouldBreak = false;
                if ($index + 1 < $assessmentVariables->count()) {
                    if ($index + 1 <= $itemsOnFirstPage) {
                        // Di halaman 1, break setelah 2 items
                        if (($index + 1) % $itemsOnFirstPage === 0) {
                            $shouldBreak = true;
                        }
                    } else {
                        // Di halaman 2+, break setiap 3 items
                        $positionAfterFirstPage = ($index + 1 - $itemsOnFirstPage) % $itemsPerPage;
                        if ($positionAfterFirstPage === 0) {
                            $shouldBreak = true;
                        }
                    }
                }
            @endphp
            
            <table class="assessment-table">
                <tr>
                    <td colspan="2" style="font-weight: bold; background-color: #f9f9f9; font-size: 9pt; padding: 5px 8px;">
                        {{ $variable->name ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td class="assessment-photo-section">
                        @if($detail)
                            @php
                                $photos = is_array($detail->images) ? $detail->images : (is_string($detail->images) ? json_decode($detail->images, true) : []);
                                $photoCount = count($photos);
                                $photoSources = [];
                                
                                // Get up to 3 photos
                                foreach (array_slice($photos, 0, 3) as $photo) {
                                    if ($photo) {
                                        if (\Storage::disk('public')->exists($photo)) {
                                            $photoSources[] = storage_path('app/public/' . $photo);
                                        } elseif (file_exists(public_path('storage/' . $photo))) {
                                            $photoSources[] = public_path('storage/' . $photo);
                                        } elseif (file_exists(storage_path('app/public/' . $photo))) {
                                            $photoSources[] = storage_path('app/public/' . $photo);
                                        }
                                    }
                                }
                            @endphp
                            
                            @if(count($photoSources) > 0)
                                @if(count($photoSources) == 1)
                                    {{-- Single photo - larger --}}
                                    <img src="{{ $photoSources[0] }}" alt="Foto Kegiatan" class="photo-single">
                                @else
                                    {{-- Multiple photos - grid --}}
                                    <div class="photo-grid">
                                        @foreach($photoSources as $photoSrc)
                                            @if(file_exists($photoSrc))
                                                <img src="{{ $photoSrc }}" alt="Foto Kegiatan {{ $loop->iteration }}">
                                                @if($loop->iteration == 2)
                                                    <br>
                                                @endif
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                <div style="padding: 40px 20px; color: #999; font-size: 10pt; border: 2px dashed #ccc;">
                                    Belum ada foto
                                </div>
                            @endif
                        @else
                            <div style="padding: 40px 20px; color: #999; font-size: 10pt; border: 2px dashed #ccc;">
                                Belum ada foto
                            </div>
                        @endif
                    </td>
                    <td class="assessment-description" style="line-height: 1.4;">
                        @if($detail)
                            @php
                                $description = $detail->description ?? 'Belum ada deskripsi.';
                                $charCount = strlen($description);
                            @endphp
                            @if($charCount > 300)
                                <div style="font-size: 9pt; line-height: 1.3;">{{ $description }}</div>
                            @else
                                <div style="font-size: 10pt; line-height: 1.4;">{{ $description }}</div>
                            @endif
                        @else
                            Belum ada penilaian untuk aspek ini.
                        @endif
                    </td>
                </tr>
            </table>
            
            {{-- Page break dengan logika: 2 di halaman 1, 3 di halaman berikutnya --}}
            @if($shouldBreak)
                <div class="page-break"></div>
                <div style="margin-top: 30px;"></div>
            @endif
        @endforeach
    @else
        <div style="margin-top: 10px; padding: 20px; text-align: center; color: #666; border: 1px dashed #ccc;">
            Belum ada variabel penilaian yang tersedia.
        </div>
    @endif
</div>

</div>

<div class="page-break"></div>

<!-- Halaman tambahan: Pertumbuhan, Kehadiran, Tanda Tangan, Komentar Orang Tua -->
<div style="font-size: 10pt;">
    
    <div style="margin-top: 12px;"></div>

    <!-- Layout 2 Kolom: Pertumbuhan (kiri) dan Kehadiran (kanan) -->
    @php
        $latestGrowth = $siswa->growthRecords()->orderBy('month', 'desc')->first();
        $attendanceRecords = $siswa->attendanceRecords ?? collect();
        $totalSakit = $attendanceRecords->sum('sakit');
        $totalIzin = $attendanceRecords->sum('izin');
    @endphp
    
    <table style="width: 100%; border-collapse: collapse; border: 2px solid #000; margin-bottom: 20px; line-height: 1;">
        <!-- Header Row -->
        <tr>
            <th style="border-right: 2px solid #000; border-bottom: 2px solid #000; padding: 5px; font-weight: bold; background: #f9f9f9; text-align: center; width: 50%; line-height: 1;">
                PERTUMBUHAN
            </th>
            <th style="border-bottom: 2px solid #000; padding: 5px; font-weight: bold; background: #f9f9f9; text-align: center; width: 50%; line-height: 1;">
                KEHADIRAN
            </th>
        </tr>
        
        <!-- Data Rows -->
        <tr>
            <td style="border-right: 2px solid #000; padding: 3px; line-height: 1;">
                Berat Badan : {{ $latestGrowth->weight ?? '-' }} kg
            </td>
            <td style="padding: 5px; line-height: 1;">
                Sakit : {{ $totalSakit }} hari
            </td>
        </tr>
        
        <tr>
            <td style="border-right: 2px solid #000; padding: 3px; line-height: 1;">
                Tinggi Badan : {{ $latestGrowth->height ?? '-' }} cm
            </td>
            <td style="padding: 5px; line-height: 1;">
                Izin : {{ $totalIzin }} hari
            </td>
        </tr>
    </table>

    <table style="width:100%; border-collapse: collapse; margin-bottom: 18px; line-height: 1;">
        <tr>
            <td colspan="2" style="text-align: right; padding-bottom: 8px; line-height: 1;">
                BREBES, {{ $academicYear->tanggal_penerimaan_raport ? \Carbon\Carbon::parse($academicYear->tanggal_penerimaan_raport)->translatedFormat('d F Y') : now()->translatedFormat('d F Y') }}
            </td>
        </tr>
        <tr>
            <td style="width: 50%; padding: 5px; vertical-align: top; line-height: 1;">
                Mengetahui,<br>
                Kepala Sekolah<br><br><br><br><br>
                <u>{{ $kepalaSekolah->nama ?? ($sekolah->kepala_sekolah ?? 'Nama Kepala Sekolah') }}</u><br>                
            </td>
            <td style="width: 50%; padding: 5px; vertical-align: top; text-align: center; line-height: 1;">
                Guru Kelas,<br><br><br><br><br><br>
                @php
                    $guruName = '................................';
                    // Gunakan wali kelas langsung
                    if (!empty($waliKelas->nama_lengkap)) {
                        $guruName = $waliKelas->nama_lengkap;
                    } elseif (!empty($waliKelas->nama)) {
                        $guruName = $waliKelas->nama;
                    } elseif (!empty($kelasInfo->waliKelas->nama_lengkap)) {
                        $guruName = $kelasInfo->waliKelas->nama_lengkap;
                    } elseif (!empty($kelasInfo->waliKelas->nama)) {
                        $guruName = $kelasInfo->waliKelas->nama;
                    }
                @endphp
                <u>{{ $guruName }}</u>
            </td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; border: 2px solid #000; margin-bottom: 8px; line-height: 1;">
        <tr>
            <th style="border-bottom: 2px solid #000; padding: 6px 8px; font-weight: bold; background: #f9f9f9; text-align: left; line-height: 1;">
                REFLEKSI ORANG TUA
            </th>
        </tr>
        <tr>
            <td style="padding: 8px; min-height: 180px; line-height: 18px; vertical-align: top;">
                <div style="color:#999;">
                    ...............................................................................................................................<br>
                    ...............................................................................................................................<br>
                    ...............................................................................................................................<br>
                    ...............................................................................................................................<br>
                    ...............................................................................................................................<br>
                    ...............................................................................................................................<br>
                    ...............................................................................................................................
                </div>
            </td>
        </tr>
    </table>

    <div style="width: 150px; text-align: center; line-height: 1.2; float: right;">
        Paraf Orang Tua<br><br><br>
        .............................
    </div>
</div>

</body>
</html>
