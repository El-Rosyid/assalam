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
        Schema::table('growth_records', function (Blueprint $table) {
            // Add unique constraint for student per month
            $table->unique(['data_siswa_id', 'month'], 'unique_student_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('growth_records', function (Blueprint $table) {
            $table->dropUnique('unique_student_month');
        });
    }
};
