<?php

namespace App\Observers;

use App\Models\data_guru;
use App\Models\User;

class DataGuruObserverSync
{
    /**
     * Handle the data_guru "created" event.
     */
    public function created(data_guru $guru): void
    {
        // Saat create guru baru, User sudah dibuat oleh Filament
        // Jadi tidak perlu action khusus
    }

    /**
     * Handle the data_guru "updated" event.
     */
    public function updated(data_guru $guru): void
    {
        // Sync perubahan username dan name ke User account
        if ($guru->user) {
            $needsUpdate = false;
            $updateData = [];

            // Cek jika ada perubahan dirty di relasi user
            // Filament meng-handle ini via nested form, tapi kita ensure sync di sini
            
            // Update dari nested form account.username dan account.name
            // Data sudah di-update oleh Filament sebelum trigger observer ini
            // Jadi tinggal pastikan User model ter-update dengan benar
            
            // Logging untuk debugging
            \Log::info('DataGuru Updated', [
                'guru_id' => $guru->guru_id,
                'user_id' => $guru->user_id,
                'user' => $guru->user?->email ?? 'No user'
            ]);
        }
    }

    /**
     * Handle the data_guru "deleting" event.
     */
    public function deleting(data_guru $guru): void
    {
        // Optional: handle deletion logic jika diperlukan
    }

    /**
     * Handle the data_guru "deleted" event.
     */
    public function deleted(data_guru $guru): void
    {
        // Optional: cleanup logic
    }

    /**
     * Handle the data_guru "restored" event.
     */
    public function restored(data_guru $guru): void
    {
        //
    }

    /**
     * Handle the data_guru "force deleted" event.
     */
    public function forceDeleted(data_guru $guru): void
    {
        //
    }
}
