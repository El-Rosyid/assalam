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
        if (Schema::hasColumn('growth_records', 'catatan')) {
            Schema::table('growth_records', function (Blueprint $table) {
                $table->dropColumn('catatan');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('growth_records', 'catatan')) {
            Schema::table('growth_records', function (Blueprint $table) {
                $table->text('catatan')->nullable()->after('tinggi_badan');
            });
        }
    }
};
