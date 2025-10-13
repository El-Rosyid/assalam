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
        Schema::create('growth_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_siswa_id')->constrained('data_siswa')->onDelete('cascade');
            $table->foreignId('data_guru_id')->constrained('data_guru')->onDelete('cascade');
            $table->foreignId('data_kelas_id')->constrained('data_kelas')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_year')->onDelete('cascade');
            $table->date('measurement_date');
            $table->decimal('lingkar_kepala', 5, 2)->nullable()->comment('dalam cm');
            $table->decimal('lingkar_lengan', 5, 2)->nullable()->comment('dalam cm');
            $table->decimal('berat_badan', 5, 2)->nullable()->comment('dalam kg');
            $table->decimal('tinggi_badan', 5, 2)->nullable()->comment('dalam cm');
            $table->text('catatan')->nullable();
            $table->timestamps();
            
            // Unique constraint untuk mencegah duplicate per siswa per bulan
            $table->unique(['data_siswa_id', 'academic_year_id', 'measurement_date'], 'unique_student_month_measurement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('growth_records');
    }
};
