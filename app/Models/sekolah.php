<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sekolah extends Model
{
    use HasFactory;
    protected $table = 'sekolah';
    protected $primaryKey = 'sekolah_id'; // Specify the correct primary key
    protected $guarded = [];

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
        'kepala_sekolah_id',
        'nip_kepala_sekolah',
        'logo_sekolah'
    ];
    
    protected $casts = [
        'npsn' => 'string', // VARCHAR(10) identifier
        'nss' => 'string', // VARCHAR(10) identifier
        'nip_kepala_sekolah' => 'string', // VARCHAR(20) identifier
        'kode_pos' => 'string', // VARCHAR(10) identifier
    ];
    
    public function kepalaSekolah()
    {
        return $this->belongsTo(data_guru::class, 'kepala_sekolah_id');
    }
}
