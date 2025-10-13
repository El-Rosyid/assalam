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
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('growth_records', function (Blueprint $table) {
            $table->unsignedBigInteger('academic_year_id')->after('data_kelas_id');
            $table->foreign('academic_year_id')->references('id')->on('academic_year')->onDelete('cascade');
        });
    }
};
