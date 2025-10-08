<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sekolah extends Model
{
    use HasFactory;
    protected $table = 'sekolah';
    protected $guarded = [];
    public $timestamps = false;

    protected $fillable = [
        'nama_sekolah',
        'npsn',
        'alamat',
        'kelurahan',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'kode_pos',
        'no_telp',
        'email',
        'website',
        'kepala_sekolah',
        'nip_kepala_sekolah'
    ];
}
