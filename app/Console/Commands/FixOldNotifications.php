<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixOldNotifications extends Command
{
    protected $signature = 'fix:old-notifications';
    protected $description = 'Fix old notification data structure to be compatible with Filament';

    public function handle()
    {
        $this->info("🔧 FIXING OLD NOTIFICATIONS");
        $this->newLine();
        
        // Get all notifications
        $notifications = DB::table('notifications')->get();
        
        $fixed = 0;
        $skipped = 0;
        
        foreach ($notifications as $notification) {
            $data = json_decode($notification->data, true);
            
            // Check if already has correct format
            if (isset($data['format']) && $data['format'] === 'filament') {
                $skipped++;
                continue;
            }
            
            // Fix structure
            $newData = [];
            
            // Handle title
            if (isset($data['title'])) {
                $newData['title'] = $data['title'];
            }
            
            // Handle body (some use 'message', some use 'body')
            if (isset($data['body'])) {
                $newData['body'] = $data['body'];
            } elseif (isset($data['message'])) {
                $newData['body'] = $data['message'];
            }
            
            // Handle icon
            if (isset($data['icon'])) {
                $newData['icon'] = $data['icon'];
            } else {
                $newData['icon'] = 'heroicon-o-bell';
            }
            
            // Handle iconColor
            if (isset($data['iconColor'])) {
                $newData['iconColor'] = $data['iconColor'];
            } else {
                $newData['iconColor'] = 'info';
            }
            
            // Add format marker
            $newData['format'] = 'filament';
            
            // Keep action URL if exists (flatten it)
            if (isset($data['actions'][0]['url'])) {
                $newData['actionUrl'] = $data['actions'][0]['url'];
                $newData['actionLabel'] = $data['actions'][0]['label'] ?? 'Lihat Detail';
            } elseif (isset($data['data']['action_url'])) {
                $newData['actionUrl'] = $data['data']['action_url'];
                $newData['actionLabel'] = 'Lihat Detail';
            }
            
            // Update database
            DB::table('notifications')
                ->where('id', $notification->id)
                ->update(['data' => json_encode($newData)]);
            
            $fixed++;
            $this->line("✓ Fixed: " . substr($notification->id, 0, 8) . "... - " . ($newData['title'] ?? 'No title'));
        }
        
        $this->newLine();
        $this->info("✅ Fixed: {$fixed} notifications");
        $this->info("⏭️  Skipped: {$skipped} notifications (already correct)");
        $this->newLine();
        $this->info("💡 Silakan refresh dashboard dan cek notifikasi lagi!");
        
        return Command::SUCCESS;
    }
}
