<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class student_assessment_detail extends Model
{
    use HasFactory;
    
    protected $table = 'student_assessment_details';
    protected $primaryKey = 'detail_id';
    
    protected $fillable = [
        'penilaian_id',
        'variabel_id',
        'rating',
        'description',
        'images'
    ];
    
    protected $casts = [
        'images' => 'array',
    ];
    
    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        // When deleting (permanent delete)
        static::deleting(function ($detail) {
            // Delete all images
            if (!empty($detail->images) && is_array($detail->images)) {
                foreach ($detail->images as $imagePath) {
                    $path = str_replace(['storage/', '/storage/'], '', $imagePath);
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                        Log::info("Deleted detail image: {$path}");
                    }
                }
            }
            
            Log::info("StudentAssessmentDetail deleted", [
                'id' => $detail->detail_id,
                'penilaian_id' => $detail->penilaian_id
            ]);
        });
    }
    
    // Rating options
    public static function getRatingOptions()
    {
        return [
            'Berkembang Sesuai Harapan' => 'Berkembang Sesuai Harapan',
            'Belum Berkembang' => 'Belum Berkembang',
            'Mulai Berkembang' => 'Mulai Berkembang', 
            'Sudah Berkembang' => 'Sudah Berkembang',
        ];
    }
    
    // Auto descriptions based on rating - now dynamic from database
    public static function getAutoDescription($rating, $assessmentVariableId = null)
    {
        // Jika ada assessment_variable_id, ambil dari database
        if ($assessmentVariableId) {
            $description = AssessmentRatingDescription::getDescriptionFor($assessmentVariableId, $rating);
            if ($description) {
                return $description;
            }
        }
        
        // Fallback ke hardcode (backward compatibility)
        return match($rating) {
            'Berkembang Sesuai Harapan' => 'Anak menunjukkan perkembangan yang sesuai dengan harapan dan standar yang ditetapkan.',
            'Belum Berkembang' => 'Anak belum menunjukkan perkembangan pada aspek ini dan memerlukan bimbingan lebih intensif.',
            'Mulai Berkembang' => 'Anak mulai menunjukkan perkembangan pada aspek ini namun masih memerlukan stimulasi.',
            'Sudah Berkembang' => 'Anak telah menunjukkan perkembangan yang baik pada aspek ini.',
            default => ''
        };
    }
    
    // Relationships
    public function studentAssessment()
    {
        return $this->belongsTo(student_assessment::class, 'penilaian_id', 'penilaian_id');
    }
    
    public function assessmentVariable()
    {
        return $this->belongsTo(assessment_variable::class, 'variabel_id', 'id');
    }
    
    // Mutators
    public function setRatingAttribute($value)
    {
        $this->attributes['rating'] = $value;
        
        // Auto-fill description if empty
        if (empty($this->attributes['description']) && $value) {
            $assessmentVariableId = $this->attributes['variabel_id'] ?? null;
            $this->attributes['description'] = self::getAutoDescription($value, $assessmentVariableId);
        }
    }
}
