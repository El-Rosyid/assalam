<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monthly_reports', function (Blueprint $table) {
            // Add tahun_ajaran_id column after data_kelas_id
            $table->bigInteger('tahun_ajaran_id')->unsigned()->nullable()->after('data_kelas_id');
            
            // Add foreign key constraint with ON DELETE SET NULL
            $table->foreign('tahun_ajaran_id')
                  ->references('tahun_ajaran_id')
                  ->on('academic_year')
                  ->onDelete('set null');
        });
        
        // Populate tahun_ajaran_id from siswa->kelas->tahun_ajaran_id
        DB::statement("
            UPDATE monthly_reports mr
            INNER JOIN data_siswa ds ON mr.siswa_nis = ds.nis
            INNER JOIN data_kelas dk ON ds.kelas = dk.kelas_id
            SET mr.tahun_ajaran_id = dk.tahun_ajaran_id
            WHERE dk.tahun_ajaran_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_reports', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['tahun_ajaran_id']);
            // Then drop column
            $table->dropColumn('tahun_ajaran_id');
        });
    }
};
