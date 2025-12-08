<?php

namespace App\Observers;

use App\Models\data_guru;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DataGuruObserver
{
    /**
     * Handle the data_guru "updating" event.
     * Auto-delete old avatar when uploading new one
     */
    public function updating(data_guru $guru): void
    {
        // Check if user avatar field is changing
        if ($guru->user && $guru->user->isDirty('avatar')) {
            $oldAvatar = $guru->user->getOriginal('avatar');
            
            if (!empty($oldAvatar)) {
                $this->moveOldFileToTrash($oldAvatar, $guru->guru_id, 'avatar');
            }
        }
    }
    
    /**
     * Handle the data_guru "deleting" event.
     * Auto-delete avatar when record is deleted
     */
    public function deleting(data_guru $guru): void
    {
        // Cleanup files when deleting
        $this->cleanupFiles($guru);
    }
    
    /**
     * Move old file to .trash folder
     */
    protected function moveOldFileToTrash(string $filePath, int $guruId, string $column): void
    {
        try {
            // Remove storage/ prefix if exists
            $filePath = str_replace(['storage/', '/storage/'], '', $filePath);
            
            if (Storage::disk('public')->exists($filePath)) {
                $fileSize = Storage::disk('public')->size($filePath);
                $fileName = basename($filePath);
                
                // Create .trash folder with date
                $trashFolder = '.trash/' . date('Y-m-d');
                Storage::disk('public')->makeDirectory($trashFolder);
                
                // Move file to trash
                $trashPath = $trashFolder . '/' . $fileName;
                
                // If file exists in trash, append timestamp
                if (Storage::disk('public')->exists($trashPath)) {
                    $trashPath = $trashFolder . '/' . time() . '_' . $fileName;
                }
                
                Storage::disk('public')->move($filePath, $trashPath);
                
                Log::info('Old guru avatar moved to trash', [
                    'guru_id' => $guruId,
                    'column' => $column,
                    'old_file' => $filePath,
                    'trash_path' => $trashPath,
                    'size' => $this->formatBytes($fileSize),
                    'user_id' => auth()->id()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to move guru avatar to trash', [
                'guru_id' => $guruId,
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Cleanup all files when permanently deleting
     */
    protected function cleanupFiles(data_guru $guru): void
    {
        try {
            // Delete user avatar if exists
            if ($guru->user && !empty($guru->user->avatar)) {
                $avatarPath = str_replace(['storage/', '/storage/'], '', $guru->user->avatar);
                
                if (Storage::disk('public')->exists($avatarPath)) {
                    Storage::disk('public')->delete($avatarPath);
                    Log::info("Deleted guru avatar on force delete", [
                        'guru_id' => $guru->guru_id,
                        'file' => $avatarPath
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to cleanup guru files', [
                'guru_id' => $guru->guru_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the data_guru "force deleted" event.
     * Delete associated User account when teacher is permanently deleted
     */
    public function forceDeleted(data_guru $guru): void
    {
        try {
            // Hapus User account jika ada
            if ($guru->user) {
                $guru->user->delete();
                Log::info('User account deleted with guru', [
                    'guru_id' => $guru->guru_id,
                    'user_id' => $guru->user_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete user account with guru', [
                'guru_id' => $guru->guru_id,
                'user_id' => $guru->user_id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return number_format($bytes / 1024, 2) . ' KB';
        return number_format($bytes / 1048576, 2) . ' MB';
    }
}
