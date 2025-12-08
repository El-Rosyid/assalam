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
        Schema::table('growth_records', function (Blueprint $table) {
            // Add missing columns that the model expects
            $table->unsignedBigInteger('data_guru_id')->nullable()->after('siswa_nis');
            $table->unsignedBigInteger('data_kelas_id')->nullable()->after('data_guru_id');
            $table->unsignedSmallInteger('year')->nullable()->after('month');
            
            // Add foreign key constraints - use semantic PK names
            $table->foreign('data_guru_id')->references('guru_id')->on('data_guru')->onDelete('set null');
            $table->foreign('data_kelas_id')->references('kelas_id')->on('data_kelas')->onDelete('set null');
        });
        
        // Populate data_guru_id and data_kelas_id from siswa's current class
        DB::statement("
            UPDATE growth_records gr
            INNER JOIN data_siswa ds ON gr.siswa_nis = ds.nis
            INNER JOIN data_kelas dk ON ds.kelas = dk.kelas_id
            SET 
                gr.data_guru_id = dk.walikelas_id,
                gr.data_kelas_id = dk.kelas_id,
                gr.year = YEAR(gr.created_at)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('growth_records', function (Blueprint $table) {
            $table->dropForeign(['data_guru_id']);
            $table->dropForeign(['data_kelas_id']);
            $table->dropColumn(['data_guru_id', 'data_kelas_id', 'year']);
        });
    }
};
