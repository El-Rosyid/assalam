<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('data_kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kelas');
            $table->foreignId('walikelas_id')
                ->nullable()
                ->constrained('data_guru')
                ->onDelete('set null');
            $table->foreignId('tahun_ajaran_id')
                ->nullable()
                ->constrained('academic_year')
                ->onDelete('set null');
            $table->integer('tingkat');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_kelas');
    }
};
