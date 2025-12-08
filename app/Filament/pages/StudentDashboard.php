<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\StudentGrowthChartWidget;
use App\Filament\Widgets\StudentProfileWidget;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class StudentDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static string $view = 'filament.pages.student-dashboard';
    
    public function getHeading(): string
    {
        $nama = Auth::user()->siswa->nama_lengkap ?? 'Siswa';
        return "Selamat Datang, {$nama}!";
    }
    
    public function getSubheading(): ?string
    {
        return now()->isoFormat('dddd, D MMMM YYYY');
    }
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $slug = 'student-dashboard';
    
    public function getWidgets(): array
    {
        return [
            StudentProfileWidget::class,
            StudentGrowthChartWidget::class,
        ];
    }
    
    public function getColumns(): int | string | array
    {
        return 2; // 2 kolom untuk layout side-by-side
    }
    
    protected function getHeaderWidgets(): array
    {
        return $this->getWidgets();
    }
    
    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && $user->siswa;
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
