<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\assessment_variable;
use App\Models\AssessmentRatingDescription;

class CheckAssessmentRating extends Command
{
    protected $signature = 'check:assessment-rating {variable_id?}';
    protected $description = 'Check assessment rating descriptions for a variable';

    public function handle()
    {
        $variableId = $this->argument('variable_id');
        
        if ($variableId) {
            $variable = assessment_variable::find($variableId);
        } else {
            $variable = assessment_variable::latest()->first();
        }
        
        if (!$variable) {
            $this->error('No assessment variable found!');
            return;
        }
        
        $this->info("Assessment Variable: {$variable->name} (ID: {$variable->id})");
        $this->info("Created at: {$variable->created_at}");
        
        $ratings = AssessmentRatingDescription::where('assessment_variable_id', $variable->id)->get();
        
        $this->info("\nRating Descriptions Count: {$ratings->count()}");
        
        if ($ratings->count() > 0) {
            $this->table(
                ['ID', 'Rating', 'Description'],
                $ratings->map(fn($r) => [$r->id, $r->rating, substr($r->description, 0, 50)])
            );
        } else {
            $this->warn('No rating descriptions found! Observer might not be working.');
        }
        
        // List semua variables
        $this->info("\n=== All Assessment Variables ===");
        $allVars = assessment_variable::with('ratingDescriptions')->get();
        foreach ($allVars as $var) {
            $count = $var->ratingDescriptions->count();
            $this->line("ID: {$var->id} | {$var->name} | Ratings: {$count}");
        }
    }
}
