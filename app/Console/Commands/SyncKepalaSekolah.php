<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sekolah;
use App\Models\data_guru;

class SyncKepalaSekolah extends Command
{
    protected $signature = 'sync:kepala-sekolah';
    protected $description = 'Sync kepala sekolah data dari text field ke foreign key';

    public function handle()
    {
        $sekolah = sekolah::first();
        
        if (!$sekolah) {
            $this->error('No sekolah data found!');
            return;
        }
        
        $this->info("Current Kepala Sekolah: {$sekolah->kepala_sekolah}");
        $this->info("Current NIP: {$sekolah->nip_kepala_sekolah}");
        
        if ($sekolah->kepala_sekolah_id) {
            $this->warn('Kepala sekolah ID already set!');
            $guru = $sekolah->kepalaSekolah;
            if ($guru) {
                $this->info("Linked to: {$guru->nama_lengkap} (ID: {$guru->id})");
            }
            return;
        }
        
        // Coba cari guru berdasarkan NIP
        if ($sekolah->nip_kepala_sekolah) {
            $guru = data_guru::where('nip', $sekolah->nip_kepala_sekolah)->first();
            
            if ($guru) {
                $sekolah->kepala_sekolah_id = $guru->id;
                $sekolah->save();
                
                $this->info("✅ Successfully linked to: {$guru->nama_lengkap} (ID: {$guru->id})");
            } else {
                $this->warn("⚠ No guru found with NIP: {$sekolah->nip_kepala_sekolah}");
                $this->info('Please select kepala sekolah manually from the form.');
            }
        } else {
            $this->warn('No NIP found. Please select kepala sekolah from the form.');
        }
    }
}
