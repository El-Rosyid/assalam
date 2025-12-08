<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class student_assessment extends Model
{
    use HasFactory;
    
    protected $table = 'student_assessments';
    protected $primaryKey = 'penilaian_id';
    
    protected $fillable = [
        'siswa_nis',
        'tahun_ajaran_id',
        'semester',
        'status',
        'completed_at'
    ];
    
    protected $casts = [
        'completed_at' => 'datetime',
    ];
    
    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        // When deleting (permanent delete)
        static::deleting(function ($assessment) {
            // Delete all images in assessment details
            foreach ($assessment->details as $detail) {
                if (!empty($detail->images) && is_array($detail->images)) {
                    foreach ($detail->images as $imagePath) {
                        $path = str_replace(['storage/', '/storage/'], '', $imagePath);
                        if (Storage::disk('public')->exists($path)) {
                            Storage::disk('public')->delete($path);
                            Log::info("Deleted assessment image: {$path}");
                        }
                    }
                }
            }
            
            // Delete all detail records
            $assessment->details()->delete();
            
            Log::info("StudentAssessment deleted", [
                'id' => $assessment->penilaian_id,
                'siswa_nis' => $assessment->siswa_nis
            ]);
        });
    }
    
    // Relationships
    public function siswa()
    {
        return $this->belongsTo(data_siswa::class, 'siswa_nis', 'nis');
    }
    
    public function tahunAjaran()
    {
        return $this->belongsTo(academic_year::class, 'tahun_ajaran_id', 'tahun_ajaran_id');
    }
    
    public function details()
    {
        return $this->hasMany(student_assessment_detail::class, 'student_assessment_id', 'penilaian_id');
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
