<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MessageBroadcastService;

class TestWhatsAppBroadcast extends Command
{
    protected $signature = 'whatsapp:test-broadcast {message} {recipients*}';
    protected $description = 'Test the WhatsApp broadcasting functionality';

    protected $broadcastService;

    public function __construct(MessageBroadcastService $broadcastService)
    {
        parent::__construct();
        $this->broadcastService = $broadcastService;
    }

    public function handle()
    {
        $message = $this->argument('message');
        $recipients = $this->argument('recipients');

        $this->info("Testing broadcast to: " . implode(', ', $recipients));

        try {
            $this->broadcastService->broadcast($message, $recipients);
            $this->info("Broadcast successful!");
        } catch (\Exception $e) {
            $this->error("Broadcast failed: " . $e->getMessage());
        }
    }
}