<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert data_siswa to use NIS as natural primary key
     * Pure SQL approach for better control
     */
    public function up(): void
    {
        echo "🔧 Converting data_siswa to Natural Key (NIS)...\n\n";
        
        $this->validateData();
        $this->step1_DropForeignKeys();
        $this->step2_ConvertNisToInt();
        $this->step3_UpdateChildTables();
        $this->step4_ChangePrimaryKey();
        $this->step5_DropOldColumns();
        $this->step6_AddNewForeignKeys();
        $this->step7_AddConstraints();
        
        echo "\n✅ SUCCESS! data_siswa now uses NIS as natural primary key!\n";
    }
    
    protected function validateData(): void
    {
        echo "📊 Step 0: Validating data...\n";
        
        $siswaCount = DB::table('data_siswa')->count();
        echo "   - Found {$siswaCount} students\n";
        
        $duplicates = DB::select("SELECT nis, COUNT(*) as cnt FROM data_siswa GROUP BY nis HAVING cnt > 1");
        if (!empty($duplicates)) {
            throw new \Exception("❌ Duplicate NIS: " . json_encode($duplicates));
        }
        
        $nullCount = DB::table('data_siswa')->whereNull('nis')->count();
        if ($nullCount > 0) {
            throw new \Exception("❌ {$nullCount} students have NULL NIS!");
        }
        
        echo "   ✅ Validation passed\n\n";
    }
    
    protected function step1_DropForeignKeys(): void
    {
        echo "📝 Step 1: Dropping foreign keys...\n";
        
        $tables = ['student_assessments', 'growth_records', 'monthly_reports', 'monthly_report_broadcasts', 'attendance_records'];
        
        foreach ($tables as $table) {
            $fks = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND REFERENCED_TABLE_NAME = 'data_siswa'
            ", [$table]);
            
            foreach ($fks as $fk) {
                DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                echo "   ✅ {$table}.{$fk->CONSTRAINT_NAME}\n";
            }
        }
        echo "\n";
    }
    
    protected function step2_ConvertNisToInt(): void
    {
        echo "📝 Step 2: Converting NIS to INT...\n";
        
        DB::statement("ALTER TABLE data_siswa ADD COLUMN nis_int INT NULL AFTER nis");
        DB::statement("UPDATE data_siswa SET nis_int = CAST(nis AS UNSIGNED)");
        
        $converted = DB::table('data_siswa')->whereNotNull('nis_int')->count();
        $total = DB::table('data_siswa')->count();
        
        if ($converted !== $total) {
            throw new \Exception("❌ Conversion failed: {$converted}/{$total}");
        }
        
        echo "   ✅ Converted {$converted} values\n\n";
    }
    
    protected function step3_UpdateChildTables(): void
    {
        echo "📝 Step 3: Updating child tables...\n";
        
        $tables = [
            'student_assessments' => 'data_siswa_id',
            'growth_records' => 'data_siswa_id',
            'monthly_reports' => 'data_siswa_id',
            'monthly_report_broadcasts' => 'data_siswa_id',
            'attendance_records' => 'data_siswa_id',
        ];
        
        foreach ($tables as $table => $column) {
            DB::statement("ALTER TABLE {$table} ADD COLUMN siswa_nis INT NULL AFTER id");
            
            DB::statement("
                UPDATE {$table} t
                JOIN data_siswa s ON t.{$column} = s.id
                SET t.siswa_nis = s.nis_int
            ");
            
            $updated = DB::table($table)->whereNotNull('siswa_nis')->count();
            echo "   ✅ {$table}: {$updated} records\n";
        }
        echo "\n";
    }
    
    protected function step4_ChangePrimaryKey(): void
    {
        echo "📝 Step 4: Changing primary key...\n";
        
        // Drop unique index if exists
        try {
            DB::statement("ALTER TABLE data_siswa DROP INDEX data_siswa_nis_unique");
        } catch (\Exception $e) {
            // Index might not exist
        }
        
        DB::statement("ALTER TABLE data_siswa DROP COLUMN nis");
        DB::statement("ALTER TABLE data_siswa CHANGE nis_int nis INT NOT NULL");
        DB::statement("ALTER TABLE data_siswa MODIFY id BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE data_siswa DROP PRIMARY KEY");
        DB::statement("ALTER TABLE data_siswa ADD PRIMARY KEY (nis)");
        DB::statement("ALTER TABLE data_siswa DROP COLUMN id");
        
        echo "   ✅ nis is now PRIMARY KEY\n\n";
    }
    
    protected function step5_DropOldColumns(): void
    {
        echo "📝 Step 5: Cleaning up...\n";
        
        $tables = [
            'student_assessments',
            'growth_records',
            'monthly_reports',
            'monthly_report_broadcasts',
            'attendance_records',
        ];
        
        // Drop all unique constraints first
        foreach ($tables as $table) {
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_TYPE = 'UNIQUE'
            ", [$table]);
            
            foreach ($constraints as $c) {
                DB::statement("ALTER TABLE {$table} DROP INDEX {$c->CONSTRAINT_NAME}");
            }
        }
        
        // Drop old columns
        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} DROP COLUMN data_siswa_id");
            echo "   ✅ {$table}.data_siswa_id\n";
        }
        echo "\n";
    }
    
    protected function step6_AddNewForeignKeys(): void
    {
        echo "📝 Step 6: Adding foreign keys...\n";
        
        $tables = ['student_assessments', 'growth_records', 'monthly_reports', 'monthly_report_broadcasts', 'attendance_records'];
        
        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} MODIFY siswa_nis INT NOT NULL");
            
            DB::statement("
                ALTER TABLE {$table} 
                ADD CONSTRAINT {$table}_siswa_nis_foreign 
                FOREIGN KEY (siswa_nis) 
                REFERENCES data_siswa(nis) 
                ON UPDATE CASCADE 
                ON DELETE CASCADE
            ");
            echo "   ✅ {$table}.siswa_nis\n";
        }
        echo "\n";
    }
    
    protected function step7_AddConstraints(): void
    {
        echo "📝 Step 7: Adding constraints...\n";
        
        DB::statement("ALTER TABLE student_assessments ADD UNIQUE INDEX unique_student_semester (siswa_nis, semester)");
        echo "   ✅ student_assessments (siswa_nis, semester)\n";
        
        DB::statement("ALTER TABLE growth_records ADD UNIQUE INDEX unique_student_month (siswa_nis, month)");
        echo "   ✅ growth_records (siswa_nis, month)\n\n";
    }

    public function down(): void
    {
        echo "⚠️  Rollback not implemented - restore from backup!\n";
    }
};
