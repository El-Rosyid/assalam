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
        Schema::create('student_assessment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_assessment_id')->constrained('student_assessments')->onDelete('cascade');
            $table->foreignId('assessment_variable_id')->constrained('assessment_variable')->onDelete('cascade');
            $table->enum('rating', [
                'Berkembang Sesuai Harapan',
                'Belum Berkembang', 
                'Mulai Berkembang',
                'Sudah Berkembang'
            ])->nullable();
            $table->text('description')->nullable(); // tanggapan guru
            $table->json('images')->nullable(); // array file paths untuk gambar
            $table->timestamps();
            
            // Unique constraint: satu assessment variable hanya bisa dinilai sekali per student assessment
            $table->unique(['student_assessment_id', 'assessment_variable_id'], 'unique_student_assessment_variable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_assessment_details');
    }
};
