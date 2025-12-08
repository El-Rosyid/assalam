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
        // Add soft deletes to growth_records
        if (!Schema::hasColumn('growth_records', 'deleted_at')) {
            Schema::table('growth_records', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
                $table->index('deleted_at');
                
                // Foreign key for deleted_by
                $table->foreign('deleted_by')
                    ->references('user_id')
                    ->on('users')
                    ->onDelete('set null');
            });
        }
        
        // Add soft deletes to student_assessments
        if (!Schema::hasColumn('student_assessments', 'deleted_at')) {
            Schema::table('student_assessments', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
                $table->index('deleted_at');
                
                // Foreign key for deleted_by
                $table->foreign('deleted_by')
                    ->references('user_id')
                    ->on('users')
                    ->onDelete('set null');
            });
        }
        
        // Add soft deletes to student_assessment_details
        if (!Schema::hasColumn('student_assessment_details', 'deleted_at')) {
            Schema::table('student_assessment_details', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
                $table->index('deleted_at');
                
                // Foreign key for deleted_by
                $table->foreign('deleted_by')
                    ->references('user_id')
                    ->on('users')
                    ->onDelete('set null');
            });
        }
        
        // Add soft deletes to attendance_records
        if (!Schema::hasColumn('attendance_records', 'deleted_at')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
                $table->index('deleted_at');
                
                // Foreign key for deleted_by
                $table->foreign('deleted_by')
                    ->references('user_id')
                    ->on('users')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove soft deletes from all tables
        $tables = ['growth_records', 'student_assessments', 'student_assessment_details', 'attendance_records'];
        
        foreach ($tables as $tableName) {
            if (Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropForeign(["{$tableName}_deleted_by_foreign"]);
                    $table->dropColumn(['deleted_at', 'deleted_by']);
                });
            }
        }
    }
};
