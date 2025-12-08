<?php

namespace App\Notifications;

class InvalidPhoneNumberNotification extends BaseAdminNotification
{
    public function __construct(string $siswaName, ?string $phoneNumber = null)
    {
        $title = 'Nomor Telepon Tidak Valid';
        
        if ($phoneNumber) {
            $body = "Nomor telepon siswa {$siswaName} ({$phoneNumber}) tidak valid untuk pengiriman WhatsApp.";
        } else {
            $body = "Siswa {$siswaName} tidak memiliki nomor telepon. WhatsApp tidak dapat dikirim.";
        }
        
        $actions = [
            [
                'name' => 'checkPhone',
                'url' => '/admin/data-siswa?tableSearch=' . urlencode($siswaName),
                'color' => 'primary'
            ]
        ];

        parent::__construct(
            $title, 
            $body, 
            'heroicon-o-exclamation-triangle', 
            'warning',
            $actions
        );
    }
    
    public function toArray($notifiable): array
    {
        $baseArray = parent::toArray($notifiable);
        
        // Add specific data for deduplication
        $baseArray['type'] = 'invalid_phone_number';
        $baseArray['created_at'] = now();
        
        return $baseArray;
    }
}
