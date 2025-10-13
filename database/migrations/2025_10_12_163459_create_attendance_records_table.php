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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('data_guru_id');
            $table->unsignedBigInteger('data_kelas_id');
            $table->unsignedBigInteger('data_siswa_id');
            $table->integer('alfa')->default(0);
            $table->integer('ijin')->default(0);
            $table->integer('sakit')->default(0);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('data_guru_id')->references('id')->on('data_guru')->onDelete('cascade');
            $table->foreign('data_kelas_id')->references('id')->on('data_kelas')->onDelete('cascade');
            $table->foreign('data_siswa_id')->references('id')->on('data_siswa')->onDelete('cascade');
            
            // Unique constraint - one record per student
            $table->unique('data_siswa_id', 'unique_student_attendance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
