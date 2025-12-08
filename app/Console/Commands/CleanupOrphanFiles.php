<?php

namespace App\Console\Commands;

use App\Models\data_siswa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CleanupOrphanFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:cleanup-orphan-files {--dry-run : Preview files that would be deleted without actually deleting them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup orphan files from storage (files yang tidak ada owner siswa-nya)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No files will be deleted');
        } else {
            $this->warn('⚠️  CLEANUP MODE - Files will be permanently deleted!');
            
            // Check if running from CLI
            if (function_exists('posix_isatty') && posix_isatty(STDIN)) {
                if (!$this->confirm('Are you sure you want to continue?')) {
                    $this->info('Operation cancelled.');
                    return 0;
                }
            } else {
                // Running from web, auto-confirm but log warning
                Log::warning('CleanupOrphanFiles: Auto-confirming from non-CLI environment');
                $this->warn('⚠️  Running from web environment - auto-confirming');
            }
        }
        
        $this->info('Starting orphan file cleanup...');
        
        // Get all active + trashed students (jangan hapus file siswa yang masih di recycle bin)
        $allStudents = data_siswa::withTrashed()->get();
        
        $fileColumns = [
            'foto_siswa',
            'dokumen_akta',
            'dokumen_kk',
            'dokumen_ijazah',
        ];
        
        // Collect all files yang masih digunakan
        $usedFiles = [];
        foreach ($allStudents as $siswa) {
            foreach ($fileColumns as $column) {
                if (!empty($siswa->$column)) {
                    $filePath = str_replace(['storage/', '/storage/'], '', $siswa->$column);
                    $usedFiles[] = $filePath;
                }
            }
        }
        
        $this->info('Found ' . count($usedFiles) . ' files in use by ' . $allStudents->count() . ' students');
        
        // Scan storage directories
        $directories = [
            'siswa/foto',
            'siswa/akta',
            'siswa/kk',
            'siswa/ijazah',
        ];
        
        $orphanFiles = [];
        $totalSize = 0;
        
        foreach ($directories as $directory) {
            if (Storage::disk('public')->exists($directory)) {
                $files = Storage::disk('public')->files($directory);
                
                foreach ($files as $file) {
                    if (!in_array($file, $usedFiles)) {
                        $orphanFiles[] = $file;
                        $totalSize += Storage::disk('public')->size($file);
                    }
                }
            }
        }
        
        if (empty($orphanFiles)) {
            $this->info('✅ No orphan files found. Storage is clean!');
            return 0;
        }
        
        $this->warn('Found ' . count($orphanFiles) . ' orphan files (' . $this->formatBytes($totalSize) . ')');
        
        // Display list
        $this->table(
            ['#', 'File Path', 'Size'],
            collect($orphanFiles)->map(fn($file, $index) => [
                $index + 1,
                $file,
                $this->formatBytes(Storage::disk('public')->size($file))
            ])->toArray()
        );
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN: These files would be deleted in actual run.');
            return 0;
        }
        
        // Delete orphan files
        $deleted = 0;
        $failed = 0;
        
        $progressBar = $this->output->createProgressBar(count($orphanFiles));
        $progressBar->start();
        
        foreach ($orphanFiles as $file) {
            try {
                Storage::disk('public')->delete($file);
                $deleted++;
                
                Log::info('Orphan file deleted', ['file' => $file]);
            } catch (\Exception $e) {
                $failed++;
                Log::error('Failed to delete orphan file', [
                    'file' => $file,
                    'error' => $e->getMessage()
                ]);
            }
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("✅ Cleanup completed!");
        $this->info("   Deleted: {$deleted} files");
        if ($failed > 0) {
            $this->error("   Failed: {$failed} files");
        }
        $this->info("   Freed space: " . $this->formatBytes($totalSize));
        
        return 0;
    }
    
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
