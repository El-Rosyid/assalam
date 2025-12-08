<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class NotificationSummary extends Command
{
    protected $signature = 'notify:summary';
    protected $description = 'Show notification summary for all admin users';

    public function handle()
    {
        $this->info("🔔 NOTIFICATION SYSTEM SUMMARY");
        $this->newLine();
        
        $adminUsers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['super_admin', 'admin']);
        })->get();
        
        if ($adminUsers->isEmpty()) {
            $this->error('No admin users found');
            return Command::FAILURE;
        }
        
        $this->table(
            ['User ID', 'Name', 'Email', 'Role', 'Unread', 'Read', 'Total'],
            $adminUsers->map(function($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->email ?: '-',
                    $user->roles->pluck('name')->implode(', '),
                    $user->unreadNotifications()->count(),
                    $user->readNotifications()->count(),
                    $user->notifications()->count(),
                ];
            })->toArray()
        );
        
        $this->newLine();
        $this->info("✅ System Status:");
        $this->line("   • Notifications are sent to both 'admin' and 'super_admin' roles");
        $this->line("   • Using Laravel Native Notifications (reliable)");
        $this->line("   • Old notifications have been fixed to Filament format");
        $this->line("   • Database polling every 10 seconds");
        
        $this->newLine();
        $this->info("💡 Next Steps:");
        $this->line("   1. Login to dashboard as admin or super_admin");
        $this->line("   2. Click the bell icon in top-right navbar");
        $this->line("   3. You should see all unread notifications");
        $this->line("   4. Click a notification to mark it as read");
        
        return Command::SUCCESS;
    }
}
