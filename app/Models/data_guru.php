<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class data_guru extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'data_guru';
    protected $guarded = []; // otomatis semua field boleh mass-assign

     protected $primaryKey = 'guru_id';

    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'nik',          // NIK (nullable for flexibility)
        'nip',          // NIP (nullable for non-PNS)
        'nuptk',
        'passport',     // For foreign teachers
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'alamat',
        'no_telp',
        'email',
        'status',       // PNS, Swasta, Honorer, Kontrak, Volunteer
        'data_lengkap', // Track data completion
    ];

    protected $casts = [
        'nip' => 'string', // VARCHAR(20) identifier
        'nuptk' => 'string', // VARCHAR(16) identifier
        'data_lengkap' => 'boolean',
        'tanggal_lahir' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id'); // Pastikan menggunakan user_id
    }

    // Relationship sebagai wali kelas
    public function kelasWali()
    {
        return $this->hasMany(data_kelas::class, 'walikelas_id', 'guru_id');
    }
}
