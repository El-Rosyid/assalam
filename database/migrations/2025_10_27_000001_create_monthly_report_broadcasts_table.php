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
        Schema::create('monthly_report_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('monthly_report_id');
            $table->unsignedBigInteger('data_siswa_id');
            $table->string('phone_number');
            $table->text('message');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('response')->nullable(); // Response dari Fonnte API
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('monthly_report_id')
                  ->references('id')
                  ->on('monthly_reports')
                  ->onDelete('cascade');
                  
            $table->foreign('data_siswa_id')
                  ->references('id')
                  ->on('data_siswa')
                  ->onDelete('cascade');
                  
            // Index for faster queries
            $table->index(['monthly_report_id', 'status']);
            $table->index('data_siswa_id');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_report_broadcasts');
    }
};
