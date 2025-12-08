<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddNotificationActions extends Command
{
    protected $signature = 'notification:add-actions';
    protected $description = 'Add action buttons to existing notifications';

    public function handle()
    {
        $this->info("🔧 ADDING ACTION BUTTONS TO NOTIFICATIONS");
        $this->newLine();
        
        $notifications = DB::table('notifications')
            ->whereNull('read_at') // Only unread notifications
            ->get();
        
        if ($notifications->isEmpty()) {
            $this->warn('No unread notifications found');
            return Command::SUCCESS;
        }
        
        $updated = 0;
        
        foreach ($notifications as $notification) {
            $data = json_decode($notification->data, true);
            
            // Check if actions already exist
            if (isset($data['actions'])) {
                continue;
            }
            
            // Add mark as read action
            $data['actions'] = [
                [
                    'name' => 'markAsRead',
                    'label' => '✓ Tandai Sudah Dibaca',
                    'color' => 'success',
                    'icon' => 'heroicon-o-check',
                    'close' => true
                ]
            ];
            
            // If notification has actionUrl, add view action as well
            if (isset($data['actionUrl'])) {
                array_unshift($data['actions'], [
                    'name' => 'view',
                    'label' => $data['actionLabel'] ?? 'Lihat Detail',
                    'url' => $data['actionUrl'],
                    'color' => 'primary',
                    'icon' => 'heroicon-o-eye'
                ]);
            }
            
            // Update database
            DB::table('notifications')
                ->where('id', $notification->id)
                ->update(['data' => json_encode($data)]);
            
            $updated++;
            $this->line("✓ Added actions to: " . ($data['title'] ?? 'No title'));
        }
        
        $this->newLine();
        $this->info("✅ Updated {$updated} notifications with action buttons");
        $this->info("💡 Admin sekarang harus klik tombol '✓ Tandai Sudah Dibaca' untuk mark as read");
        
        return Command::SUCCESS;
    }
}