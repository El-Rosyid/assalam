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
        // Add soft deletes to critical tables
        Schema::table('data_siswa', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('data_guru', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('data_kelas', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('student_assessments', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('student_assessment_details', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('growth_records', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_siswa', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('data_guru', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('data_kelas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('student_assessments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('student_assessment_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('growth_records', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
