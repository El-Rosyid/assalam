<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Notifications\TestDebugNotification;

class RoleBasedNotificationTest extends Command
{
    protected $signature = 'test:role-notification';
    protected $description = 'Test sending notification based on roles (admin & super_admin)';

    public function handle()
    {
        $this->info("🔔 TESTING ROLE-BASED NOTIFICATION");
        $this->newLine();
        
        // Get ALL users with admin or super_admin role
        $adminUsers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['super_admin', 'admin']);
        })->get();
        
        if ($adminUsers->isEmpty()) {
            $this->error('❌ Tidak ada user dengan role admin/super_admin');
            return Command::FAILURE;
        }
        
        $this->info("👥 Target Users ({$adminUsers->count()} user):");
        foreach ($adminUsers as $user) {
            $roles = $user->roles->pluck('name')->implode(', ');
            $this->line("   • {$user->name} (ID: {$user->id}) - Role: {$roles}");
        }
        $this->newLine();
        
        // BEFORE stats
        $this->info("📊 BEFORE - Notification Count:");
        foreach ($adminUsers as $user) {
            $this->line("   {$user->name}: Unread = " . $user->unreadNotifications()->count() . ", Total = " . $user->notifications()->count());
        }
        $this->newLine();
        
        // Send notification using Laravel Native
        $this->info("📤 Sending notification using LARAVEL NATIVE...");
        
        $notificationData = [
            'title' => '✅ Role-Based Test Notification',
            'body' => 'Notifikasi ini dikirim ke SEMUA user dengan role admin dan super_admin. Dikirim pada: ' . now()->format('Y-m-d H:i:s'),
            'icon' => 'heroicon-o-check-circle',
            'iconColor' => 'success',
            'format' => 'filament',
        ];
        
        $successCount = 0;
        foreach ($adminUsers as $user) {
            try {
                $user->notify(new TestDebugNotification($notificationData));
                $successCount++;
                $this->line("   ✓ Sent to {$user->name}");
            } catch (\Exception $e) {
                $this->error("   ✗ Failed for {$user->name}: " . $e->getMessage());
            }
        }
        
        $this->newLine();
        $this->info("✅ Successfully sent to {$successCount}/{$adminUsers->count()} users");
        $this->newLine();
        
        // AFTER stats
        $this->info("📊 AFTER - Notification Count:");
        foreach ($adminUsers as $user) {
            $user->refresh(); // Reload from database
            $unread = $user->unreadNotifications()->count();
            $total = $user->notifications()->count();
            $this->line("   {$user->name}: Unread = {$unread}, Total = {$total}");
        }
        $this->newLine();
        
        // Show latest notifications
        $this->info("📋 LATEST NOTIFICATIONS:");
        foreach ($adminUsers as $user) {
            $latest = $user->notifications()->latest()->first();
            if ($latest) {
                $data = $latest->data;
                $title = isset($data['title']) ? $data['title'] : 'No title';
                $this->line("   {$user->name}: {$title} [{$latest->created_at->diffForHumans()}]");
            }
        }
        
        $this->newLine();
        $this->info("💡 Silakan login ke dashboard dan cek lonceng notifikasi!");
        
        return Command::SUCCESS;
    }
}
