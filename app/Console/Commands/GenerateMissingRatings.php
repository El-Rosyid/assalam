<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\assessment_variable;
use App\Models\AssessmentRatingDescription;

class GenerateMissingRatings extends Command
{
    protected $signature = 'generate:missing-ratings';
    protected $description = 'Generate missing rating descriptions for assessment variables';

    public function handle()
    {
        $this->info('Checking for assessment variables without rating descriptions...');
        
        $variables = assessment_variable::all();
        $ratings = AssessmentRatingDescription::getRatingOptions();
        $generated = 0;
        
        foreach ($variables as $variable) {
            $existingCount = AssessmentRatingDescription::where('assessment_variable_id', $variable->id)->count();
            
            if ($existingCount < count($ratings)) {
                $this->warn("Variable '{$variable->name}' (ID: {$variable->id}) has only {$existingCount} ratings. Generating missing ones...");
                
                foreach ($ratings as $ratingKey => $ratingLabel) {
                    // Cek apakah rating ini sudah ada
                    $exists = AssessmentRatingDescription::where('assessment_variable_id', $variable->id)
                        ->where('rating', $ratingKey)
                        ->exists();
                    
                    if (!$exists) {
                        AssessmentRatingDescription::create([
                            'assessment_variable_id' => $variable->id,
                            'rating' => $ratingKey,
                            'description' => "Deskripsi untuk {$variable->name} - {$ratingLabel}",
                        ]);
                        $generated++;
                        $this->line("  ✓ Created: {$ratingLabel}");
                    }
                }
            } else {
                $this->info("✓ Variable '{$variable->name}' already has all ratings.");
            }
        }
        
        if ($generated > 0) {
            $this->info("✅ Successfully generated {$generated} rating descriptions!");
        } else {
            $this->info('All assessment variables already have complete rating descriptions.');
        }
    }
}
