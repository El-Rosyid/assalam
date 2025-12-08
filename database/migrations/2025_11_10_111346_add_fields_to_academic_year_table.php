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
            $table->boolean('is_active')->default(false)->after('semester');
            $table->date('tanggal_penerimaan_raport')->nullable()->after('pembagian_raport');
            $table->timestamps(); // Menambahkan created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_year', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'tanggal_penerimaan_raport', 'created_at', 'updated_at']);
        });
    }
};
