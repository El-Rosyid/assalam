<?php

namespace App\Services;

use App\Models\academic_year;
use App\Models\data_siswa;
use App\Models\data_kelas;
use App\Models\student_assessment;
use App\Models\GrowthRecord;
use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AcademicYearTransitionService
{
    /**
     * Transisi ke tahun ajaran baru
     * Opsi 1: Copy structure (tidak copy data)
     * Opsi 2: Copy data terakhir (untuk kontinuitas)
     */
    public function transitionToNewYear(academic_year $newYear, array $options = [])
    {
        $copyLastData = $options['copy_last_data'] ?? false;
        $oldYear = academic_year::where('is_active', true)->first();
        
        DB::beginTransaction();
        
        try {
            // 1. Nonaktifkan tahun ajaran lama
            if ($oldYear) {
                $oldYear->update(['is_active' => false]);
            }
            
            // 2. Aktifkan tahun ajaran baru
            $newYear->update(['is_active' => true]);
            
            // 3. Generate struktur assessment untuk semua siswa aktif
            $this->generateAssessmentStructure($newYear);
            
            // 4. Optional: Copy data pertumbuhan terakhir
            if ($copyLastData && $oldYear) {
                $this->copyLastGrowthData($oldYear, $newYear);
            }
            
            DB::commit();
            
            Log::info("Academic year transition completed", [
                'old_year' => $oldYear ? $oldYear->year . ' ' . $oldYear->semester : 'none',
                'new_year' => $newYear->year . ' ' . $newYear->semester,
            ]);
            
            return [
                'success' => true,
                'message' => 'Transisi tahun ajaran berhasil',
                'old_year' => $oldYear,
                'new_year' => $newYear,
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Academic year transition failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Transisi gagal: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Generate struktur assessment kosong untuk semua siswa
     */
    protected function generateAssessmentStructure(academic_year $year)
    {
        $siswaList = data_siswa::where('status', 'aktif')->get();
        $semester = $year->semester === 'Ganjil' ? 1 : 2;
        
        foreach ($siswaList as $siswa) {
            // Create assessment record if not exists
            student_assessment::firstOrCreate([
                'siswa_nis' => $siswa->nis,
                'semester' => $semester == 1 ? 'Ganjil' : 'Genap',
            ], [
                'status' => 'belum_dinilai',
            ]);
        }
        
        return true;
    }
    
    /**
     * Copy data pertumbuhan terakhir dari tahun lalu
     * Berguna untuk tracking kontinuitas
     */
    protected function copyLastGrowthData(academic_year $oldYear, academic_year $newYear)
    {
        $siswaList = data_siswa::where('status', 'aktif')->get();
        
        foreach ($siswaList as $siswa) {
            // Ambil data pertumbuhan terakhir
            $lastGrowth = GrowthRecord::where('siswa_nis', $siswa->nis)
                ->where('year', $oldYear->year)
                ->orderBy('month', 'desc')
                ->first();
            
            if ($lastGrowth) {
                // Create record di tahun baru dengan data terakhir
                GrowthRecord::create([
                    'siswa_nis' => $siswa->nis,
                    'data_guru_id' => $siswa->kelasInfo?->walikelas_id,
                    'data_kelas_id' => $siswa->kelas,
                    'tahun_ajaran_id' => $newYear->tahun_ajaran_id,
                    'year' => $newYear->year,
                    'month' => now()->month,
                    'berat_badan' => $lastGrowth->berat_badan,
                    'tinggi_badan' => $lastGrowth->tinggi_badan,
                    'lingkar_kepala' => $lastGrowth->lingkar_kepala,
                    'lingkar_lengan' => $lastGrowth->lingkar_lengan,
                ]);
            }
        }
        
        return true;
    }
    
    /**
     * Get summary data yang akan terpengaruh
     * Uses linear hierarchy to count related data
     */
    public function getTransitionSummary(academic_year $newYear)
    {
        $activeSiswa = data_siswa::where('status', 'aktif')->count();
        // Count kelas in active year (LINEAR HIERARCHY)
        $oldYear = academic_year::where('is_active', true)->first();
        $activeKelas = data_kelas::where('tahun_ajaran_id', $oldYear?->tahun_ajaran_id)->count();
        
        $oldYearData = null;
        if ($oldYear) {
            $oldYearData = [
                'year' => $oldYear->year . ' ' . $oldYear->semester,
                'assessments' => student_assessment::where('semester', $oldYear->semester == 'Ganjil' ? 'Ganjil' : 'Genap')->count(),
                'growth_records' => GrowthRecord::where('year', explode('/', $oldYear->year)[0])->count(),
            ];
        }
        
        return [
            'active_students' => $activeSiswa,
            'active_classes' => $activeKelas,
            'will_create_assessments' => $activeSiswa,
            'old_year' => $oldYearData,
        ];
    }
}
