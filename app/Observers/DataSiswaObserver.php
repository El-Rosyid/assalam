<?php

namespace App\Observers;

use App\Models\data_siswa;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DataSiswaObserver
{
    /**
     * Handle the data_siswa "created" event.
     */
    public function created(data_siswa $data_siswa): void
    {
        //
    }
    
    /**
     * Handle the data_siswa "updating" event.
     * Dipanggil SEBELUM data diupdate - untuk auto-delete old files
     */
    public function updating(data_siswa $siswa): void
    {
        // Get original values (sebelum update)
        $original = $siswa->getOriginal();
        
        // File columns yang perlu dimonitor
        $fileColumns = [
            'foto_siswa',
            'dokumen_akta',
            'dokumen_kk',
            'dokumen_ijazah',
        ];
        
        foreach ($fileColumns as $column) {
            // Cek apakah file berubah
            if ($siswa->isDirty($column)) {
                $oldFile = $original[$column] ?? null;
                $newFile = $siswa->$column;
                
                // Jika ada file lama DAN file baru berbeda
                if (!empty($oldFile) && $oldFile !== $newFile) {
                    $this->moveOldFileToTrash($oldFile, $siswa->nis, $column);
                }
            }
        }
    }

    /**
     * Handle the data_siswa "updated" event.
     */
    public function updated(data_siswa $data_siswa): void
    {
        //
    }

    /**
     * Handle the data_siswa "deleted" event.
     */
    public function deleted(data_siswa $data_siswa): void
    {
        //
    }

    /**
     * Handle the data_siswa "restored" event.
     */
    public function restored(data_siswa $data_siswa): void
    {
        //
    }

    /**
     * Handle the data_siswa "force deleted" event.
     * Delete associated User account when student is permanently deleted
     */
    public function forceDeleted(data_siswa $data_siswa): void
    {
        try {
            // Hapus User account jika ada
            if ($data_siswa->user) {
                $data_siswa->user->delete();
                Log::info('User account deleted with siswa', [
                    'nis' => $data_siswa->nis,
                    'user_id' => $data_siswa->user_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete user account with siswa', [
                'nis' => $data_siswa->nis,
                'user_id' => $data_siswa->user_id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Move old file to .trash folder (soft delete for 7 days)
     */
    private function moveOldFileToTrash(string $filePath, string $nis, string $column): void
    {
        try {
            // Clean path
            $filePath = str_replace(['storage/', '/storage/'], '', $filePath);
            
            // Check if file exists
            if (!Storage::disk('public')->exists($filePath)) {
                return;
            }
            
            // Get file size before moving
            $fileSize = Storage::disk('public')->size($filePath);
            
            // Create trash path with date folder
            $trashPath = '.trash/' . date('Y-m-d') . '/' . $nis . '_' . basename($filePath);
            
            // Move file to trash
            Storage::disk('public')->move($filePath, $trashPath);
            
            // Log the move
            Log::info("Old file moved to trash on update", [
                'nis' => $nis,
                'column' => $column,
                'from' => $filePath,
                'to' => $trashPath,
                'size' => $this->formatBytes($fileSize),
                'user' => auth()->id(),
            ]);
            
        } catch (\Exception $e) {
            // Log error but don't fail the update
            Log::error("Failed to move old file to trash", [
                'nis' => $nis,
                'column' => $column,
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
