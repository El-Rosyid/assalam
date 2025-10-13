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
        Schema::create('student_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_siswa_id')->constrained('data_siswa')->onDelete('cascade');
            $table->foreignId('data_guru_id')->constrained('data_guru')->onDelete('cascade'); // wali kelas yang menilai
            $table->foreignId('data_kelas_id')->constrained('data_kelas')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_year')->onDelete('cascade');
            $table->string('semester', 10); // 'Ganjil' atau 'Genap'
            $table->enum('status', ['belum_dinilai', 'sebagian', 'selesai'])->default('belum_dinilai');
            $table->timestamp('completed_at')->nullable(); // kapan selesai dinilai
            $table->timestamps();
            
            // Unique constraint: satu siswa hanya bisa dinilai sekali per semester per tahun ajaran
            $table->unique(['data_siswa_id', 'academic_year_id', 'semester'], 'unique_student_semester_assessment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_assessments');
    }
};
