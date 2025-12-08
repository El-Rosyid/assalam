<?php
// app/Http/Controllers/RaportController.php

namespace App\Http\Controllers;

use App\Models\data_siswa;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class RaportController extends Controller
{
    /**
     * View PDF inline in browser (like academic journal websites)
     */
    public function viewPDFInline(data_siswa $siswa)
    {
        try {
            // Load relationships
            $siswa->load(['kelasInfo', 'kelasInfo.waliKelas', 'growthRecords', 'attendanceRecords']);
            
            // Get sekolah data
            $sekolah = sekolah::first();
            
            // Get kepala sekolah dari data sekolah
            $kepalaSekolah = (object)[
                'nama' => $sekolah->kepala_sekolah ?? 'Nama Kepala Sekolah',
                'nip' => $sekolah->nip_kepala_sekolah ?? '.....................'
            ];
            
            // Get academic year (ambil yang active atau dari session)
            $academicYearId = session('selected_academic_year_id') ?? \App\Models\academic_year::where('is_active', true)->first()?->tahun_ajaran_id;
            $academicYear = \App\Models\academic_year::find($academicYearId) ?? \App\Models\academic_year::where('is_active', true)->first();
            
            if (!$academicYear) {
                return response("Tahun ajaran tidak ditemukan. Silakan aktifkan tahun ajaran terlebih dahulu.", 404);
            }
            
            // Get all assessment variables
            $assessmentVariables = \App\Models\assessment_variable::orderBy('name')->get();

            // Get assessment data (tanpa filter academic_year karena kolom tidak ada)
            $assessments = \App\Models\student_assessment::where('siswa_nis', $siswa->nis)
                ->with(['details.assessmentVariable'])
                ->orderBy('semester', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Get wali kelas
            $waliKelas = $siswa->kelasInfo?->waliKelas ?? null;
            
            // Format semester untuk display sebagai angka + label: "1 (Ganjil)" atau "2 (Genap)"
            $semester = $academicYear->semester == 'Ganjil' ? '1 (Ganjil)' : '2 (Genap)';
            
            $data = [
                'siswa' => $siswa,
                'sekolah' => $sekolah,
                'kelasInfo' => $siswa->kelasInfo,
                'waliKelas' => $waliKelas,
                'kepalaSekolah' => $kepalaSekolah,
                'academicYear' => $academicYear,
                'semester' => $semester,
                'assessments' => $assessments,
                'assessmentVariables' => $assessmentVariables
            ];
            
            // Generate PDF
            $pdf = Pdf::loadView('pdf.cover-pages', $data)
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isRemoteEnabled' => true,
                    'isHtml5ParserEnabled' => true,
                    'fontDir' => storage_path('fonts/'),
                    'fontCache' => storage_path('fonts/'),
                    'tempDir' => sys_get_temp_dir(),
                    'chroot' => realpath(base_path()),
                    'enable_font_subsetting' => false,
                    'pdf_backend' => 'CPDF',
                    'dpi' => 96,
                ]);
                
            $filename = 'raport-' . str_replace(' ', '-', strtolower($siswa->nama_lengkap)) . '.pdf';
            
            // Return inline PDF response for browser viewing
            return response($pdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Content-Transfer-Encoding', 'binary')
                ->header('Accept-Ranges', 'bytes')
                ->header('Cache-Control', 'public, must-revalidate, max-age=0')
                ->header('Pragma', 'public')
                ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT')
                ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');

        } catch (\Exception $e) {
            Log::error("PDF inline view failed: " . $e->getMessage());
            
            return response()->json([
                'error' => 'Gagal menampilkan PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}