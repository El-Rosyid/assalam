<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class data_kelas extends Model
{
    use HasFactory;

    protected $table = 'data_kelas';

    protected $guarded = []; // Otomatis semua field boleh mass-assign
    
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

    // Disable timestamps if not using timestamps in migration
    public $timestamps = false;

    // Relationships
    public function walikelas()
    {
        return $this->belongsTo(data_guru::class, 'walikelas_id');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(academic_year::class, 'tahun_ajaran_id');
    }

    public function siswa()
    {
        return $this->hasMany(data_siswa::class, 'kelas', 'id');
    }

    public function getJumlahSiswaAttribute()
    {
        return $this->siswa()->count();
    }
}
