<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class academic_year extends Model
{
    use HasFactory;
    protected $table = 'academic_year';
    protected $guarded = [];
    public $timestamps = false;

    protected $fillable = [
        'tahun_mulai',
        'tahun_selesai',
        'semester',
        'is_active'
    ];

    // Relationship ke kelas
    public function kelas()
    {
        return $this->hasMany(data_kelas::class, 'tahun_ajaran_id');
    }

    // Accessor untuk nama tahun ajaran
    public function getNamaTahunAjaranAttribute()
    {
        return $this->tahun_mulai . '/' . $this->tahun_selesai . ' - ' . ucfirst($this->semester);
    }
}
