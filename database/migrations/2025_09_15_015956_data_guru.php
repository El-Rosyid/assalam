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
        Schema::create(
            'data_guru',
            function (Blueprint $table) {
                $table->id();
                $table->string('nama_lengkap');
                $table->integer('nip')->unique();
                $table->integer('nuptk')->unique();
                $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
                $table->string('tempat_lahir');
                $table->date('tanggal_lahir');
                $table->string('alamat');
                $table->string('no_telp')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
