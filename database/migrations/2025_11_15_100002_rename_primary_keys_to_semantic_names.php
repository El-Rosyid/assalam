<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * PHASE 2: Rename all primary keys to semantic names
     * - users.id → users.user_id
     * - data_guru.id → data_guru.guru_id
     * - data_kelas.id → data_kelas.kelas_id
     * - academic_year.id → academic_year.tahun_ajaran_id
     * - sekolah.id → sekolah.sekolah_id
     */
    public function up(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // 1. USERS TABLE: id → user_id
        if (Schema::hasColumn('users', 'id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('id', 'user_id');
            });
        }
        
        // 2. DATA_GURU TABLE: id → guru_id
        if (Schema::hasColumn('data_guru', 'id')) {
            Schema::table('data_guru', function (Blueprint $table) {
                $table->renameColumn('id', 'guru_id');
            });
        }
        
        // 3. DATA_KELAS TABLE: id → kelas_id
        if (Schema::hasColumn('data_kelas', 'id')) {
            Schema::table('data_kelas', function (Blueprint $table) {
                $table->renameColumn('id', 'kelas_id');
            });
        }
        
        // 4. ACADEMIC_YEAR TABLE: id → tahun_ajaran_id
        if (Schema::hasColumn('academic_year', 'id')) {
            Schema::table('academic_year', function (Blueprint $table) {
                $table->renameColumn('id', 'tahun_ajaran_id');
            });
        }
        
        // 5. SEKOLAH TABLE: id → sekolah_id
        if (Schema::hasColumn('sekolah', 'id')) {
            Schema::table('sekolah', function (Blueprint $table) {
                $table->renameColumn('id', 'sekolah_id');
            });
        }
        
        // 6. ASSESSMENT_VARIABLE TABLE: id → variabel_id
        if (Schema::hasTable('assessment_variable') && Schema::hasColumn('assessment_variable', 'id')) {
            Schema::table('assessment_variable', function (Blueprint $table) {
                $table->renameColumn('id', 'variabel_id');
            });
        }
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Reverse all renames
        if (Schema::hasColumn('users', 'user_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('user_id', 'id');
            });
        }
        
        if (Schema::hasColumn('data_guru', 'guru_id')) {
            Schema::table('data_guru', function (Blueprint $table) {
                $table->renameColumn('guru_id', 'id');
            });
        }
        
        if (Schema::hasColumn('data_kelas', 'kelas_id')) {
            Schema::table('data_kelas', function (Blueprint $table) {
                $table->renameColumn('kelas_id', 'id');
            });
        }
        
        if (Schema::hasColumn('academic_year', 'tahun_ajaran_id')) {
            Schema::table('academic_year', function (Blueprint $table) {
                $table->renameColumn('tahun_ajaran_id', 'id');
            });
        }
        
        if (Schema::hasColumn('sekolah', 'sekolah_id')) {
            Schema::table('sekolah', function (Blueprint $table) {
                $table->renameColumn('sekolah_id', 'id');
            });
        }
        
        if (Schema::hasColumn('assessment_variable', 'variabel_id')) {
            Schema::table('assessment_variable', function (Blueprint $table) {
                $table->renameColumn('variabel_id', 'id');
            });
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
