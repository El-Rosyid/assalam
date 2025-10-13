<?php

namespace App\Services;

use App\Models\data_siswa;
use App\Models\data_kelas;
use App\Models\sekolah;
use App\Models\student_assessment;
use App\Models\student_assessment_detail;
use App\Models\GrowthRecord;
use App\Models\AttendanceRecord;
use TCPDF;

class ReportCardService
{
    public function generateReportCard(data_siswa $siswa): string
    {
        // Create new PDF document with A4 format
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Sistem Sekolah');
        $pdf->SetAuthor('Sistem Sekolah');
        $pdf->SetTitle('Raport - ' . $siswa->nama_lengkap);
        $pdf->SetSubject('Raport Siswa');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins 3cm (30mm) for all sides
        $pdf->SetMargins(30, 30, 30);
        $pdf->SetAutoPageBreak(TRUE, 30);
        
        // Add a page
        $pdf->AddPage();
        
        // Set default font to sans serif (helvetica) 12pt
        $pdf->SetFont('helvetica', '', 12);
        
        // Generate content
        $this->generateHeader($pdf, $siswa);
        $this->generateStudentInfo($pdf, $siswa);
        $this->generateAssessmentSection($pdf, $siswa);
        $this->generateGrowthSection($pdf, $siswa);
        $this->generateAttendanceSection($pdf, $siswa);
        $this->generateReflectionSection($pdf);
        
        // Generate filename
        $filename = 'raport_' . $siswa->nis . '_' . str_replace(' ', '_', $siswa->nama_lengkap) . '.pdf';
        
        // Output PDF
        return $pdf->Output($filename, 'D');
    }
    
    public function generateReportCardFromTemplate(data_siswa $siswa, bool $withCoverPages = true): string
    {
        // Get all data needed
        $sekolah = \App\Models\sekolah::first();
        $kelasInfo = \App\Models\data_kelas::find($siswa->kelas);
        $waliKelasInfo = $kelasInfo ? \App\Models\data_guru::find($kelasInfo->walikelas_id) : null;
        
        // Get assessment data
        $assessmentDetails = \App\Models\student_assessment_detail::whereHas('studentAssessment', function($query) use ($siswa) {
                $query->where('data_siswa_id', $siswa->id);
            })
            ->with('assessmentVariable')
            ->whereNotNull('rating')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get growth records
        $growthRecords = \App\Models\GrowthRecord::where('data_siswa_id', $siswa->id)
            ->orderBy('month', 'desc')
            ->take(6)
            ->get();
        
        // Get attendance record
        $attendance = \App\Models\AttendanceRecord::where('data_siswa_id', $siswa->id)->first();
        
        // Choose template based on cover pages option
        $templateName = $withCoverPages ? 'raport.pdf-full-template' : 'raport.pdf-template';
        
        // Generate HTML from template
        $html = view($templateName, compact(
            'siswa', 'sekolah', 'kelasInfo', 'waliKelasInfo', 
            'assessmentDetails', 'growthRecords', 'attendance'
        ))->render();
        
        // Create PDF from HTML
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Sistem Sekolah');
        $pdf->SetAuthor('Sistem Sekolah');
        $pdf->SetTitle('Raport - ' . $siswa->nama_lengkap);
        $pdf->SetSubject('Raport Siswa');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins 3cm (30mm) for all sides
        $pdf->SetMargins(30, 30, 30);
        $pdf->SetAutoPageBreak(TRUE, 30);
        
        // Add a page
        $pdf->AddPage();
        
        // Write HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Generate filename
        $filename = 'raport_' . $siswa->nis . '_' . str_replace(' ', '_', $siswa->nama_lengkap) . '.pdf';
        
        // Output PDF
        return $pdf->Output($filename, 'D');
    }
    
    public function generateReportCardContentOnly(data_siswa $siswa): string
    {
        return $this->generateReportCardFromTemplate($siswa, false);
    }
    
    private function generateHeader(TCPDF $pdf, data_siswa $siswa): void
    {
        // Get school data
        $sekolah = sekolah::first();
        
        if ($sekolah) {
            // School header
            $pdf->SetFont('helvetica', 'B', 18); // Increased from 16 to 18
            $pdf->Cell(0, 10, strtoupper($sekolah->nama_sekolah), 0, 1, 'C');
            
            $pdf->SetFont('helvetica', '', 14); // Increased from 12 to 14
            if ($sekolah->alamat) {
                $pdf->Cell(0, 8, $sekolah->alamat, 0, 1, 'C');
            }
            if ($sekolah->telepon) {
                $pdf->Cell(0, 8, 'Telp: ' . $sekolah->telepon, 0, 1, 'C');
            }
        }
        
        // Title
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 16); // Increased from 14 to 16
        $pdf->Cell(0, 10, 'RAPORT SISWA', 0, 1, 'C');
        
        // Line separator
        $pdf->Ln(5);
        $pdf->Line(30, $pdf->GetY(), 180, $pdf->GetY()); // Adjusted for 3cm margins (30mm left, 210-30=180mm right)
        $pdf->Ln(10);
    }
    
    private function generateStudentInfo(TCPDF $pdf, data_siswa $siswa): void
    {
        $pdf->SetFont('helvetica', 'B', 14); // Increased from 12 to 14
        $pdf->Cell(0, 10, 'DATA SISWA', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 12); // Increased from 10 to 12
        
        // Student information table - Fix relasi kelas
        $kelasInfo = null;
        $waliKelasInfo = null;
        
        if ($siswa->kelas) {
            $kelasInfo = \App\Models\data_kelas::find($siswa->kelas);
            if ($kelasInfo && $kelasInfo->walikelas_id) {
                $waliKelasInfo = \App\Models\data_guru::find($kelasInfo->walikelas_id);
            }
        }
        
        $data = [
            ['Nama Lengkap', ':', $siswa->nama_lengkap],
            ['NIS', ':', $siswa->nis],
            ['Kelas', ':', $kelasInfo->nama_kelas ?? '-'],
            ['Wali Kelas', ':', $waliKelasInfo->nama_lengkap ?? '-'],
        ];
        
        foreach ($data as $row) {
            $pdf->Cell(40, 8, $row[0], 0, 0, 'L'); // Increased height from 6 to 8
            $pdf->Cell(10, 8, $row[1], 0, 0, 'L');
            $pdf->Cell(0, 8, $row[2], 0, 1, 'L');
        }
        
        $pdf->Ln(10);
    }
    
    private function generateAssessmentSection(TCPDF $pdf, data_siswa $siswa): void
    {
        $pdf->SetFont('helvetica', 'B', 14); // Increased from 12 to 14
        $pdf->Cell(0, 10, 'PENILAIAN', 0, 1, 'L');
        
        // Get assessment details
        $assessmentDetails = student_assessment_detail::whereHas('studentAssessment', function($query) use ($siswa) {
                $query->where('data_siswa_id', $siswa->id);
            })
            ->with('assessmentVariable')
            ->whereNotNull('rating')
            ->orderBy('created_at', 'desc')
            ->get();
        
        if ($assessmentDetails->count() > 0) {
            $pdf->SetFont('helvetica', '', 11); // Increased from 9 to 11
            
            // Table header - adjusted widths for A4 with 3cm margins (total width = 150mm)
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(12, 10, 'No', 1, 0, 'C', true); // Increased height from 8 to 10
            $pdf->Cell(55, 10, 'Aspek Penilaian', 1, 0, 'C', true);
            $pdf->Cell(25, 10, 'Rating', 1, 0, 'C', true);
            $pdf->Cell(58, 10, 'Keterangan', 1, 1, 'C', true);
            
            $pdf->SetFillColor(255, 255, 255);
            
            $no = 1;
            foreach ($assessmentDetails as $detail) {
                $variableName = $detail->assessmentVariable->name ?? 'Tidak diketahui';
                $rating = $detail->rating ?? '-';
                $description = $detail->description ?? '-';
                
                // Calculate row height based on content
                $descHeight = $pdf->getStringHeight(58, $description);
                $varHeight = $pdf->getStringHeight(55, $variableName);
                $rowHeight = max(10, max($descHeight, $varHeight) + 3); // Increased minimum from 8 to 10
                
                $pdf->Cell(12, $rowHeight, $no, 1, 0, 'C');
                $pdf->Cell(55, $rowHeight, $variableName, 1, 0, 'L');
                $pdf->Cell(25, $rowHeight, $rating, 1, 0, 'L');
                $pdf->Cell(58, $rowHeight, $description, 1, 1, 'L');
                
                $no++;
                
                // Check if we need a new page (A4 height = 297mm, with 3cm margins top/bottom)
                if ($pdf->GetY() > 267) { // 297 - 30 = 267mm
                    $pdf->AddPage();
                }
            }
        } else {
            $pdf->SetFont('helvetica', 'I', 12); // Increased from 10 to 12
            $pdf->Cell(0, 10, 'Belum ada data penilaian.', 0, 1, 'L');
        }
        
        $pdf->Ln(10);
    }
    
    private function generateGrowthSection(TCPDF $pdf, data_siswa $siswa): void
    {
        $pdf->SetFont('helvetica', 'B', 14); // Increased from 12 to 14
        $pdf->Cell(0, 10, 'PERTUMBUHAN', 0, 1, 'L');
        
        // Get growth records
        $growthRecords = GrowthRecord::where('data_siswa_id', $siswa->id)
            ->orderBy('month', 'desc')
            ->take(6) // Last 6 months
            ->get();
        
        if ($growthRecords->count() > 0) {
            $pdf->SetFont('helvetica', '', 11); // Increased from 9 to 11
            
            // Table header - adjusted widths for A4 with 3cm margins (total width = 150mm)
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(25, 10, 'Bulan', 1, 0, 'C', true); // Increased height from 8 to 10
            $pdf->Cell(20, 10, 'Berat (kg)', 1, 0, 'C', true);
            $pdf->Cell(20, 10, 'Tinggi (cm)', 1, 0, 'C', true);
            $pdf->Cell(25, 10, 'L. Kepala (cm)', 1, 0, 'C', true);
            $pdf->Cell(25, 10, 'L. Lengan (cm)', 1, 0, 'C', true);
            $pdf->Cell(35, 10, 'BMI', 1, 1, 'C', true);
            
            $pdf->SetFillColor(255, 255, 255);
            
            foreach ($growthRecords as $record) {
                $bulan = $record->bulan_tahun; // Using accessor from model
                $berat = $record->berat_badan ?? '-';
                $tinggi = $record->tinggi_badan ?? '-';
                $kepala = $record->lingkar_kepala ?? '-';
                $lengan = $record->lingkar_lengan ?? '-';
                $bmi = $record->bmi ?? '-';
                
                $pdf->Cell(25, 8, $bulan, 1, 0, 'C'); // Increased height from 6 to 8
                $pdf->Cell(20, 8, $berat, 1, 0, 'C');
                $pdf->Cell(20, 8, $tinggi, 1, 0, 'C');
                $pdf->Cell(25, 8, $kepala, 1, 0, 'C');
                $pdf->Cell(25, 8, $lengan, 1, 0, 'C');
                $pdf->Cell(35, 8, $bmi, 1, 1, 'C');
            }
        } else {
            $pdf->SetFont('helvetica', 'I', 12); // Increased from 10 to 12
            $pdf->Cell(0, 10, 'Belum ada data pertumbuhan.', 0, 1, 'L');
        }
        
        $pdf->Ln(10);
    }
    
    private function generateAttendanceSection(TCPDF $pdf, data_siswa $siswa): void
    {
        $pdf->SetFont('helvetica', 'B', 14); // Increased from 12 to 14
        $pdf->Cell(0, 10, 'KEHADIRAN', 0, 1, 'L');
        
        // Get attendance record
        $attendance = AttendanceRecord::where('data_siswa_id', $siswa->id)->first();
        
        if ($attendance) {
            $pdf->SetFont('helvetica', '', 12); // Increased from 10 to 12
            
            $pdf->Cell(40, 8, 'Alfa (tanpa keterangan)', 0, 0, 'L');
            $pdf->Cell(10, 8, ':', 0, 0, 'L');
            $pdf->Cell(0, 8, ($attendance->alfa ?? 0) . ' hari', 0, 1, 'L');
            
            $pdf->Cell(40, 8, 'Ijin', 0, 0, 'L');
            $pdf->Cell(10, 8, ':', 0, 0, 'L');
            $pdf->Cell(0, 8, ($attendance->ijin ?? 0) . ' hari', 0, 1, 'L');
            
            $pdf->Cell(40, 8, 'Sakit', 0, 0, 'L');
            $pdf->Cell(10, 8, ':', 0, 0, 'L');
            $pdf->Cell(0, 8, ($attendance->sakit ?? 0) . ' hari', 0, 1, 'L');
            
            $total = ($attendance->alfa ?? 0) + ($attendance->ijin ?? 0) + ($attendance->sakit ?? 0);
            $pdf->SetFont('helvetica', 'B', 12); // Keep bold at 12
            $pdf->Cell(40, 8, 'Total Absen', 0, 0, 'L');
            $pdf->Cell(10, 8, ':', 0, 0, 'L');
            $pdf->Cell(0, 8, $total . ' hari', 0, 1, 'L');
        } else {
            $pdf->SetFont('helvetica', 'I', 12); // Increased from 10 to 12
            $pdf->Cell(0, 10, 'Belum ada data kehadiran.', 0, 1, 'L');
        }
        
        $pdf->Ln(10);
    }
    
    private function generateReflectionSection(TCPDF $pdf): void
    {
        $pdf->SetFont('helvetica', 'B', 14); // Increased from 12 to 14
        $pdf->Cell(0, 10, 'REFLEKSI ORANG TUA', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 12); // Increased from 10 to 12
        $pdf->Cell(0, 6, 'Catatan dan masukan dari orang tua:', 0, 1, 'L');
        
        // Create space for parent reflection - adjusted for 3cm margins
        $pdf->Rect(30, $pdf->GetY() + 2, 150, 40); // Width adjusted to 150mm (210-30-30)
        $pdf->Ln(45);
        
        // Signature section - adjusted positions for 3cm margins
        $pdf->Ln(10);
        $y = $pdf->GetY();
        
        // Teacher signature
        $pdf->SetXY(45, $y); // Adjusted from 30 to 45 for better spacing
        $pdf->Cell(60, 6, 'Wali Kelas', 0, 1, 'C');
        $pdf->Ln(20);
        $pdf->SetX(45);
        $pdf->Cell(60, 6, '(__________________)', 0, 0, 'C');
        
        // Parent signature
        $pdf->SetXY(105, $y); // Adjusted from 120 to 105 for better spacing with new margins
        $pdf->Cell(60, 6, 'Orang Tua/Wali', 0, 1, 'C');
        $pdf->SetXY(105, $y + 26);
        $pdf->Cell(60, 6, '(__________________)', 0, 0, 'C');
        
        // Date
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', '', 11); // Increased from 9 to 11
        $pdf->Cell(0, 6, 'Tanggal: ' . date('d F Y'), 0, 1, 'R');
    }
}