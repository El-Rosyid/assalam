<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\monthly_reports;
use App\Models\student_assessment;
use App\Models\assessment_variable;
use App\Models\GrowthRecord;
use App\Models\data_siswa;
use App\Models\data_guru;
use App\Observers\MonthlyReportObserver;
use App\Observers\StudentAssessmentObserver;
use App\Observers\AssessmentVariableObserver;
use App\Observers\GrowthRecordObserver;
use App\Observers\DataSiswaObserver;
use App\Observers\DataGuruObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers for notifications
        monthly_reports::observe(MonthlyReportObserver::class);
        student_assessment::observe(StudentAssessmentObserver::class);
        GrowthRecord::observe(GrowthRecordObserver::class);
        
        // Register observer untuk auto-generate rating descriptions
        assessment_variable::observe(AssessmentVariableObserver::class);
        
        // Register observer untuk delete User account saat siswa/guru dihapus
        data_siswa::observe(DataSiswaObserver::class);
        data_guru::observe(DataGuruObserver::class);
    }
}
