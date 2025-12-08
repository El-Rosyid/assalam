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
        Schema::table('academic_year', function (Blueprint $table) {
            // Drop the old unique constraint on 'year' column only
            $table->dropUnique('academic_year_year_unique');
            
            // Add new unique constraint on combination of year + semester
            $table->unique(['year', 'semester'], 'academic_year_year_semester_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_year', function (Blueprint $table) {
            // Drop the combined unique constraint
            $table->dropUnique('academic_year_year_semester_unique');
            
            // Restore the old unique constraint on 'year' only
            $table->unique('year', 'academic_year_year_unique');
        });
    }
};
