<?php

namespace App\Console\Commands;

use App\Models\AssessmentRatingDescription;
use App\Models\assessment_variable;
use Illuminate\Console\Command;

class SyncAssessmentDescriptionsCommand extends Command
{
    protected $signature = 'assessment:sync-descriptions';
    protected $description = 'Sync assessment descriptions for new assessment variables';

    public function handle()
    {
        $this->info('Syncing Assessment Rating Descriptions...');
        
        $beforeCount = AssessmentRatingDescription::count();
        
        // Seed untuk semua assessment variables
        AssessmentRatingDescription::seedForAllVariables();
        
        $afterCount = AssessmentRatingDescription::count();
        $newRecords = $afterCount - $beforeCount;
        
        $this->info("Sync completed!");
        $this->info("Total descriptions: {$afterCount}");
        $this->info("New records created: {$newRecords}");
        
        if ($newRecords > 0) {
            $this->warn("Created default descriptions for {$newRecords} new assessment variable-rating combinations.");
            $this->warn("Please review and customize them in the admin panel.");
        }

        return 0;
    }
}