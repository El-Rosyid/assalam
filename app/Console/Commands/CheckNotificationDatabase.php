<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class CheckNotificationDatabase extends Command
{
    protected $signature = 'check:notifications {user_id?}';
    protected $description = 'Check notifications in database';

    public function handle()
    {
        $userId = $this->argument('user_id') ?? 14; // default super_admin
        
        $this->info("🔍 CHECKING NOTIFICATIONS DATABASE");
        $this->newLine();
        
        // Total notifications
        $total = DB::table('notifications')->count();
        $this->info("📊 Total notifications in database: {$total}");
        
        // Notifications for specific user
        $userNotifs = DB::table('notifications')
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', 'App\\Models\\User')
            ->count();
        
        $this->info("👤 Notifications for user ID {$userId}: {$userNotifs}");
        
        $this->newLine();
        $this->info("📋 LATEST 5 NOTIFICATIONS (ALL USERS):");
        
        $notifications = DB::table('notifications')
            ->latest('created_at')
            ->limit(5)
            ->get();
        
        if ($notifications->isEmpty()) {
            $this->warn('   Tidak ada notifikasi di database');
        } else {
            $this->table(
                ['ID', 'User ID', 'Type', 'Created', 'Read At'],
                $notifications->map(function($n) {
                    return [
                        substr($n->id, 0, 8) . '...',
                        $n->notifiable_id,
                        class_basename($n->type),
                        $n->created_at,
                        $n->read_at ?? 'NULL'
                    ];
                })->toArray()
            );
        }
        
        $this->newLine();
        $this->info("📋 USING ELOQUENT (User Model):");
        
        $user = User::find($userId);
        if ($user) {
            $this->info("   User: {$user->name}");
            $this->info("   Unread: " . $user->unreadNotifications()->count());
            $this->info("   Read: " . $user->readNotifications()->count());
            $this->info("   Total: " . $user->notifications()->count());
            
            $this->newLine();
            $this->info("   Latest 3 notifications:");
            foreach ($user->notifications()->latest()->take(3)->get() as $notif) {
                $data = $notif->data;
                $title = isset($data['title']) ? $data['title'] : 'No title';
                $this->line("   • {$title} [{$notif->created_at->diffForHumans()}]");
            }
        } else {
            $this->error("   User ID {$userId} not found");
        }
        
        return Command::SUCCESS;
    }
}
