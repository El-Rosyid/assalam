<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raport Content - {{ $siswa->nama_lengkap }}</title>
    <style>
        @page {
            margin: 2cm;
            size: A4;
        }
        
        body {
            font-family: "DejaVu Sans", sans-serif;
            margin: 0;
            padding: 0;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
        }
        
        .section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
        }
        
        .student-info {
            margin-bottom: 15px;
        }
        
        .student-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .student-info td {
            padding: 3px 0;
            vertical-align: top;
        }
        
        .student-info td:first-child {
            width: 120px;
        }
        
        .student-info td:nth-child(2) {
            width: 15px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            vertical-align: top;
            font-size: 10pt;
        }
        
        .data-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        .data-table td.center {
            text-align: center;
        }
        
        .no-data {
            font-style: italic;
            color: #666;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    <!-- Student Information -->
    <div class="section">
        <div class="section-title">Data Siswa</div>
        <div class="student-info">
            <table>
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
            </table>
        </div>
    </div>

    <!-- Assessment Section -->
    <div class="section">
        <div class="section-title">Penilaian</div>
        @if($assessmentDetails->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 30px;">No</th>
                        <th style="width: 180px;">Aspek Penilaian</th>
                        <th style="width: 60px;">Rating</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assessmentDetails as $index => $detail)
                        <tr>
                            <td class="center">{{ $index + 1 }}</td>
                            <td>{{ $detail->assessmentVariable->name ?? 'Tidak diketahui' }}</td>
                            <td class="center">{{ $detail->rating ?? '-' }}</td>
                            <td>{{ $detail->description ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="no-data">Belum ada data penilaian.</p>
        @endif
    </div>

    <!-- Growth Section -->
    <div class="section">
        <div class="section-title">Pertumbuhan</div>
        @if($growthRecords->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Bulan</th>
                        <th style="width: 70px;">Berat (kg)</th>
                        <th style="width: 70px;">Tinggi (cm)</th>
                        <th style="width: 90px;">L. Kepala (cm)</th>
                        <th style="width: 90px;">L. Lengan (cm)</th>
                        <th>BMI</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($growthRecords as $record)
                        <tr>
                            <td class="center">{{ $record->bulan_tahun }}</td>
                            <td class="center">{{ $record->berat_badan ?? '-' }}</td>
                            <td class="center">{{ $record->tinggi_badan ?? '-' }}</td>
                            <td class="center">{{ $record->lingkar_kepala ?? '-' }}</td>
                            <td class="center">{{ $record->lingkar_lengan ?? '-' }}</td>
                            <td class="center">{{ $record->bmi ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="no-data">Belum ada data pertumbuhan.</p>
        @endif
    </div>

    <!-- Attendance Section -->
    <div class="section">
        <div class="section-title">Kehadiran</div>
        @if($attendance)
            <div class="student-info">
                <table>
                    <tr>
                        <td>Alfa</td>
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
            </div>
        @else
            <p class="no-data">Belum ada data kehadiran.</p>
        @endif
    </div>
</body>
</html>