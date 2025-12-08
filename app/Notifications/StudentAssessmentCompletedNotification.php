<?php

namespace App\Notifications;

use App\Models\data_guru;
use App\Models\data_kelas;
use App\Models\data_siswa;
use App\Models\academic_year;

class StudentAssessmentCompletedNotification extends BaseAdminNotification
{
    public function __construct(data_siswa $siswa, data_kelas $kelas, academic_year $tahunAjaran, data_guru $guru)
    {
        $title = 'Penilaian Siswa Selesai';
        $body = "Guru {$guru->nama_lengkap} telah menyelesaikan penilaian untuk siswa {$siswa->nama_lengkap} dari kelas {$kelas->nama_kelas} tahun ajaran {$tahunAjaran->nama_tahun}.";
        
        $actions = [
            [
                'name' => 'Lihat Penilaian',
                'url' => "/admin/student-assessments?tableFilters[siswa_nis][value]={$siswa->nis}",
                'color' => 'primary'
            ]
        ];

        parent::__construct(
            $title, 
            $body, 
            'heroicon-o-academic-cap', 
            'success',
            $actions
        );
    }
    
    public function toArray($notifiable): array
    {
        $baseArray = parent::toArray($notifiable);
        
        // Add specific data for deduplication
        $baseArray['type'] = 'student_assessment_completed';
        $baseArray['created_at'] = now();
        
        return $baseArray;
    }
}