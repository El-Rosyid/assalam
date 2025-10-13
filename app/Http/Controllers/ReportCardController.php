<?php

namespace App\Http\Controllers;

use App\Models\data_siswa;
use App\Services\ReportCardService;
use Illuminate\Http\Request;

class ReportCardController extends Controller
{
    public function previewRaport(data_siswa $siswa)
    {
        try {
            // Check authorization
            $user = auth()->user();
            if (!$user || !$user->guru) {
                abort(403, 'Anda tidak memiliki akses untuk preview raport.');
            }
            
            // Check if user is kepala sekolah or wali kelas of this student
            $isKepalaSekolah = \App\Models\sekolah::where('kepala_sekolah', $user->guru->id)->exists();
            
            if (!$isKepalaSekolah) {
                // Get kelas info properly
                $kelasInfo = \App\Models\data_kelas::find($siswa->kelas);
                if (!$kelasInfo || $kelasInfo->walikelas_id !== $user->guru->id) {
                    abort(403, 'Anda tidak memiliki akses untuk preview raport siswa ini.');
                }
            }
            
            // Get all data needed for preview
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
            
            return view('raport.print-modal', compact(
                'siswa', 'sekolah', 'kelasInfo', 'waliKelasInfo', 
                'assessmentDetails', 'growthRecords', 'attendance'
            ));
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat membuat preview: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadRaport(data_siswa $siswa)
    {
        try {
            // Check authorization
            $user = auth()->user();
            if (!$user || !$user->guru) {
                abort(403, 'Anda tidak memiliki akses untuk download raport.');
            }
            
            // Check if user is kepala sekolah or wali kelas of this student
            $isKepalaSekolah = \App\Models\sekolah::where('kepala_sekolah', $user->guru->id)->exists();
            
            if (!$isKepalaSekolah) {
                // Get kelas info properly
                $kelasInfo = \App\Models\data_kelas::find($siswa->kelas);
                if (!$kelasInfo || $kelasInfo->walikelas_id !== $user->guru->id) {
                    abort(403, 'Anda tidak memiliki akses untuk download raport siswa ini.');
                }
            }
            
            $reportService = new ReportCardService();
            return $reportService->generateReportCardFromTemplate($siswa);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat membuat PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    public function printRaport(data_siswa $siswa)
    {
        try {
            // Check authorization
            $user = auth()->user();
            if (!$user || !$user->guru) {
                abort(403, 'Anda tidak memiliki akses untuk print raport.');
            }
            
            // Check if user is kepala sekolah or wali kelas of this student
            $isKepalaSekolah = \App\Models\sekolah::where('kepala_sekolah', $user->guru->id)->exists();
            
            if (!$isKepalaSekolah) {
                // Get kelas info properly
                $kelasInfo = \App\Models\data_kelas::find($siswa->kelas);
                if (!$kelasInfo || $kelasInfo->walikelas_id !== $user->guru->id) {
                    abort(403, 'Anda tidak memiliki akses untuk print raport siswa ini.');
                }
            }
            
            // Get all data needed for print
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
            
            return view('raport.print-modal', compact(
                'siswa', 'sekolah', 'kelasInfo', 'waliKelasInfo', 
                'assessmentDetails', 'growthRecords', 'attendance'
            ));
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat membuat print: ' . $e->getMessage()
            ], 500);
        }
    }
}