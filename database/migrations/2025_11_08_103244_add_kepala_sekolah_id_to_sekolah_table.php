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
        Schema::table('sekolah', function (Blueprint $table) {
            $table->unsignedBigInteger('kepala_sekolah_id')->nullable()->after('kepala_sekolah');
            $table->foreign('kepala_sekolah_id')->references('id')->on('data_guru')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            $table->dropForeign(['kepala_sekolah_id']);
            $table->dropColumn('kepala_sekolah_id');
        });
    }
};
