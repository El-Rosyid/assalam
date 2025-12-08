<?php

namespace App\Notifications;

use App\Models\data_guru;
use App\Models\data_kelas;

class GrowthRecordCompletedNotification extends BaseAdminNotification
{
    public function __construct(data_guru $guru, data_kelas $kelas, int $month, int $totalStudents)
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        $monthName = $months[$month] ?? 'Unknown';
        
        $title = 'Data Pertumbuhan Lengkap';
        $body = "Guru {$guru->nama_lengkap} telah melengkapi semua data pertumbuhan siswa kelas {$kelas->nama_kelas} untuk bulan {$monthName} ({$totalStudents} siswa).";
        
        $actions = [
            [
                'name' => 'Lihat Data',
                'url' => "/admin/growth-records/manage/{$month}",
                'color' => 'primary'
            ]
        ];

        parent::__construct(
            $title, 
            $body, 
            'heroicon-o-chart-bar-square', 
            'success',
            $actions
        );
    }
    
    public function toArray($notifiable): array
    {
        $baseArray = parent::toArray($notifiable);
        
        // Add specific data for deduplication
        $baseArray['type'] = 'growth_record_completed';
        $baseArray['created_at'] = now();
        
        return $baseArray;
    }
}