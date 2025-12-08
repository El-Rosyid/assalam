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
        // Remove soft deletes from monthly_reports (transactional data)
        if (Schema::hasColumn('monthly_reports', 'deleted_at')) {
            Schema::table('monthly_reports', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
        
        // Remove soft deletes from attendance_records (transactional data)
        if (Schema::hasColumn('attendance_records', 'deleted_at')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->softDeletes();
        });
    }
};
