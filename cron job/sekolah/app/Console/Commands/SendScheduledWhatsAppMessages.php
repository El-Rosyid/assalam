<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledMessage;
use App\Jobs\SendWhatsAppMessageJob;

class SendScheduledWhatsAppMessages extends Command
{
    protected $signature = 'whatsapp:send-scheduled-messages';
    protected $description = 'Send scheduled WhatsApp messages';

    public function handle()
    {
        $scheduledMessages = ScheduledMessage::where('scheduled_time', '<=', now())
            ->where('status', 'pending')
            ->get();

        foreach ($scheduledMessages as $message) {
            SendWhatsAppMessageJob::dispatch($message);
            $message->update(['status' => 'sent']);
        }

        $this->info('Scheduled WhatsApp messages sent successfully.');
    }
}