<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        /* PDF Specific Styles */
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
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
            margin-bottom: 20mm;
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
        }
        
        .signature-section {
            margin-top: 40mm;
            width: 100%;
        }
        
        .signature-box {
            display: inline-block;
            text-align: center;
            font-size: 12pt;
            width: 45%;
            vertical-align: top;
        }
        
        .signature-line {
            margin-top: 20mm;
            border-bottom: 1px solid #333;
            width: 80%;
            height: 1px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .signature-text {
            margin-top: 2mm;
        }
        
        .date {
            text-align: right;
            margin-top: 15mm;
            font-size: 11pt;
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
</head>
<body>
    @include('raport.content', [
        'siswa' => $siswa,
        'sekolah' => $sekolah,
        'kelasInfo' => $kelasInfo,
        'waliKelasInfo' => $waliKelasInfo,
        'assessmentDetails' => $assessmentDetails,
        'growthRecords' => $growthRecords,
        'attendance' => $attendance
    ])
</body>
</html>