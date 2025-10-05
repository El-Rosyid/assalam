<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class data_siswa extends Model
{
    use HasFactory;
    protected $table = 'data_siswa';
    protected $guarded =[];
    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'nisn',
        'nis',
        'kelas',
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
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
