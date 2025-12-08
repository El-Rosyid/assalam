<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\academic_year;
use Carbon\Carbon;

class BackupAcademicYearData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:academic-year 
                            {year_id? : ID Tahun Ajaran yang akan di-backup}
                            {--all : Backup semua tahun ajaran}';

    /**
     * The console command description.
     */
    protected $description = 'Backup data berdasarkan tahun ajaran ke file SQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $yearId = $this->argument('year_id');
        $backupAll = $this->option('all');
        
        if ($backupAll) {
            $this->backupAllYears();
        } elseif ($yearId) {
            $this->backupYear($yearId);
        } else {
            $this->showInteractiveMenu();
        }
        
        return Command::SUCCESS;
    }
    
    protected function showInteractiveMenu()
    {
        $years = academic_year::orderBy('nama_tahun_ajaran', 'desc')->get();
        
        if ($years->isEmpty()) {
            $this->error('❌ Tidak ada tahun ajaran di database');
            return;
        }
        
        $this->info('📚 PILIH TAHUN AJARAN UNTUK BACKUP:');
        $this->newLine();
        
        $choices = [];
        foreach ($years as $year) {
            $choices[$year->id] = $year->nama_tahun_ajaran . ' (ID: ' . $year->id . ')';
        }
        $choices['all'] = '🔄 Backup Semua Tahun Ajaran';
        
        $selected = $this->choice('Pilih tahun ajaran', $choices);
        
        if ($selected === '🔄 Backup Semua Tahun Ajaran') {
            $this->backupAllYears();
        } else {
            $yearId = array_search($selected, $choices);
            $this->backupYear($yearId);
        }
    }
    
    protected function backupAllYears()
    {
        $years = academic_year::all();
        
        $this->info('🔄 Memulai backup untuk ' . $years->count() . ' tahun ajaran...');
        $this->newLine();
        
        $bar = $this->output->createProgressBar($years->count());
        $bar->start();
        
        foreach ($years as $year) {
            $this->backupYear($year->id, false);
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('✅ Backup semua tahun ajaran selesai!');
    }
    
    protected function backupYear($yearId, $showProgress = true)
    {
        $year = academic_year::find($yearId);
        
        if (!$year) {
            $this->error("❌ Tahun ajaran dengan ID {$yearId} tidak ditemukan");
            return;
        }
        
        if ($showProgress) {
            $this->info("📦 Membackup data tahun ajaran: {$year->nama_tahun_ajaran}");
        }
        
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $yearName = str_replace(['/', ' '], '_', $year->nama_tahun_ajaran);
        $fileName = "backup_{$yearName}_{$timestamp}.sql";
        
        // Create backup directory if not exists
        $backupPath = storage_path('app/backups/academic-years');
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        $fullPath = $backupPath . '/' . $fileName;
        
        // Generate SQL backup
        $sql = $this->generateBackupSQL($year);
        
        // Save to file
        file_put_contents($fullPath, $sql);
        
        // Get file size
        $fileSize = $this->formatBytes(filesize($fullPath));
        
        if ($showProgress) {
            $this->newLine();
            $this->info("✅ Backup berhasil dibuat:");
            $this->line("   📁 File: {$fileName}");
            $this->line("   📊 Ukuran: {$fileSize}");
            $this->line("   📂 Lokasi: {$fullPath}");
        }
    }
    
    protected function generateBackupSQL($year)
    {
        $sql = "-- =====================================================\n";
        $sql .= "-- BACKUP DATA TAHUN AJARAN: {$year->nama_tahun_ajaran}\n";
        $sql .= "-- Tanggal Backup: " . Carbon::now()->format('Y-m-d H:i:s') . "\n";
        $sql .= "-- =====================================================\n\n";
        
        // Backup Academic Year
        $sql .= "-- TAHUN AJARAN\n";
        $sql .= $this->dumpTable('academic_year', ['id' => $year->id]);
        
        // Backup Student Assessments
        $sql .= "\n-- PENILAIAN SISWA\n";
        $assessments = DB::table('student_assessments')
            ->where('academic_year_id', $year->id)
            ->get();
        
        if ($assessments->count() > 0) {
            $assessmentIds = $assessments->pluck('id')->toArray();
            $sql .= $this->dumpTable('student_assessments', ['academic_year_id' => $year->id]);
            
            // Backup Assessment Details
            $sql .= "\n-- DETAIL PENILAIAN\n";
            $sql .= $this->dumpTableIn('student_assessment_details', 'student_assessment_id', $assessmentIds);
        }
        
        // Backup Growth Records
        $sql .= "\n-- CATATAN PERTUMBUHAN\n";
        $sql .= $this->dumpTable('growth_records', ['academic_year_id' => $year->id]);
        
        // Backup Attendance Records (if has year FK)
        $sql .= "\n-- CATATAN KEHADIRAN\n";
        $attendances = DB::table('attendance_records')
            ->whereIn('data_siswa_id', function($q) use ($year) {
                $q->select('data_siswa_id')
                  ->from('student_assessments')
                  ->where('academic_year_id', $year->id);
            })
            ->get();
        
        if ($attendances->count() > 0) {
            $attendanceIds = $attendances->pluck('id')->toArray();
            $sql .= $this->dumpTableIn('attendance_records', 'id', $attendanceIds);
        }
        
        // Backup Monthly Reports (approximate by year)
        $sql .= "\n-- CATATAN BULANAN\n";
        $sql .= "-- Monthly reports untuk tahun {$year->nama_tahun_ajaran}\n";
        $yearParts = explode('/', $year->nama_tahun_ajaran);
        if (count($yearParts) == 2) {
            $startYear = $yearParts[0];
            $sql .= $this->dumpTableYear('monthly_reports', $startYear);
        }
        
        $sql .= "\n-- =====================================================\n";
        $sql .= "-- END OF BACKUP\n";
        $sql .= "-- Total Records: " . $this->countRecords($year) . "\n";
        $sql .= "-- =====================================================\n";
        
        return $sql;
    }
    
    protected function dumpTable($table, $where = [])
    {
        $query = DB::table($table);
        
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }
        
        $records = $query->get();
        
        if ($records->isEmpty()) {
            return "-- Tidak ada data di tabel {$table}\n";
        }
        
        $sql = "-- Tabel: {$table} ({$records->count()} records)\n";
        
        foreach ($records as $record) {
            $columns = implode(', ', array_keys((array)$record));
            $values = implode(', ', array_map(function($value) {
                return is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
            }, array_values((array)$record)));
            
            $sql .= "INSERT INTO {$table} ({$columns}) VALUES ({$values});\n";
        }
        
        return $sql;
    }
    
    protected function dumpTableIn($table, $column, $ids)
    {
        if (empty($ids)) {
            return "-- Tidak ada data di tabel {$table}\n";
        }
        
        $records = DB::table($table)->whereIn($column, $ids)->get();
        
        if ($records->isEmpty()) {
            return "-- Tidak ada data di tabel {$table}\n";
        }
        
        $sql = "-- Tabel: {$table} ({$records->count()} records)\n";
        
        foreach ($records as $record) {
            $columns = implode(', ', array_keys((array)$record));
            $values = implode(', ', array_map(function($value) {
                return is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
            }, array_values((array)$record)));
            
            $sql .= "INSERT INTO {$table} ({$columns}) VALUES ({$values});\n";
        }
        
        return $sql;
    }
    
    protected function dumpTableYear($table, $year)
    {
        $records = DB::table($table)
            ->whereYear('created_at', $year)
            ->get();
        
        if ($records->isEmpty()) {
            return "-- Tidak ada data di tabel {$table} untuk tahun {$year}\n";
        }
        
        $sql = "-- Tabel: {$table} ({$records->count()} records)\n";
        
        foreach ($records as $record) {
            $columns = implode(', ', array_keys((array)$record));
            $values = implode(', ', array_map(function($value) {
                return is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
            }, array_values((array)$record)));
            
            $sql .= "INSERT INTO {$table} ({$columns}) VALUES ({$values});\n";
        }
        
        return $sql;
    }
    
    protected function countRecords($year)
    {
        $total = 0;
        
        $total += DB::table('student_assessments')->where('academic_year_id', $year->id)->count();
        
        $assessmentIds = DB::table('student_assessments')
            ->where('academic_year_id', $year->id)
            ->pluck('id');
        
        $total += DB::table('student_assessment_details')->whereIn('student_assessment_id', $assessmentIds)->count();
        $total += DB::table('growth_records')->where('academic_year_id', $year->id)->count();
        
        return $total;
    }
    
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
