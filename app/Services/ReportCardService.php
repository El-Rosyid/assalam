<?php

namespace App\Services;

use App\Models\data_siswa;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportCardService
{
    /**
     * Generate PDF and return inline response for browser viewing
     */
    public function viewReportCardInline(data_siswa $siswa, bool $withCoverPages = true)
    {
        // Get all data needed
        $sekolah = \App\Models\Sekolah::first();
        $kelasInfo = \App\Models\data_kelas::find($siswa->kelas);
        $waliKelasInfo = $kelasInfo ? \App\Models\data_guru::find($kelasInfo->walikelas_id) : null;
        
        // Get assessment data
        $assessmentDetails = \App\Models\student_assessment_detail::whereHas('studentAssessment', function($query) use ($siswa) {
                $query->where('siswa_nis', $siswa->nis);
            })
            ->with('assessmentVariable')
            ->whereNotNull('rating')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get growth records
        $growthRecords = \App\Models\GrowthRecord::where('siswa_nis', $siswa->nis)
            ->orderBy('month', 'desc')
            ->take(6)
            ->get();
        
        // Get attendance record
        $attendance = \App\Models\AttendanceRecord::where('siswa_nis', $siswa->nis)->first();
        
        // Choose template based on cover pages option
        $templateName = $withCoverPages ? 'pdf.cover-pages' : 'pdf.raport-content';
        
        // Generate HTML from template
        $html = view($templateName, compact(
            'siswa', 'sekolah', 'kelasInfo', 'waliKelasInfo', 
            'assessmentDetails', 'growthRecords', 'attendance'
        ))->render();
        
        // Create PDF from HTML using DomPDF
        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'fontDir' => storage_path('fonts/'),
                'fontCache' => storage_path('fonts/cache/'),
                'tempDir' => storage_path('temp/'),
                'chroot' => storage_path(),
                'enable_font_subsetting' => true,
                'pdf_backend' => 'CPDF',
                'default_media_type' => 'screen',
                'default_paper_size' => 'A4',
                'default_font' => 'DejaVu Sans',
                'dpi' => 150,
                'enable_php' => false,
                'enable_javascript' => false,
                'enable_remote' => true,
                'font_height_ratio' => 1.1,
                'enable_html5_parser' => true
            ]);
        
        // Generate filename
        $filename = 'raport_' . $siswa->nis . '_' . str_replace(' ', '_', $siswa->nama_lengkap) . '.pdf';
        
        // Stream PDF to browser for inline viewing
        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->header('Content-Transfer-Encoding', 'binary')
            ->header('Accept-Ranges', 'bytes');
    }
}