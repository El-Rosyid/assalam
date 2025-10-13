<style>
    /* Shared raport base styles */
    .raport-base {
        font-family: 'Helvetica', Arial, sans-serif;
        font-size: 12pt;
        line-height: 1.4;
        color: #000;
    }
    
    @media screen {
        .print-modal-content {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 30mm;
            max-height: 70vh;
            overflow-y: auto;
        }
    }
    
    @media print {
        body * {
            visibility: hidden;
        }
        
        .print-modal-content, .print-modal-content * {
            visibility: visible;
        }
        
        .print-modal-content {
            position: absolute;
            left: 0;
            top: 0;
            width: 210mm;
            margin: 0;
            padding: 30mm;
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            color: #000;
            box-shadow: none;
            max-height: none;
            overflow: visible;
        }
        
        @page {
            size: A4;
            margin: 30mm;
        }
        
        .page-break {
            page-break-before: always;
            margin-top: 0;
            padding-top: 0;
        }
        
        .new-page {
            page-break-before: always;
        }
        
        /* Remove page-break-inside: avoid untuk memungkinkan pemecahan halaman */
        .section {
            page-break-inside: auto;
        }
        
        /* Khusus untuk section penilaian yang panjang */
        .assessment-section {
            page-break-inside: auto;
        }
        
        .assessment-table {
            page-break-inside: auto;
        }
        
        .assessment-table tbody tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        
        /* Pastikan signature section selalu di halaman terakhir */
        .signature-section {
            page-break-before: auto;
            page-break-inside: avoid;
        }
    }
    
    .header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #000;
        padding-bottom: 20px;
    }
    
    .header h1 {
        font-size: 18pt;
        font-weight: bold;
        margin: 0;
        text-transform: uppercase;
    }
    
    .header p {
        margin: 5px 0;
        font-size: 14pt;
    }
    
    .title {
        text-align: center;
        font-size: 16pt;
        font-weight: bold;
        margin: 20px 0;
        text-transform: uppercase;
    }
    
    .section {
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .section:last-of-type {
        border-bottom: none;
    }
    
    @media print {
        .section {
            border-bottom: none !important;
            margin-bottom: 15mm;
            padding-bottom: 0;
        }
        
        .signature-section {
            border-top: none !important;
            margin-top: 30mm !important;
            padding-top: 0 !important;
        }
        
        /* Specific spacing untuk data yang panjang */
        .assessment-section {
            margin-bottom: 10mm;
        }
        
        .growth-section {
            margin-bottom: 10mm;
        }
        
        /* Force break untuk section tertentu jika diperlukan */
        .force-page-break {
            page-break-before: always;
        }
    }
    
    .section-title {
        font-size: 14pt;
        font-weight: bold;
        margin-bottom: 10px;
        text-transform: uppercase;
    }
    
    .info-table {
        width: 100%;
        margin-bottom: 15px;
        border-collapse: collapse;
    }
    
    .info-table td {
        padding: 3px 0;
        vertical-align: top;
        font-size: 12pt;
    }
    
    .info-table td:first-child {
        width: 40mm;
        font-weight: 500;
    }
    
    .info-table td:nth-child(2) {
        width: 10mm;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    
    .data-table th,
    .data-table td {
        border: 1px solid #333;
        padding: 3mm;
        text-align: left;
        font-size: 11pt;
    }
    
    .data-table th {
        background-color: #e6e6e6;
        font-weight: bold;
        text-align: center;
        height: 10mm;
    }
    
    .data-table td {
        height: 8mm;
    }
    
    .text-center {
        text-align: center;
    }
    
    .reflection-box {
        border: 1px solid #333;
        height: 40mm;
        margin: 10mm 0;
        padding: 0;
        background-color: white;
    }
    
    .signature-section {
        margin-top: 30mm;
        display: flex;
        justify-content: space-between;
        page-break-inside: avoid;
    }
    
    .signature-box {
        text-align: center;
        font-size: 12pt;
        width: 60mm;
    }
    
    .signature-line {
        margin-top: 20mm;
        border-bottom: 1px solid #333;
        width: 100%;
        height: 1px;
    }
    
    .signature-text {
        margin-top: 2mm;
    }
    
    .date {
        text-align: right;
        margin-top: 15mm;
        font-size: 11pt;
        color: #333;
    }
    
    .no-data {
        font-style: italic;
        color: #666;
        font-size: 12pt;
    }
    
    /* Table width adjustments for A4 with 3cm margins (150mm content width) */
    .assessment-table th:nth-child(1), .assessment-table td:nth-child(1) { width: 12mm; }
    .assessment-table th:nth-child(2), .assessment-table td:nth-child(2) { width: 55mm; }
    .assessment-table th:nth-child(3), .assessment-table td:nth-child(3) { width: 25mm; }
    .assessment-table th:nth-child(4), .assessment-table td:nth-child(4) { width: 58mm; }
    
    .growth-table th:nth-child(1), .growth-table td:nth-child(1) { width: 25mm; }
    .growth-table th:nth-child(2), .growth-table td:nth-child(2) { width: 20mm; }
    .growth-table th:nth-child(3), .growth-table td:nth-child(3) { width: 20mm; }
    .growth-table th:nth-child(4), .growth-table td:nth-child(4) { width: 25mm; }
    .growth-table th:nth-child(5), .growth-table td:nth-child(5) { width: 25mm; }
    .growth-table th:nth-child(6), .growth-table td:nth-child(6) { width: 35mm; }
</style>

@php
    // Get all data needed for print modal
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

<div class="print-modal-content raport-base">
    @include('raport.full-template', [
        'siswa' => $siswa,
        'sekolah' => $sekolah,
        'kelasInfo' => $kelasInfo,
        'waliKelasInfo' => $waliKelasInfo,
        'assessmentDetails' => $assessmentDetails,
        'growthRecords' => $growthRecords,
        'attendance' => $attendance
    ])
</div>

<script>
    // Auto print ketika modal terbuka
    document.addEventListener('DOMContentLoaded', function() {
        // Delay untuk memastikan modal fully rendered
        setTimeout(function() {
            window.print();
        }, 1000);
    });
    
    // Juga trigger saat modal content loaded
    window.addEventListener('load', function() {
        setTimeout(function() {
            window.print();
        }, 500);
    });
</script>