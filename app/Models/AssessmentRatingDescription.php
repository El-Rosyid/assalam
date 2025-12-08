<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentRatingDescription extends Model
{
    use HasFactory;
    
    protected $table = 'assessment_rating_descriptions';
    
    protected $fillable = [
        'assessment_variable_id',
        'rating',
        'description'
    ];
    
    // Rating options - static, tidak berubah
    public static function getRatingOptions()
    {
        return [
            'Berkembang Sesuai Harapan' => 'Berkembang Sesuai Harapan',
            'Belum Berkembang' => 'Belum Berkembang',
            'Mulai Berkembang' => 'Mulai Berkembang', 
            'Sudah Berkembang' => 'Sudah Berkembang',
        ];
    }
    
    // Default descriptions - untuk seeding
    public static function getDefaultDescriptions()
    {
        return [
            'Berkembang Sesuai Harapan' => 'Anak menunjukkan perkembangan yang sesuai dengan harapan dan standar yang ditetapkan.',
            'Belum Berkembang' => 'Anak belum menunjukkan perkembangan pada aspek ini dan memerlukan bimbingan lebih intensif.',
            'Mulai Berkembang' => 'Anak mulai menunjukkan perkembangan pada aspek ini namun masih memerlukan stimulasi.',
            'Sudah Berkembang' => 'Anak telah menunjukkan perkembangan yang baik pada aspek ini.',
        ];
    }
    
    // Relationships
    public function assessmentVariable()
    {
        return $this->belongsTo(assessment_variable::class);
    }
    
    // Scopes
    public function scopeForVariable($query, $variableId)
    {
        return $query->where('assessment_variable_id', $variableId);
    }
    
    public function scopeForRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }
    
    // Helper methods
    public static function getDescriptionFor($assessmentVariableId, $rating)
    {
        $description = self::forVariable($assessmentVariableId)
            ->forRating($rating)
            ->value('description');
            
        // Fallback ke default jika tidak ada custom description
        if (!$description) {
            $defaults = self::getDefaultDescriptions();
            return $defaults[$rating] ?? '';
        }
        
        return $description;
    }
    
    // Bulk create descriptions for all variables
    public static function seedForAllVariables()
    {
        $variables = assessment_variable::all();
        $defaultDescriptions = self::getDefaultDescriptions();
        $ratings = array_keys($defaultDescriptions);
        
        foreach ($variables as $variable) {
            foreach ($ratings as $rating) {
                self::updateOrCreate(
                    [
                        'assessment_variable_id' => $variable->id,
                        'rating' => $rating,
                    ],
                    [
                        'description' => $defaultDescriptions[$rating],
                    ]
                );
            }
        }
    }
}