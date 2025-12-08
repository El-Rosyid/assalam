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
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('data_guru_id');
            $table->unsignedBigInteger('data_kelas_id');
            $table->unsignedBigInteger('data_siswa_id');
            $table->tinyInteger('month')->comment('Month 1-12');
            $table->integer('year')->default(date('Y'));
            $table->text('catatan')->nullable();
            $table->enum('status', ['draft', 'final'])->default('draft');
            $table->timestamps();

            // foreign key
            $table->foreign('data_guru_id')->references('id')->on('data_guru')->onDelete('cascade');
            $table->foreign('data_kelas_id')->references('id')->on('data_kelas')->onDelete('cascade');
            $table->foreign('data_siswa_id')->references('id')->on('data_siswa')->onDelete('cascade');
            
            // Ensure one report per student per month per year
            $table->unique(['data_siswa_id', 'month', 'year'], 'unique_student_month_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
