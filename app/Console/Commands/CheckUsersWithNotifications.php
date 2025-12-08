<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CheckUsersWithNotifications extends Command
{
    protected $signature = 'check:users';
    protected $description = 'Check all users with super_admin role and their notifications';

    public function handle()
    {
        $this->info("👥 USERS WITH SUPER_ADMIN ROLE:");
        $this->newLine();
        
        $superAdmins = User::whereHas('roles', function($query) {
            $query->where('name', 'super_admin');
        })->get();
        
        foreach ($superAdmins as $user) {
            $this->info("ID: {$user->id}");
            $this->info("Name: {$user->name}");
            $this->info("Email: {$user->email}");
            $this->info("Unread Notifications: " . $user->unreadNotifications()->count());
            $this->info("Total Notifications: " . $user->notifications()->count());
            $this->newLine();
        }
        
        $this->info("👥 ALL ADMIN USERS:");
        $this->newLine();
        
        $admins = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['super_admin', 'admin']);
        })->get();
        
        $this->table(
            ['ID', 'Name', 'Email', 'Roles', 'Notifications'],
            $admins->map(function($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->roles->pluck('name')->implode(', '),
                    $user->notifications()->count()
                ];
            })->toArray()
        );
        
        return Command::SUCCESS;
    }
}
