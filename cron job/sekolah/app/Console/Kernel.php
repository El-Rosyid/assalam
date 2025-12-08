<?php

namespace App\Console;

use App\Console\Commands\SendScheduledWhatsAppMessages;
use App\Console\Commands\TestWhatsAppBroadcast;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        SendScheduledWhatsAppMessages::class,
        TestWhatsAppBroadcast::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Schedule the command to send scheduled WhatsApp messages every minute
        $schedule->command('whatsapp:send-scheduled-messages')->everyMinute();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}