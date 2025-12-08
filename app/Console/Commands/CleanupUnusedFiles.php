<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\monthly_reports;
use App\Models\User;

class CleanupUnusedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:cleanup {--dry-run : Preview tanpa menghapus file} {--days= : Hapus file lebih dari N hari yang tidak terpakai}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membersihkan file foto yang tidak terpakai di storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Memindai file yang tidak terpakai...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $days = $this->option('days');

        // Kumpulkan semua file yang DIGUNAKAN di database
        $usedFiles = $this->getUsedFiles();

        $this->info('📊 Total file yang digunakan di database: ' . count($usedFiles));
        $this->newLine();

        // Scan folder storage/app/public/photos
        $allFiles = $this->getAllStorageFiles();
        $this->info('📁 Total file di storage: ' . count($allFiles));
        $this->newLine();

        // Cari file yang TIDAK digunakan
        $unusedFiles = array_diff($allFiles, $usedFiles);

        if ($days) {
            $unusedFiles = $this->filterByAge($unusedFiles, $days);
        }

        if (count($unusedFiles) === 0) {
            $this->info('✅ Tidak ada file yang tidak terpakai!');
            return 0;
        }

        $this->warn('⚠️  Ditemukan ' . count($unusedFiles) . ' file yang tidak terpakai:');
        $this->newLine();

        // Tampilkan list file
        $totalSize = 0;
        foreach ($unusedFiles as $file) {
            $filePath = storage_path('app/public/' . $file);
            if (file_exists($filePath)) {
                $size = filesize($filePath);
                $totalSize += $size;
                $this->line('  • ' . $file . ' (' . $this->formatBytes($size) . ')');
            }
        }

        $this->newLine();
        $this->info('💾 Total ukuran: ' . $this->formatBytes($totalSize));
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔸 Mode DRY-RUN: File tidak akan dihapus.');
            $this->info('   Jalankan tanpa --dry-run untuk menghapus file.');
            return 0;
        }

        // Konfirmasi sebelum menghapus
        if (!$this->confirm('Apakah Anda yakin ingin menghapus ' . count($unusedFiles) . ' file ini?', false)) {
            $this->info('❌ Dibatalkan.');
            return 0;
        }

        // Hapus file
        $deleted = 0;
        foreach ($unusedFiles as $file) {
            if (Storage::disk('public')->exists($file)) {
                Storage::disk('public')->delete($file);
                $deleted++;
            }
        }

        $this->info('✅ Berhasil menghapus ' . $deleted . ' file!');
        $this->info('💾 Ruang yang dibebaskan: ' . $this->formatBytes($totalSize));

        return 0;
    }

    /**
     * Kumpulkan semua file yang DIGUNAKAN di database
     */
    private function getUsedFiles(): array
    {
        $usedFiles = [];

        // 1. Dari monthly_reports (photos JSON)
        $this->line('  📸 Scanning monthly_reports.photos...');
        $reports = monthly_reports::whereNotNull('photos')->get();
        $reportCount = 0;
        foreach ($reports as $report) {
            $photos = is_string($report->photos) ? json_decode($report->photos, true) : $report->photos;
            if (is_array($photos)) {
                foreach ($photos as $photo) {
                    $usedFiles[] = $this->cleanPath($photo);
                    $reportCount++;
                }
            }
        }
        $this->line('    ✓ Found ' . $reportCount . ' photos');

        // 2. Dari users avatar
        $this->line('  👤 Scanning users.avatar...');
        $users = User::whereNotNull('avatar')
            ->whereNotIn('avatar', ['', 'null'])
            ->get();
        $avatarCount = 0;
        foreach ($users as $user) {
            if (!empty($user->avatar)) {
                $usedFiles[] = $this->cleanPath($user->avatar);
                $avatarCount++;
            }
        }
        $this->line('    ✓ Found ' . $avatarCount . ' avatars');

        return array_unique(array_filter($usedFiles));
    }

    /**
     * Ambil semua file di storage/app/public/photos
     */
    private function getAllStorageFiles(): array
    {
        $files = [];
        
        // Scan folder photos (recursive)
        if (Storage::disk('public')->exists('photos')) {
            $allFiles = Storage::disk('public')->allFiles('photos');
            foreach ($allFiles as $file) {
                // Skip .gitignore dan file hidden
                if (basename($file) !== '.gitignore' && !str_starts_with(basename($file), '.')) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    /**
     * Bersihkan path file (hapus storage/app/public/ atau /storage/)
     */
    private function cleanPath($path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = str_replace('/storage/', '', $path);
        $path = str_replace('storage/app/public/', '', $path);
        $path = ltrim($path, '/');
        return $path;
    }

    /**
     * Filter file berdasarkan umur
     */
    private function filterByAge($files, $days)
    {
        $threshold = now()->subDays($days)->timestamp;
        $filtered = [];

        foreach ($files as $file) {
            $filePath = storage_path('app/public/' . $file);
            if (file_exists($filePath)) {
                $fileTime = filemtime($filePath);
                if ($fileTime < $threshold) {
                    $filtered[] = $file;
                }
            }
        }

        return $filtered;
    }

    /**
     * Format bytes ke KB, MB, GB
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
