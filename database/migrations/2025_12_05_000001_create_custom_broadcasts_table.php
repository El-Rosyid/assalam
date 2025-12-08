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
        Schema::create('custom_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Admin yang membuat broadcast');
            $table->string('title')->comment('Judul broadcast untuk referensi internal');
            $table->text('message')->comment('Isi pesan yang akan dikirim');
            $table->string('image_path')->nullable()->comment('Path gambar attachment (opsional)');
            $table->enum('target_type', ['all', 'class', 'individual'])
                ->comment('Tipe target: all=semua siswa, class=per kelas, individual=per siswa');
            $table->json('target_ids')->nullable()
                ->comment('Array ID kelas atau siswa tergantung target_type');
            $table->enum('status', ['draft', 'sending', 'completed', 'failed'])
                ->default('draft')
                ->comment('Status broadcast: draft, sending, completed, failed');
            $table->unsignedInteger('total_recipients')->default(0)
                ->comment('Total jumlah penerima');
            $table->unsignedInteger('sent_count')->default(0)
                ->comment('Jumlah yang berhasil terkirim');
            $table->unsignedInteger('failed_count')->default(0)
                ->comment('Jumlah yang gagal terkirim');
            $table->timestamp('sent_at')->nullable()
                ->comment('Waktu mulai pengiriman');
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('target_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_broadcasts');
    }
};
