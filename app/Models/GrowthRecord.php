<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class GrowthRecord extends Model
{
    use HasFactory;
    
    protected $table = 'growth_records';
    
    protected $fillable = [
        'data_siswa_id',
        'data_guru_id',
        'data_kelas_id',
        'month',
        'lingkar_kepala',
        'lingkar_lengan',
        'berat_badan',
        'tinggi_badan',
    ];
    
    protected $casts = [
        'month' => 'integer',
        'lingkar_kepala' => 'decimal:2',
        'lingkar_lengan' => 'decimal:2',
        'berat_badan' => 'decimal:2',
        'tinggi_badan' => 'decimal:2',
    ];
    
    // Relationships
    public function siswa()
    {
        return $this->belongsTo(data_siswa::class, 'data_siswa_id');
    }
    
    public function guru()
    {
        return $this->belongsTo(data_guru::class, 'data_guru_id');
    }
    
    public function kelas()
    {
        return $this->belongsTo(data_kelas::class, 'data_kelas_id');
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
        // Get students under this wali kelas
        $siswaList = data_siswa::whereHas('kelas', function ($q) use ($guruId) {
            $q->where('walikelas_id', $guruId);
        })->get();
        
        $records = [];
        
        foreach ($siswaList as $siswa) {
            $existingRecord = self::where([
                'data_siswa_id' => $siswa->id,
                'month' => $month,
            ])->first();
            
            if (!$existingRecord) {
                $records[] = [
                    'data_siswa_id' => $siswa->id,
                    'data_guru_id' => $guruId,
                    'data_kelas_id' => $siswa->kelas,
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
        // Get students under this wali kelas
        $siswaList = data_siswa::whereHas('kelas', function ($q) use ($guruId) {
            $q->where('walikelas_id', $guruId);
        })->get();
        
        $records = [];
        
        // Generate 12 months of records
        for ($month = 1; $month <= 12; $month++) {
            foreach ($siswaList as $siswa) {
                $existingRecord = self::where([
                    'data_siswa_id' => $siswa->id,
                    'month' => $month,
                ])->first();
                
                if (!$existingRecord) {
                    $records[] = [
                        'data_siswa_id' => $siswa->id,
                        'data_guru_id' => $guruId,
                        'data_kelas_id' => $siswa->kelas,
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
}
