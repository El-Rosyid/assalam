<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WhatsAppFailedNotification extends Notification
{
    use Queueable;

    protected $siswaName;
    protected $error;
    protected $tries;

    public function __construct(string $siswaName, string $error, int $tries = 3)
    {
        $this->siswaName = $siswaName;
        $this->error = $error;
        $this->tries = $tries;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Gagal Mengirim WhatsApp',
            'body' => "Gagal mengirim notifikasi WhatsApp untuk siswa {$this->siswaName} setelah {$this->tries} percobaan. Error: {$this->error}",
            'icon' => 'heroicon-o-x-circle',
            'iconColor' => 'danger',
            'format' => 'filament',
            'actions' => [
                [
                    'name' => 'markAsRead',
                    'label' => '✓ Tandai Sudah Dibaca',
                    'color' => 'success',
                    'icon' => 'heroicon-o-check',
                    'close' => true
                ]
            ]
        ];
    }
}
