<?php

namespace App\Filament\Pages;

use App\Models\data_siswa;
use Filament\Pages\Page;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StorageManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    
    protected static ?string $navigationLabel = 'Storage Management';
    
    protected static ?string $title = 'Storage Management';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.storage-management';
    
    /**
     * Authorization: Super Admin only
     */
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }
    
    /**
     * Header Actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('scan')
                ->label('Scan Orphan Files')
                ->icon('heroicon-o-magnifying-glass')
                ->color('gray')
                ->modalHeading('Scan Orphan Files')
                ->modalDescription('Preview files that have no owner and can be safely deleted.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->action(function () {
                    // Akan dihandle di modal content
                })
                ->modalContent(view('filament.modals.orphan-scan-results')),
            
            Actions\Action::make('cleanup')
                ->label('Cleanup Orphan Files')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete Orphan Files?')
                ->modalDescription('This will permanently delete files that have no owner. This action cannot be undone!')
                ->modalSubmitActionLabel('Yes, Delete Orphan Files')
                ->action(function () {
                    try {
                        Artisan::call('storage:cleanup-orphan-files');
                        $output = Artisan::output();
                        
                        Notification::make()
                            ->title('Cleanup Completed!')
                            ->body('Orphan files have been deleted successfully.')
                            ->success()
                            ->send();
                            
                        Log::info('Manual orphan cleanup triggered', ['user' => auth()->id()]);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Cleanup Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Actions\Action::make('cleanupTrash')
                ->label('Cleanup .trash Folder')
                ->icon('heroicon-o-archive-box-x-mark')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Delete .trash Files?')
                ->modalDescription('This will delete all files in .trash folder (including recent ones). Only use if you are sure!')
                ->action(function () {
                    $trashDirs = Storage::disk('public')->directories('.trash');
                    $count = 0;
                    
                    foreach ($trashDirs as $dir) {
                        $files = Storage::disk('public')->allFiles($dir);
                        $count += count($files);
                        Storage::disk('public')->deleteDirectory($dir);
                    }
                    
                    Notification::make()
                        ->title('.trash Cleaned')
                        ->body("{$count} files deleted from .trash folder")
                        ->success()
                        ->send();
                        
                    Log::info('Manual .trash cleanup', ['user' => auth()->id(), 'files' => $count]);
                }),
        ];
    }
    
    /**
     * Get view data
     */
    protected function getViewData(): array
    {
        return [
            'storageStats' => $this->getStorageStats(),
            'studentStats' => $this->getStudentStats(),
            'trashedStudents' => $this->getTrashedStudents(),
            'scheduledTasks' => $this->getScheduledTasks(),
            'trashStats' => $this->getTrashStats(),
        ];
    }
    
    /**
     * Get storage statistics
     */
    private function getStorageStats(): array
    {
        $directories = [
            'siswa/foto' => 'Foto Siswa',
            'siswa/akta' => 'Akta',
            'siswa/kk' => 'KK',
            'siswa/ijazah' => 'Ijazah',
            'custom-broadcasts' => 'Broadcast',
            '.trash' => '.trash (Temp)',
        ];
        
        $stats = [];
        $totalSize = 0;
        
        foreach ($directories as $dir => $label) {
            $size = $this->getDirectorySize($dir);
            $stats[] = [
                'label' => $label,
                'size' => $this->formatBytes($size),
                'raw_size' => $size,
            ];
            $totalSize += $size;
        }
        
        $stats[] = [
            'label' => 'Total',
            'size' => $this->formatBytes($totalSize),
            'raw_size' => $totalSize,
        ];
        
        return $stats;
    }
    
    /**
     * Get directory size
     */
    private function getDirectorySize(string $directory): int
    {
        if (!Storage::disk('public')->exists($directory)) {
            return 0;
        }
        
        $files = Storage::disk('public')->allFiles($directory);
        $size = 0;
        
        foreach ($files as $file) {
            $size += Storage::disk('public')->size($file);
        }
        
        return $size;
    }
    
    /**
     * Get student statistics
     */
    private function getStudentStats(): array
    {
        return [
            'active' => data_siswa::count(),
            'trashed' => data_siswa::onlyTrashed()->count(),
            'total' => data_siswa::withTrashed()->count(),
        ];
    }
    
    /**
     * Get trashed students
     */
    private function getTrashedStudents()
    {
        return data_siswa::onlyTrashed()
            ->select(['nis', 'nama_lengkap', 'deleted_at'])
            ->orderBy('deleted_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($s) => [
                'nis' => $s->nis,
                'nama' => $s->nama_lengkap,
                'deleted_days' => now()->diffInDays($s->deleted_at),
                'auto_delete_in' => max(0, 90 - now()->diffInDays($s->deleted_at)),
            ]);
    }
    
    /**
     * Get scheduled tasks info
     */
    private function getScheduledTasks(): array
    {
        return [
            [
                'name' => 'Auto-Prune Students',
                'schedule' => 'Daily at 03:00',
                'description' => 'Force delete students >90 days in recycle bin',
                'status' => 'active',
            ],
            [
                'name' => 'Orphan Files Cleanup',
                'schedule' => 'Weekly Sunday at 02:00',
                'description' => 'Delete files without owner',
                'status' => 'active',
            ],
            [
                'name' => 'Trash Folder Cleanup',
                'schedule' => 'Daily at 04:00',
                'description' => 'Delete .trash files older than 7 days',
                'status' => 'active',
            ],
        ];
    }
    
    /**
     * Get .trash folder stats
     */
    private function getTrashStats(): array
    {
        $trashDirs = Storage::disk('public')->directories('.trash');
        $totalFiles = 0;
        $totalSize = 0;
        $oldestDate = null;
        
        foreach ($trashDirs as $dir) {
            $files = Storage::disk('public')->allFiles($dir);
            $totalFiles += count($files);
            
            foreach ($files as $file) {
                $totalSize += Storage::disk('public')->size($file);
            }
            
            $date = basename($dir);
            if (!$oldestDate || $date < $oldestDate) {
                $oldestDate = $date;
            }
        }
        
        return [
            'total_files' => $totalFiles,
            'total_size' => $this->formatBytes($totalSize),
            'oldest_date' => $oldestDate,
            'folders_count' => count($trashDirs),
        ];
    }
    
    /**
     * Format bytes
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Restore student action (called from view)
     */
    public function restoreStudent(string $nis): void
    {
        try {
            $siswa = data_siswa::onlyTrashed()->where('nis', $nis)->first();
            
            if ($siswa) {
                $siswa->restore();
                
                Notification::make()
                    ->title('Student Restored')
                    ->body("Student {$siswa->nama_lengkap} has been restored successfully.")
                    ->success()
                    ->send();
                    
                Log::info('Student restored from UI', ['nis' => $nis, 'user' => auth()->id()]);
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Restore Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    /**
     * Force delete student action (called from view)
     */
    public function forceDeleteStudent(string $nis): void
    {
        try {
            $siswa = data_siswa::onlyTrashed()->where('nis', $nis)->first();
            
            if ($siswa) {
                $nama = $siswa->nama_lengkap;
                $siswa->forceDelete();
                
                Notification::make()
                    ->title('Student Permanently Deleted')
                    ->body("Student {$nama} and all files have been deleted permanently.")
                    ->warning()
                    ->send();
                    
                Log::warning('Student force deleted from UI', ['nis' => $nis, 'user' => auth()->id()]);
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Delete Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
