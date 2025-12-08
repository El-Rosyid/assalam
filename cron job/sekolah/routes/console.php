<?php

use Illuminate\Foundation\Inspiring;
use App\Console\Commands\SendScheduledWhatsAppMessages;
use App\Console\Commands\TestWhatsAppBroadcast;

Artisan::command('whatsapp:send-scheduled', function () {
    $this->call(SendScheduledWhatsAppMessages::class);
})->describe('Send scheduled WhatsApp messages');

Artisan::command('whatsapp:test-broadcast', function () {
    $this->call(TestWhatsAppBroadcast::class);
})->describe('Test WhatsApp broadcasting functionality');