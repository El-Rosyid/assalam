<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Filament\Notifications\Notification;

class DatabaseNotificationTest extends Command
{
    protected $signature = 'test:db-notification {user_id?}';
    protected $description = 'Test database notification for Filament';

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        if ($userId) {
            $users = collect([User::find($userId)]);
        } else {
            // Get ALL admin users (super_admin dan admin)
            $users = User::whereHas('roles', function($query) {
                $query->whereIn('name', ['super_admin', 'admin']);
            })->get();
        }
        
        if ($users->isEmpty()) {
            $this->error('❌ User tidak ditemukan');
            return Command::FAILURE;
        }
        
        $this->info("📧 Mengirim notifikasi test ke {$users->count()} admin user(s):");
        foreach ($users as $user) {
            $this->line("   • {$user->name} (ID: {$user->id})");
        }
        $this->newLine();
        
        // Send test notification to ALL admin users
        Notification::make()
            ->title('🔔 Test Notification')
            ->body('Ini adalah test notifikasi database. Jika Anda melihat ini di dashboard, maka notifikasi berfungsi dengan baik!')
            ->icon('heroicon-o-bell')
            ->iconColor('success')
            ->success()
            ->sendToDatabase($users);
        
        $this->newLine();
        $this->info('✅ Notifikasi berhasil dikirim ke semua admin!');
        $this->newLine();
        
        // Show notification stats for each user
        $this->info('📊 STATISTIK NOTIFIKASI PER USER:');
        foreach ($users as $user) {
            $this->line("   {$user->name} (ID: {$user->id}):");
            $this->line("   • Unread: " . $user->unreadNotifications()->count());
            $this->line("   • Total: " . $user->notifications()->count());
            $this->newLine();
        }
        
        $this->newLine();
        $this->info('📋 DAFTAR NOTIFIKASI UNREAD:');
        
        foreach ($users as $user) {
            $unread = $user->unreadNotifications()->get();
            
            if ($unread->isEmpty()) {
                $this->line("   {$user->name}: Tidak ada notifikasi unread");
            } else {
                $this->line("   {$user->name}:");
                foreach ($unread->take(3) as $notification) {
                    $data = $notification->data;
                    $title = isset($data['title']) ? $data['title'] : 'No Title';
                    $this->line("      • [{$notification->created_at->diffForHumans()}] {$title}");
                    if (isset($data['body'])) {
                        $this->line("        {$data['body']}");
                    }
                }
            }
            $this->newLine();
        }
        
        $this->newLine();
        $this->info('💡 CARA CEK DI DASHBOARD:');
        $this->line('   1. Login ke /admin');
        $this->line('   2. Klik icon lonceng di navbar kanan atas');
        $this->line('   3. Notifikasi harus muncul di dropdown');
        
        return Command::SUCCESS;
    }
}
