<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;
    
    protected $table = 'attendance_records';
    
    protected $fillable = [
        'data_guru_id',
        'data_kelas_id',
        'siswa_nis',
        'alfa',
        'ijin',
        'sakit',
    ];
    
    protected $casts = [
        'alfa' => 'integer',
        'ijin' => 'integer',
        'sakit' => 'integer',
    ];
    
    // Relationships
    public function guru()
    {
        return $this->belongsTo(data_guru::class, 'data_guru_id');
    }
    
    public function kelas()
    {
        return $this->belongsTo(data_kelas::class, 'data_kelas_id');
    }
    
    public function siswa()
    {
        return $this->belongsTo(data_siswa::class, 'siswa_nis', 'nis');
    }
    
    // Accessors
    public function getTotalAbsenAttribute()
    {
        return $this->alfa + $this->ijin + $this->sakit;
    }
    
    // Scopes
    public function scopeForWaliKelas($query, $guruId)
    {
        return $query->whereHas('kelas', function ($q) use ($guruId) {
            $q->where('walikelas_id', $guruId);
        });
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
                    'alfa' => 0,
                    'ijin' => 0,
                    'sakit' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        return $records;
    }
}
