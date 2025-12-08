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
        Schema::create('custom_broadcast_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_broadcast_id')->constrained('custom_broadcasts')->cascadeOnDelete();
            $table->string('siswa_nis', 20);
            $table->string('phone_number', 20)->comment('Nomor telepon tervalidasi (format 62xxx)');
            $table->text('message')->comment('Pesan final yang dikirim (dengan placeholder replaced)');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('response')->nullable()->comment('Response JSON dari Fonnte API');
            $table->text('error_message')->nullable()->comment('Error message jika gagal');
            $table->unsignedTinyInteger('retry_count')->default(0)->comment('Jumlah percobaan ulang');
            $table->timestamp('sent_at')->nullable()->comment('Waktu berhasil terkirim');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('siswa_nis')->references('nis')->on('data_siswa')->cascadeOnDelete();
            
            // Indexes
            $table->index('custom_broadcast_id');
            $table->index('siswa_nis');
            $table->index('status');
            $table->index(['custom_broadcast_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_broadcast_logs');
    }
};
