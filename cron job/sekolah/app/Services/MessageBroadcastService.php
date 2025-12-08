<?php

namespace App\Services;

use App\Models\ScheduledMessage;
use App\Models\MessageLog;
use App\Services\WhatsAppService;

class MessageBroadcastService
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function broadcastMessages()
    {
        $scheduledMessages = ScheduledMessage::where('scheduled_time', '<=', now())
            ->where('status', 'pending')
            ->get();

        foreach ($scheduledMessages as $message) {
            $this->sendMessage($message);
        }
    }

    protected function sendMessage(ScheduledMessage $message)
    {
        $response = $this->whatsAppService->sendMessage($message->recipient, $message->content);

        if ($response->isSuccessful()) {
            $this->logMessage($message, 'sent');
            $message->update(['status' => 'sent']);
        } else {
            $this->logMessage($message, 'failed', $response->getError());
            $message->update(['status' => 'failed']);
        }
    }

    protected function logMessage(ScheduledMessage $message, string $status, string $error = null)
    {
        MessageLog::create([
            'message_id' => $message->id,
            'recipient' => $message->recipient,
            'status' => $status,
            'error' => $error,
        ]);
    }
}