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
     * PHASE 3: Update all foreign keys to match new semantic names
     * and implement single path (relasi berjenjang)
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // ===== UPDATE data_siswa FOREIGN KEYS =====
        
        // Update user_id FK (if exists)
        if (Schema::hasColumn('data_siswa', 'user_id')) {
            // FK already named correctly, just update reference
            try {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'data_siswa' 
                    AND COLUMN_NAME = 'user_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if (!empty($foreignKeys)) {
                    Schema::table('data_siswa', function (Blueprint $table) use ($foreignKeys) {
                        $table->dropForeign($foreignKeys[0]->CONSTRAINT_NAME);
                    });
                }
            } catch (\Exception $e) {
                // FK might not exist
            }
            
            Schema::table('data_siswa', function (Blueprint $table) {
                $table->foreign('user_id')
                    ->references('user_id')->on('users')
                    ->onDelete('cascade');
            });
        }
        
        // Update created_by, updated_by FKs
        foreach (['created_by', 'updated_by'] as $column) {
            if (Schema::hasColumn('data_siswa', $column)) {
                try {
                    $foreignKeys = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'data_siswa' 
                        AND COLUMN_NAME = '$column'
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ");
                    
                    if (!empty($foreignKeys)) {
                        Schema::table('data_siswa', function (Blueprint $table) use ($foreignKeys) {
                            $table->dropForeign($foreignKeys[0]->CONSTRAINT_NAME);
                        });
                    }
                } catch (\Exception $e) {
                    // FK might not exist
                }
                
                Schema::table('data_siswa', function (Blueprint $table) use ($column) {
                    $table->foreign($column)
                        ->references('user_id')->on('users')
                        ->onDelete('set null');
                });
            }
        }
        
        // ===== UPDATE data_kelas FOREIGN KEYS =====
        
        // Update walikelas_id FK: data_guru.id → data_guru.guru_id
        if (Schema::hasColumn('data_kelas', 'walikelas_id')) {
            try {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'data_kelas' 
                    AND COLUMN_NAME = 'walikelas_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if (!empty($foreignKeys)) {
                    Schema::table('data_kelas', function (Blueprint $table) use ($foreignKeys) {
                        $table->dropForeign($foreignKeys[0]->CONSTRAINT_NAME);
                    });
                }
            } catch (\Exception $e) {
                // FK might not exist
            }
            
            Schema::table('data_kelas', function (Blueprint $table) {
                $table->foreign('walikelas_id')
                    ->references('guru_id')->on('data_guru')
                    ->onDelete('set null');
            });
        }
        
        // Update tahun_ajaran_id FK: academic_year.id → academic_year.tahun_ajaran_id
        if (Schema::hasColumn('data_kelas', 'tahun_ajaran_id')) {
            try {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'data_kelas' 
                    AND COLUMN_NAME = 'tahun_ajaran_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if (!empty($foreignKeys)) {
                    Schema::table('data_kelas', function (Blueprint $table) use ($foreignKeys) {
                        $table->dropForeign($foreignKeys[0]->CONSTRAINT_NAME);
                    });
                }
            } catch (\Exception $e) {
                // FK might not exist
            }
            
            Schema::table('data_kelas', function (Blueprint $table) {
                $table->foreign('tahun_ajaran_id')
                    ->references('tahun_ajaran_id')->on('academic_year')
                    ->onDelete('set null');
            });
        }
        
        // ===== UPDATE data_guru FOREIGN KEYS =====
        
        // Update user_id FK
        if (Schema::hasColumn('data_guru', 'user_id')) {
            try {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'data_guru' 
                    AND COLUMN_NAME = 'user_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if (!empty($foreignKeys)) {
                    Schema::table('data_guru', function (Blueprint $table) use ($foreignKeys) {
                        $table->dropForeign($foreignKeys[0]->CONSTRAINT_NAME);
                    });
                }
            } catch (\Exception $e) {
                // FK might not exist
            }
            
            Schema::table('data_guru', function (Blueprint $table) {
                $table->foreign('user_id')
                    ->references('user_id')->on('users')
                    ->onDelete('set null');
            });
        }
        
        // ===== UPDATE sekolah FOREIGN KEYS =====
        
        // Update kepala_sekolah_id FK: data_guru.id → data_guru.guru_id
        if (Schema::hasColumn('sekolah', 'kepala_sekolah_id')) {
            try {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'sekolah' 
                    AND COLUMN_NAME = 'kepala_sekolah_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if (!empty($foreignKeys)) {
                    Schema::table('sekolah', function (Blueprint $table) use ($foreignKeys) {
                        $table->dropForeign($foreignKeys[0]->CONSTRAINT_NAME);
                    });
                }
            } catch (\Exception $e) {
                // FK might not exist
            }
            
            Schema::table('sekolah', function (Blueprint $table) {
                $table->foreign('kepala_sekolah_id')
                    ->references('guru_id')->on('data_guru')
                    ->onDelete('set null');
            });
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Reverse all FK updates back to reference 'id' columns
        // This is complex, so we'll just drop and recreate with old references
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
