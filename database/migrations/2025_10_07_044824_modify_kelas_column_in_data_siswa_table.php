<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Backup data kelas lama (A, B) ke kolom temporary
        Schema::table('data_siswa', function (Blueprint $table) {
            $table->string('kelas_old')->nullable()->after('kelas');
        });

        // Copy data ke kelas_old
        DB::table('data_siswa')->update([
            'kelas_old' => DB::raw('kelas')
        ]);

        // Step 2: Drop kolom kelas lama
        Schema::table('data_siswa', function (Blueprint $table) {
            $table->dropColumn('kelas');
        });

        // Step 3: Buat kolom kelas baru sebagai foreign key
        Schema::table('data_siswa', function (Blueprint $table) {
            $table->foreignId('kelas')
                ->nullable()
                ->after('nis')
                ->constrained('data_kelas', 'id')
                ->onDelete('set null');
        });

        // Step 4: Migrasi data lama (A, B) ke ID kelas baru
        // Ambil ID kelas berdasarkan nama
        $kelasA = DB::table('data_kelas')->where('nama_kelas', 'like', '%A%')->first();
        $kelasB = DB::table('data_kelas')->where('nama_kelas', 'like', '%B%')->first();

        if ($kelasA) {
            DB::table('data_siswa')
                ->where('kelas_old', 'A')
                ->update(['kelas' => $kelasA->id]);
        }

        if ($kelasB) {
            DB::table('data_siswa')
                ->where('kelas_old', 'B')
                ->update(['kelas' => $kelasB->id]);
        }

        // Step 5: Hapus kolom temporary
        Schema::table('data_siswa', function (Blueprint $table) {
            $table->dropColumn('kelas_old');
        });
    }

    public function down(): void
    {
        // Kembalikan ke struktur lama
        Schema::table('data_siswa', function (Blueprint $table) {
            $table->string('kelas_old')->nullable()->after('kelas');
        });

        // Backup ID kelas ke nama kelas
        DB::statement("
            UPDATE data_siswa 
            SET kelas_old = (
                SELECT nama_kelas 
                FROM data_kelas 
                WHERE data_kelas.id = data_siswa.kelas
            )
        ");

        // Drop foreign key
        Schema::table('data_siswa', function (Blueprint $table) {
            $table->dropForeign(['kelas']);
            $table->dropColumn('kelas');
        });

        // Buat kembali kolom kelas varchar
        Schema::table('data_siswa', function (Blueprint $table) {
            $table->string('kelas')->nullable()->after('nis');
        });

        // Copy data kembali
        DB::table('data_siswa')->update([
            'kelas' => DB::raw('kelas_old')
        ]);

        // Drop temporary
        Schema::table('data_siswa', function (Blueprint $table) {
            $table->dropColumn('kelas_old');
        });
    }
};