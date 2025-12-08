<?php

namespace App\Jobs;

use App\Services\MessageBroadcastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastWhatsAppMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $messageContent;
    protected $recipients;

    public function __construct(string $messageContent, array $recipients)
    {
        $this->messageContent = $messageContent;
        $this->recipients = $recipients;
    }

    public function handle(MessageBroadcastService $broadcastService)
    {
        foreach ($this->recipients as $recipient) {
            $broadcastService->sendMessage($recipient, $this->messageContent);
        }
    }
}