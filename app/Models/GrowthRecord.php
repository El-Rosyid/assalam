<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GrowthRecord extends Model
{
    use HasFactory;
    
    protected $table = 'growth_records';
    protected $primaryKey = 'pertumbuhan_id';
    
    protected $fillable = [
        'siswa_nis',
        'data_guru_id',
        'data_kelas_id',
        'tahun_ajaran_id',
        'month',
        'year',
        'lingkar_kepala',
        'lingkar_lengan',
        'berat_badan',
        'tinggi_badan',
    ];
    
    protected $casts = [
        'month' => 'integer', // TINYINT UNSIGNED
        'year' => 'integer', // SMALLINT UNSIGNED
        'lingkar_kepala' => 'decimal:2',
        'lingkar_lengan' => 'decimal:2',
        'berat_badan' => 'decimal:2',
        'tinggi_badan' => 'decimal:2',
    ];
    
    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        // Log when deleting (permanent delete)
        static::deleting(function ($record) {
            Log::info("GrowthRecord deleted", [
                'id' => $record->pertumbuhan_id,
                'siswa_nis' => $record->siswa_nis,
                'month' => $record->month,
                'year' => $record->year
            ]);
        });
    }
    
    // Relationships
    public function siswa()
    {
        // Use select to avoid loading all columns which might trigger id query
        return $this->belongsTo(data_siswa::class, 'siswa_nis', 'nis')
            ->select(['nis', 'nama_lengkap', 'nisn', 'kelas']);
    }
    
    public function guru()
    {
        return $this->belongsTo(data_guru::class, 'data_guru_id', 'guru_id');
    }
    
    public function kelas()
    {
        return $this->belongsTo(data_kelas::class, 'data_kelas_id', 'kelas_id');
    }
    
    public function tahunAjaran()
    {
        return $this->belongsTo(academic_year::class, 'tahun_ajaran_id', 'tahun_ajaran_id');
    }
    
    // Accessors
    public function getBulanTahunAttribute()
    {
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        return $monthNames[$this->month] ?? 'Unknown';
    }
    
    public function getBmiAttribute()
    {
        if ($this->tinggi_badan && $this->berat_badan) {
            $tinggiMeter = $this->tinggi_badan / 100;
            return round($this->berat_badan / ($tinggiMeter * $tinggiMeter), 2);
        }
        return null;
    }
    
    // Scopes
    public function scopeForWaliKelas($query, $guruId)
    {
        return $query->whereHas('kelas', function ($q) use ($guruId) {
            $q->where('walikelas_id', $guruId);
        });
    }
    
    public function scopeByMonth($query, $month)
    {
        return $query->where('month', $month);
    }
    
    // Helper methods
    public static function generateSpecificMonthRecords($month, $guruId)
    {
        // Get students under this wali kelas using Eloquent model
        $siswaList = data_siswa::whereIn('kelas', function ($query) use ($guruId) {
            $query->select('kelas_id')
                ->from('data_kelas')
                ->where('walikelas_id', $guruId);
        })->with('kelasInfo')->get();
        
        $records = [];
        
        foreach ($siswaList as $siswa) {
            $existingRecord = self::where([
                'siswa_nis' => $siswa->nis,
                'month' => $month,
            ])->first();
            
            if (!$existingRecord) {
                // Get tahun_ajaran_id dari relationship (use kelasInfo to avoid attribute conflict)
                $tahunAjaranId = $siswa->kelasInfo?->tahun_ajaran_id;
                
                $records[] = [
                    'siswa_nis' => $siswa->nis,
                    'data_guru_id' => $guruId,
                    'data_kelas_id' => $siswa->kelas,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'month' => $month,
                    'lingkar_kepala' => null,
                    'lingkar_lengan' => null,
                    'berat_badan' => null,
                    'tinggi_badan' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        return $records;
    }
    
    public static function generateMonthlyRecords($guruId)
    {
        // Get students under this wali kelas using Eloquent model
        $siswaList = data_siswa::whereIn('kelas', function ($query) use ($guruId) {
            $query->select('kelas_id')
                ->from('data_kelas')
                ->where('walikelas_id', $guruId);
        })->with('kelasInfo')->get();
        
        $records = [];
        
        // Generate 12 months of records
        for ($month = 1; $month <= 12; $month++) {
            foreach ($siswaList as $siswa) {
                $existingRecord = self::where([
                    'siswa_nis' => $siswa->nis,
                    'month' => $month,
                ])->first();
                
                if (!$existingRecord) {
                    // Get tahun_ajaran_id dari relationship (use kelasInfo to avoid attribute conflict)
                    $tahunAjaranId = $siswa->kelasInfo?->tahun_ajaran_id;
                    
                    $records[] = [
                        'siswa_nis' => $siswa->nis,
                        'data_guru_id' => $guruId,
                        'data_kelas_id' => $siswa->kelas,
                        'tahun_ajaran_id' => $tahunAjaranId,
                        'month' => $month,
                        'lingkar_kepala' => null,
                        'lingkar_lengan' => null,
                        'berat_badan' => null,
                        'tinggi_badan' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }
        
        return $records;
    }
    
    // Method untuk Filament Resource - langsung insert ke DB dan return count
    public static function generateForWaliKelas($guruId, $month, $year = null)
    {
        // Year parameter diabaikan karena struktur DB tidak support year
        
        // Get students under this wali kelas using Eloquent model
        $siswaList = data_siswa::whereIn('kelas', function ($query) use ($guruId) {
            $query->select('kelas_id')
                ->from('data_kelas')
                ->where('walikelas_id', $guruId);
        })->with('kelasInfo')->get();
        
        $recordsToInsert = [];
        
        foreach ($siswaList as $siswa) {
            $existingRecord = self::where([
                'siswa_nis' => $siswa->nis,
                'month' => $month,
            ])->first();
            
            if (!$existingRecord) {
                // Get tahun_ajaran_id dari relationship (use kelasInfo to avoid attribute conflict)
                $tahunAjaranId = $siswa->kelasInfo?->tahun_ajaran_id;
                
                $recordsToInsert[] = [
                    'siswa_nis' => $siswa->nis,
                    'data_guru_id' => $guruId,
                    'data_kelas_id' => $siswa->kelas,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'month' => $month,
                    'lingkar_kepala' => null,
                    'lingkar_lengan' => null,
                    'berat_badan' => null,
                    'tinggi_badan' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        if (!empty($recordsToInsert)) {
            self::insert($recordsToInsert);
        }
        
        return count($recordsToInsert);
    }
    
    /**
     * Debug method to check data consistency for a guru
     */
    public static function debugDataConsistency($guruId)
    {
        // Get current students in guru's classes - use raw query to avoid id issue
        $kelasIds = DB::table('data_kelas')
            ->where('walikelas_id', $guruId)
            ->pluck('kelas_id');
        
        $currentStudents = DB::table('data_siswa')
            ->whereIn('kelas', $kelasIds)
            ->get();
        
        // Get growth records for this guru
        $growthRecords = self::where('data_guru_id', $guruId)->get();
        
        // Group records by month
        $recordsByMonth = $growthRecords->groupBy('month');
        
        $debug = [
            'current_students_count' => $currentStudents->count(),
            'current_students_nis' => $currentStudents->pluck('nis')->toArray(),
            'months_data' => []
        ];
        
        foreach ($recordsByMonth as $month => $records) {
            $uniqueNis = $records->pluck('siswa_nis')->unique();
            $validNis = $uniqueNis->intersect($currentStudents->pluck('nis'));
            $invalidNis = $uniqueNis->diff($currentStudents->pluck('nis'));
            
            $debug['months_data'][$month] = [
                'total_records' => $records->count(),
                'unique_students_in_records' => $uniqueNis->count(),
                'valid_students' => $validNis->count(),
                'invalid_students' => $invalidNis->count(),
                'invalid_nis' => $invalidNis->toArray()
            ];
        }
        
        return $debug;
    }
    
    /**
     * Ensure all current students have growth records for existing months
     */
    public static function ensureAllStudentsHaveRecords($guruId)
    {
        // Get current students - use raw query to avoid id issue
        $kelasIds = DB::table('data_kelas')
            ->where('walikelas_id', $guruId)
            ->pluck('kelas_id');
        
        $currentStudents = DB::table('data_siswa')
            ->whereIn('kelas', $kelasIds)
            ->get();
        
        // Get existing months
        $existingMonths = self::where('data_guru_id', $guruId)
            ->distinct()
            ->pluck('month');
            
        $createdCount = 0;
        
        foreach ($existingMonths as $month) {
            foreach ($currentStudents as $siswa) {
                $existingRecord = self::where([
                    'siswa_nis' => $siswa->nis,
                    'month' => $month,
                    'data_guru_id' => $guruId
                ])->first();
                
                if (!$existingRecord) {
                    self::create([
                        'siswa_nis' => $siswa->nis,
                        'data_guru_id' => $guruId,
                        'data_kelas_id' => $siswa->kelas,
                        'tahun_ajaran_id' => $siswa->kelasInfo?->tahun_ajaran_id,
                        'month' => $month,
                        'year' => now()->year,
                        'lingkar_kepala' => null,
                        'lingkar_lengan' => null,
                        'berat_badan' => null,
                        'tinggi_badan' => null,
                    ]);
                    $createdCount++;
                }
            }
        }
        
        return $createdCount;
    }
}
