<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class data_siswa extends Model
{
    use HasFactory;
    protected $table = 'data_siswa';

     protected $primaryKey = 'id';

    protected $guarded =[];
    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'nisn',
        'nis',
        'kelas', // Foreign key ke data_kelas
        'diterima_kelas',
        'agama',
        'asal_sekolah',
        'jenis_kelamin',
        'anak_ke',
        'jumlah_saudara',
        'tanggal_lahir',
        'tanggal_diterima',
        'tempat_lahir',
        'alamat',
        'nama_ayah',
        'nama_ibu',
        'pekerjaan_ayah',
        'pekerjaan_ibu',
        'no_telp_ortu_wali',
        'email_ortu_wali',
        'is_active',
    ];

    protected $casts = [
        'kelas' => 'integer',
        'is_active' => 'boolean',
        'tanggal_lahir' => 'date',
        'tanggal_diterima' => 'date',
    ];

    // Define attributes to prevent undefined property access
    protected $attributes = [
        'kelas' => null,
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kelas()
    {
        return $this->belongsTo(data_kelas::class, 'kelas', 'id');
    }

    // Accessor untuk nama kelas dengan fallback
    public function getNamaKelasAttribute()
    {
        try {
            return $this->kelas?->nama_kelas ?? 'Belum ada kelas';
        } catch (\Exception $e) {
            return 'Error loading kelas';
        }
    }

    // Helper untuk mendapatkan kelas dengan aman
    public function getKelasInfo()
    {
        try {
            return $this->kelas;
        } catch (\Exception $e) {
            return null;
        }
    }

    // Helper untuk mendapatkan wali kelas dengan aman
    public function getWaliKelasInfo()
    {
        try {
            $kelas = $this->kelas;
            return $kelas ? $kelas->walikelas : null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
}
