<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $recipient;
    protected $message;

    public function __construct(string $recipient, string $message)
    {
        $this->recipient = $recipient;
        $this->message = $message;
    }

    public function handle(WhatsAppService $whatsAppService)
    {
        $whatsAppService->sendMessage($this->recipient, $this->message);
    }
}