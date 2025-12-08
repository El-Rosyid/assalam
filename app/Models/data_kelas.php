<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class data_kelas extends Model
{
    use HasFactory;

    protected $table = 'data_kelas';
    protected $primaryKey = 'kelas_id';
    
    // Disable timestamps if not using timestamps in migration
    public $timestamps = false;
    
    protected $fillable = [
        'nama_kelas',
        'walikelas_id',
        'tahun_ajaran_id',
        'tingkat'
    ];

    protected $casts = [
        'tingkat' => 'integer',
        'walikelas_id' => 'integer',
        'tahun_ajaran_id' => 'integer'
    ];

    // Relationships
    public function walikelas()
    {
        return $this->belongsTo(data_guru::class, 'walikelas_id', 'guru_id'); // Pastikan menggunakan guru_id
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(academic_year::class, 'tahun_ajaran_id');
    }

    public function siswa()
    {
        return $this->hasMany(data_siswa::class, 'kelas', 'kelas_id');
    }

    public function getJumlahSiswaAttribute()
    {
        return $this->siswa()->count();
    }

    /**
     * Accessor untuk nama_kelas - otomatis berdasarkan tingkat
     */
    public function getNamaKelasAttribute($value)
    {
        // Jika nama_kelas kosong atau tidak sesuai, generate dari tingkat
        if (empty($value) || !in_array($value, ['Kelas A', 'Kelas B'])) {
            return $this->tingkat == 1 ? 'Kelas A' : ($this->tingkat == 2 ? 'Kelas B' : $value);
        }
        return $value;
    }
}
