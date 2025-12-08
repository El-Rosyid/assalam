<?php

namespace App\Console\Commands;

use App\Models\data_siswa;
use Illuminate\Console\Command;

class CleanupDeletedStudents extends Command
{
    protected $signature = 'students:cleanup-deleted 
                            {--days=90 : Days to keep soft deleted records}
                            {--force : Actually perform the deletion}';

    protected $description = 'Permanently delete students that have been soft-deleted for specified days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $force = $this->option('force');
        
        $this->info("Finding students deleted more than {$days} days ago...");
        
        $students = data_siswa::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays($days))
            ->get();
        
        if ($students->isEmpty()) {
            $this->info('✅ No students to cleanup.');
            return Command::SUCCESS;
        }
        
        $this->table(
            ['NIS', 'Nama', 'Deleted At', 'Days Ago'],
            $students->map(fn($s) => [
                $s->nis,
                $s->nama_lengkap,
                $s->deleted_at->format('d/m/Y H:i'),
                $s->deleted_at->diffInDays(now()) . ' days'
            ])
        );
        
        $this->warn("Found {$students->count()} students to cleanup.");
        
        if (!$force) {
            $this->warn('⚠️  This is a DRY RUN. Use --force to actually delete.');
            $this->info('Files and related data will be permanently removed!');
            return Command::SUCCESS;
        }
        
        if (!$this->confirm('Are you sure you want to PERMANENTLY delete these students?')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }
        
        $deleted = 0;
        $failed = 0;
        
        foreach ($students as $student) {
            try {
                $nis = $student->nis;
                $nama = $student->nama_lengkap;
                
                // Force delete (will trigger boot events for cleanup)
                $student->forceDelete();
                
                $deleted++;
                $this->info("✅ Deleted: {$nis} - {$nama}");
                
            } catch (\Exception $e) {
                $failed++;
                $this->error("❌ Failed to delete {$student->nis}: " . $e->getMessage());
            }
        }
        
        $this->info("\n📊 Summary:");
        $this->info("✅ Deleted: {$deleted}");
        
        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
        }
        
        return Command::SUCCESS;
    }
}
