<?php

namespace App\Filament\Widgets;

use App\Models\GrowthRecord;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class StudentGrowthChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Grafik Pertumbuhan';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 1;
    
    protected static ?string $maxHeight = '300px';
    
    public ?string $filter = 'height';
    
    protected function getData(): array
    {
        $user = Auth::user();
        $siswa = $user?->siswa;
        
        if (!$siswa) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }
        
        // Ambil data growth record siswa, diurutkan berdasarkan bulan
        // Note: growth_records tidak punya kolom 'year', hanya 'month'
        $records = GrowthRecord::where('siswa_nis', $siswa->nis)
            ->orderBy('month')
            ->orderBy('created_at')
            ->get();
        
        if ($records->isEmpty()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }
        
        $bulanIndonesia = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];
        
        $labels = [];
        $heightData = [];
        $weightData = [];
        
        foreach ($records as $record) {
            // Karena tidak ada year, kita tampilkan bulan saja atau tambahkan tahun dari created_at
            $yearFromRecord = $record->created_at ? $record->created_at->format('Y') : date('Y');
            $labels[] = $bulanIndonesia[$record->month] . ' ' . $yearFromRecord;
            $heightData[] = $record->tinggi_badan ?? null;
            $weightData[] = $record->berat_badan ?? null;
        }
        
        // Filter berdasarkan pilihan user
        if ($this->filter === 'height') {
            return [
                'datasets' => [
                    [
                        'label' => 'Tinggi Badan (cm)',
                        'data' => $heightData,
                        'borderColor' => 'rgb(59, 130, 246)', // blue-500
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'fill' => true,
                        'tension' => 0.4,
                    ],
                ],
                'labels' => $labels,
            ];
        } elseif ($this->filter === 'weight') {
            return [
                'datasets' => [
                    [
                        'label' => 'Berat Badan (kg)',
                        'data' => $weightData,
                        'borderColor' => 'rgb(16, 185, 129)', // green-500
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'fill' => true,
                        'tension' => 0.4,
                    ],
                ],
                'labels' => $labels,
            ];
        } else {
            // Both
            return [
                'datasets' => [
                    [
                        'label' => 'Tinggi Badan (cm)',
                        'data' => $heightData,
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'yAxisID' => 'y',
                        'tension' => 0.4,
                    ],
                    [
                        'label' => 'Berat Badan (kg)',
                        'data' => $weightData,
                        'borderColor' => 'rgb(16, 185, 129)',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'yAxisID' => 'y1',
                        'tension' => 0.4,
                    ],
                ],
                'labels' => $labels,
            ];
        }
    }
    
    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getFilters(): ?array
    {
        return [
            'height' => 'Tinggi Badan',
            'weight' => 'Berat Badan',
            'both' => 'Keduanya',
        ];
    }
    
    protected function getOptions(): array
    {
        $baseOptions = [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => false,
                    'title' => [
                        'display' => true,
                        'text' => $this->filter === 'height' ? 'Tinggi Badan (cm)' : ($this->filter === 'weight' ? 'Berat Badan (kg)' : 'Tinggi Badan (cm)'),
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
        
        // Jika 'both', tambahkan y-axis kedua
        if ($this->filter === 'both') {
            $baseOptions['scales']['y1'] = [
                'beginAtZero' => false,
                'position' => 'right',
                'title' => [
                    'display' => true,
                    'text' => 'Berat Badan (kg)',
                ],
                'grid' => [
                    'drawOnChartArea' => false,
                ],
            ];
        }
        
        return $baseOptions;
    }
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->siswa;
    }
}
