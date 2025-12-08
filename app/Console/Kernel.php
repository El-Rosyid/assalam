<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Auto-prune siswa yang >90 hari di recycle bin (setiap hari jam 3 pagi)
        $schedule->command('model:prune', ['--model' => \App\Models\data_siswa::class])
            ->daily()
            ->at('03:00')
            ->withoutOverlapping()
            ->runInBackground();
        
        // Cleanup orphan files setiap minggu (Minggu jam 2 pagi)
        $schedule->command('storage:cleanup-orphan-files')
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->withoutOverlapping()
            ->runInBackground();
        
        // Cleanup .trash folder - hapus file >7 hari (setiap hari jam 4 pagi)
        $schedule->call(function () {
            $trashDirs = Storage::disk('public')->directories('.trash');
            $deletedCount = 0;
            $deletedSize = 0;
            
            foreach ($trashDirs as $dir) {
                // Get date from folder name (format: .trash/2025-12-06)
                $date = basename($dir);
                
                try {
                    $folderDate = \Carbon\Carbon::parse($date);
                    
                    // Delete if older than 7 days
                    if ($folderDate->lt(now()->subDays(7))) {
                        $files = Storage::disk('public')->allFiles($dir);
                        
                        foreach ($files as $file) {
                            $deletedSize += Storage::disk('public')->size($file);
                            $deletedCount++;
                        }
                        
                        Storage::disk('public')->deleteDirectory($dir);
                        
                        Log::info('.trash folder cleaned', [
                            'folder' => $dir,
                            'files_deleted' => count($files),
                            'age_days' => now()->diffInDays($folderDate),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to parse trash folder date', ['folder' => $dir]);
                }
            }
            
            if ($deletedCount > 0) {
                Log::info('.trash cleanup completed', [
                    'total_files' => $deletedCount,
                    'total_size' => round($deletedSize / 1024 / 1024, 2) . ' MB',
                ]);
            }
        })
        ->daily()
        ->at('04:00')
        ->name('cleanup-trash-folder')
        ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
