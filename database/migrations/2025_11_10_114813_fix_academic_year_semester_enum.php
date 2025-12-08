<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change enum to string with uppercase
        DB::statement("ALTER TABLE academic_year MODIFY COLUMN semester VARCHAR(10)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to enum
        DB::statement("ALTER TABLE academic_year MODIFY COLUMN semester ENUM('genap','ganjil')");
    }
};
