<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class student_assessment_detail extends Model
{
    use HasFactory;
    
    protected $table = 'student_assessment_details';
    
    protected $fillable = [
        'student_assessment_id',
        'assessment_variable_id',
        'rating',
        'description',
        'images'
    ];
    
    protected $casts = [
        'images' => 'array',
    ];
    
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
    
    // Auto descriptions based on rating
    public static function getAutoDescription($rating)
    {
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
        return $this->belongsTo(student_assessment::class);
    }
    
    public function assessmentVariable()
    {
        return $this->belongsTo(assessment_variable::class);
    }
    
    // Mutators
    public function setRatingAttribute($value)
    {
        $this->attributes['rating'] = $value;
        
        // Auto-fill description if empty
        if (empty($this->attributes['description']) && $value) {
            $this->attributes['description'] = self::getAutoDescription($value);
        }
    }
}
