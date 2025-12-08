<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class monthly_reports extends Model
{
    use HasFactory;
    protected $table = 'monthly_reports';
    protected $guarded = [];
    protected $fillable = [
        'siswa_nis',
        'data_guru_id',
        'data_kelas_id',
        'tahun_ajaran_id',
        'month',
        'year',
        'catatan',
        'photos',
        ];

    protected $casts = [
        'month' => 'integer', // TINYINT UNSIGNED
        'year' => 'integer', // SMALLINT UNSIGNED
        'photos' => 'array',
    ];

     // Relationships
    public function siswa()
    {
        return $this->belongsTo(data_siswa::class, 'siswa_nis', 'nis');
    }
    
    public function guru()
    {
        return $this->belongsTo(data_guru::class, 'data_guru_id');
    }
    
    public function kelas()
    {
        return $this->belongsTo(data_kelas::class, 'data_kelas_id');
    }
    
    public function tahunAjaran()
    {
        return $this->belongsTo(academic_year::class, 'tahun_ajaran_id', 'tahun_ajaran_id');
    }
    // Accessors
    public function getBulanAttribute()
    {
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        return $monthNames[$this->month] ?? 'Unknown';
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
    public static function generateAttendanceRecords($guruId, $kelasId)
    {
        // Get students in this class
        $siswaList = data_siswa::where('kelas', $kelasId)->get();
        
        $records = [];
        
        foreach ($siswaList as $siswa) {
            $existingRecord = self::where('siswa_nis', $siswa->nis)->first();
            
            if (!$existingRecord) {
                $records[] = [
                    'data_guru_id' => $guruId,
                    'data_kelas_id' => $kelasId,
                    'siswa_nis' => $siswa->nis,
                    'tahun_ajaran_id' => $siswa->kelasInfo?->tahun_ajaran_id,
                    'month' => now()->month,
                    'year' => now()->year,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        return $records;
    }
    
    // Method yang hilang - sama seperti di GrowthRecord
    public static function generateSpecificMonthRecords($month, $guruId)
    {
        // Get all classes where this guru is wali kelas
        $kelasList = data_kelas::where('walikelas_id', $guruId)->get();
        
        $records = [];
        
        foreach ($kelasList as $kelas) {
            // Get students in this class
            $siswaList = data_siswa::where('kelas', $kelas->kelas_id)->get();
            
            foreach ($siswaList as $siswa) {
                // Check if record already exists for this student and month
                $existingRecord = self::where('siswa_nis', $siswa->nis)
                    ->where('month', $month)
                    ->where('year', date('Y'))
                    ->first();
                
                if (!$existingRecord) {
                    $records[] = [
                        'data_guru_id' => $guruId,
                        'data_kelas_id' => $kelas->kelas_id,
                        'siswa_nis' => $siswa->nis,
                        'tahun_ajaran_id' => $kelas->tahun_ajaran_id,
                        'month' => $month,
                        'year' => date('Y'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }
        
        return $records;
    }
}