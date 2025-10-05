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
       Schema::create('data_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nama_lengkap');
            $table->string('nisn')->unique();
            $table->string('nis')->unique();
            $table->string('kelas');
            $table->string('diterima_kelas');
            $table->enum('agama', ['Islam', 'Kristen', 'Hindu', 'Buddha', 'Konghucu']);
            $table->string('asal_sekolah')->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->integer('anak_ke');
            $table->integer('jumlah_saudara');
            $table->date('tanggal_lahir');
            $table->date('tanggal_diterima');
            $table->string('alamat');
            $table->string('nama_ayah');
            $table->string('nama_ibu');
            $table->string('pekerjaan_ayah');
            $table->string('pekerjaan_ibu');
            $table->string('no_telp_ortu_wali', 15);
            $table->string('email_ortu_wali')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
