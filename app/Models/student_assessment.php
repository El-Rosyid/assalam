<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class student_assessment extends Model
{
    use HasFactory;
    
    protected $table = 'student_assessments';
    
    protected $fillable = [
        'data_siswa_id',
        'data_guru_id',
        'data_kelas_id', 
        'academic_year_id',
        'semester',
        'status',
        'completed_at'
    ];
    
    protected $casts = [
        'completed_at' => 'datetime',
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
    
    public function academicYear()
    {
        return $this->belongsTo(academic_year::class, 'academic_year_id');
    }
    
    public function details()
    {
        return $this->hasMany(student_assessment_detail::class);
    }
    
    // Helper methods
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'belum_dinilai' => '❌ Belum Dinilai',
            'sebagian' => '⚠️ Sebagian',
            'selesai' => '✅ Selesai',
            default => '❓ Unknown'
        };
    }
    
    public function updateStatus()
    {
        $totalVariables = assessment_variable::count();
        $completedDetails = $this->details()->whereNotNull('rating')->count();
        
        if ($completedDetails == 0) {
            $this->status = 'belum_dinilai';
            $this->completed_at = null;
        } elseif ($completedDetails < $totalVariables) {
            $this->status = 'sebagian';
            $this->completed_at = null;
        } else {
            $this->status = 'selesai';
            $this->completed_at = now();
        }
        
        $this->save();
    }
}
