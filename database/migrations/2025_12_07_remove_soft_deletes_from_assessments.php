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
        Schema::table('student_assessments', function (Blueprint $table) {
            if (Schema::hasColumn('student_assessments', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        
        Schema::table('student_assessment_details', function (Blueprint $table) {
            if (Schema::hasColumn('student_assessment_details', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_assessments', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('student_assessment_details', function (Blueprint $table) {
            $table->softDeletes();
        });
    }
};
