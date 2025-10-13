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
            // Add month column
            $table->tinyInteger('month')->after('data_kelas_id')->comment('Month 1-12');
            
            // Drop unnecessary columns
            $table->dropColumn(['measurement_date', 'catatan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('growth_records', function (Blueprint $table) {
            // Add back the dropped columns
            $table->date('measurement_date')->after('data_kelas_id');
            $table->text('catatan')->nullable()->after('tinggi_badan');
            
            // Drop month column
            $table->dropColumn('month');
        });
    }
};
