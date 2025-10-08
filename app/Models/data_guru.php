<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class data_guru extends Model
{
    use HasFactory;

    protected $table = 'data_guru';
    protected $guarded = []; // otomatis semua field boleh mass-assign

     protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'nip',
        'nuptk',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'alamat',
        'no_telp',
        'email',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship sebagai wali kelas
    public function kelasWali()
    {
        return $this->hasMany(data_kelas::class, 'walikelas_id');
    }
}
