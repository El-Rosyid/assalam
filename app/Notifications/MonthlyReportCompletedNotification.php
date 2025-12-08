<?php

namespace App\Notifications;

use App\Models\data_guru;
use App\Models\data_kelas;
use App\Models\monthly_reports;

class MonthlyReportCompletedNotification extends BaseAdminNotification
{
    public function __construct(monthly_reports $report, data_guru $guru, data_kelas $kelas)
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        $monthName = $months[$report->month] ?? 'Unknown';
        
        $title = 'Laporan Bulanan Selesai';
        $body = "Guru {$guru->nama_lengkap} telah menyelesaikan laporan bulanan {$monthName} {$report->year} untuk kelas {$kelas->nama_kelas}.";
        
        $actions = [
            [
                'name' => 'Lihat Laporan',
                'url' => "/admin/monthly-reports?tableFilters[month][value]={$report->month}&tableFilters[year][value]={$report->year}",
                'color' => 'primary'
            ]
        ];

        parent::__construct(
            $title, 
            $body, 
            'heroicon-o-document-text', 
            'info',
            $actions
        );
    }
    
    public function toArray($notifiable): array
    {
        $baseArray = parent::toArray($notifiable);
        
        // Add specific data for deduplication
        $baseArray['type'] = 'monthly_report_completed';
        $baseArray['created_at'] = now();
        
        return $baseArray;
    }
}