<?php

namespace App\Observers;

use App\Models\assessment_variable;
use App\Models\AssessmentRatingDescription;

class AssessmentVariableObserver
{
    /**
     * Handle the assessment_variable "created" event.
     */
    public function created(assessment_variable $assessmentVariable): void
    {
        // Auto-generate AssessmentRatingDescription untuk semua rating
        $ratings = AssessmentRatingDescription::getRatingOptions();
        
        foreach ($ratings as $ratingKey => $ratingLabel) {
            AssessmentRatingDescription::create([
                'assessment_variable_id' => $assessmentVariable->id,
                'rating' => $ratingKey,
                'description' => "Deskripsi untuk {$assessmentVariable->name} - {$ratingLabel}",
            ]);
        }
    }

    /**
     * Handle the assessment_variable "deleted" event.
     */
    public function deleted(assessment_variable $assessmentVariable): void
    {
        // Hapus semua rating description terkait ketika variable dihapus
        AssessmentRatingDescription::where('assessment_variable_id', $assessmentVariable->id)->delete();
    }
}
