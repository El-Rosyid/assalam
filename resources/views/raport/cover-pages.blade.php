@php
    // Ensure all variables are available with fallbacks
    $sekolah = $sekolah ?? \App\Models\sekolah::first();
    $kelasInfo = $kelasInfo ?? \App\Models\data_kelas::find($siswa->kelas);
    $waliKelasInfo = $waliKelasInfo ?? ($kelasInfo ? \App\Models\data_guru::find($kelasInfo->walikelas_id) : null);
@endphp

{{-- Halaman Cover/Sampul --}}
<div class="cover-page">
    <div class="cover-header">
        @if($sekolah)
            <h1 class="school-name">{{ strtoupper($sekolah->nama_sekolah) }}</h1>
            @if($sekolah->alamat)
                <p class="school-address">{{ $sekolah->alamat }}</p>
            @endif
            @if($sekolah->no_telp)
                <p class="school-phone">Telp: {{ $sekolah->no_telp }}</p>
            @endif
        @endif
    </div>
    
    <div class="cover-title-section">
        <div class="cover-title">LAPORAN HASIL BELAJAR</div>
        <div class="cover-subtitle">RAPORT SISWA</div>
        
        <div class="academic-info">
            <p>TAHUN PELAJARAN {{ date('Y') }}/{{ date('Y') + 1 }}</p>
            <p>SEMESTER {{ date('n') <= 6 ? 'II' : 'I' }}</p>
        </div>
    </div>
    
    <div class="student-info-cover">
        <table class="cover-info-table">
            <tr>
                <td class="label">Nama Siswa</td>
                <td class="separator">:</td>
                <td class="value">{{ $siswa->nama_lengkap }}</td>
            </tr>
            <tr>
                <td class="label">NIS</td>
                <td class="separator">:</td>
                <td class="value">{{ $siswa->nis }}</td>
            </tr>
            <tr>
                <td class="label">Kelas</td>
                <td class="separator">:</td>
                <td class="value">{{ $kelasInfo->nama_kelas ?? '-' }}</td>
            </tr>
        </table>
    </div>
    
    <div class="cover-footer">
        <div class="school-logo">
            {{-- Tempat untuk logo sekolah jika ada --}}
            <div class="logo-placeholder">
                <!-- Logo Sekolah -->
            </div>
        </div>
        <p class="print-date">Dicetak: {{ date('d F Y') }}</p>
    </div>
</div>

{{-- Page Break --}}
<div class="page-break"></div>

{{-- Halaman Kata Pengantar --}}
<div class="preface-page">
    <div class="preface-header">
        <h2>KATA PENGANTAR</h2>
    </div>
    
    <div class="preface-content">
        <p>Puji syukur kehadirat Allah SWT yang telah memberikan rahmat dan hidayah-Nya, sehingga kami dapat menyelesaikan Laporan Hasil Belajar (Raport) untuk semester ini.</p>
        
        <p>Raport ini merupakan gambaran pencapaian hasil belajar siswa selama mengikuti proses pembelajaran di {{ $sekolah->nama_sekolah ?? 'sekolah ini' }}. Laporan ini disusun berdasarkan penilaian yang telah dilakukan secara komprehensif meliputi aspek pengetahuan, keterampilan, dan sikap.</p>
        
        <p>Kami berharap laporan ini dapat memberikan informasi yang jelas kepada orang tua/wali siswa mengenai perkembangan putra-putrinya. Selain itu, laporan ini juga dapat menjadi bahan evaluasi untuk meningkatkan kualitas pembelajaran ke depan.</p>
        
        <div class="note-section">
            <h4>Catatan Penting:</h4>
            <ul>
                <li>Laporan ini menggambarkan hasil belajar siswa pada periode tertentu</li>
                <li>Mohon orang tua/wali untuk mengisi kolom refleksi yang telah disediakan</li>
                <li>Konsultasikan perkembangan anak dengan wali kelas untuk hasil yang optimal</li>
            </ul>
        </div>
        
        <p>Kami mengucapkan terima kasih kepada semua pihak yang telah mendukung proses pembelajaran. Semoga kerja sama yang baik antara sekolah dan orang tua dapat terus terjalin untuk kemajuan pendidikan anak-anak kita.</p>
    </div>
    
    <div class="preface-signature">
        <div class="signature-date">
            {{ $sekolah->alamat ?? 'Kota' }}, {{ date('d F Y') }}
        </div>
        <div class="signature-title">
            <p>Kepala Sekolah</p>
            <div class="signature-space"></div>
            <p class="signature-name">( {{ $sekolah->kepala_sekolah ?? '_________________' }} )</p>
        </div>
    </div>
</div>

{{-- Page Break sebelum content utama --}}
<div class="page-break"></div>

<style>
    /* Cover Page Styles */
    .cover-page {
        text-align: center;
        height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 20mm 0;
    }
    
    .cover-header .school-name {
        font-size: 24pt;
        font-weight: bold;
        margin: 0 0 10mm 0;
        color: #2c3e50;
        letter-spacing: 1pt;
    }
    
    .cover-header .school-address,
    .cover-header .school-phone {
        font-size: 14pt;
        margin: 3mm 0;
        color: #34495e;
    }
    
    .cover-title-section {
        margin: 30mm 0;
    }
    
    .cover-title {
        font-size: 20pt;
        font-weight: bold;
        margin-bottom: 5mm;
        color: #2c3e50;
    }
    
    .cover-subtitle {
        font-size: 18pt;
        font-weight: bold;
        margin-bottom: 15mm;
        color: #34495e;
    }
    
    .academic-info p {
        font-size: 14pt;
        font-weight: bold;
        margin: 5mm 0;
        color: #2c3e50;
    }
    
    .student-info-cover {
        margin: 20mm auto;
        display: inline-block;
    }
    
    .cover-info-table {
        border: 2px solid #2c3e50;
        border-collapse: collapse;
        margin: 0 auto;
        padding: 10mm;
        background-color: #f8f9fa;
    }
    
    .cover-info-table td {
        padding: 5mm 8mm;
        font-size: 14pt;
        border: 1px solid #bdc3c7;
    }
    
    .cover-info-table .label {
        font-weight: bold;
        width: 35mm;
        color: #2c3e50;
    }
    
    .cover-info-table .separator {
        width: 10mm;
        text-align: center;
        font-weight: bold;
    }
    
    .cover-info-table .value {
        font-weight: bold;
        color: #34495e;
        min-width: 60mm;
    }
    
    .cover-footer {
        margin-top: 30mm;
    }
    
    .logo-placeholder {
        width: 60mm;
        height: 60mm;
        border: 2px dashed #bdc3c7;
        margin: 0 auto 10mm auto;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #95a5a6;
        font-size: 10pt;
    }
    
    .print-date {
        font-size: 12pt;
        color: #7f8c8d;
        font-style: italic;
    }
    
    /* Preface Page Styles */
    .preface-page {
        padding: 10mm 0;
        line-height: 1.6;
    }
    
    .preface-header h2 {
        text-align: center;
        font-size: 18pt;
        font-weight: bold;
        margin-bottom: 20mm;
        color: #2c3e50;
        text-transform: uppercase;
        border-bottom: 2px solid #2c3e50;
        padding-bottom: 5mm;
    }
    
    .preface-content p {
        text-align: justify;
        margin-bottom: 8mm;
        font-size: 12pt;
        line-height: 1.8;
    }
    
    .note-section {
        background-color: #f8f9fa;
        padding: 10mm;
        margin: 15mm 0;
        border-left: 4px solid #3498db;
    }
    
    .note-section h4 {
        margin: 0 0 8mm 0;
        color: #2c3e50;
        font-size: 13pt;
    }
    
    .note-section ul {
        margin: 0;
        padding-left: 15mm;
    }
    
    .note-section li {
        margin-bottom: 3mm;
        font-size: 11pt;
    }
    
    .preface-signature {
        margin-top: 40mm;
        text-align: right;
    }
    
    .preface-signature .signature-date {
        font-size: 12pt;
        margin-bottom: 5mm;
    }
    
    .preface-signature .signature-title p {
        font-size: 12pt;
        margin: 5mm 0;
    }
    
    .preface-signature .signature-space {
        height: 20mm;
    }
    
    .preface-signature .signature-name {
        border-bottom: 1px solid #333;
        display: inline-block;
        min-width: 50mm;
        text-align: center;
    }
    
    /* Print specific styles untuk cover pages */
    @media print {
        .cover-page, .preface-page {
            page-break-after: always;
        }
        
        .cover-page {
            height: 257mm; /* A4 height minus 30mm top/bottom margins */
        }
    }
</style>