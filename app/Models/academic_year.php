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
        'year',
        'semester',
        'pembagian_raport'
    ];

    protected $casts = [
        'pembagian_raport' => 'date'
    ];

    // Relationship ke kelas
    public function kelas()
    {
        return $this->hasMany(data_kelas::class, 'tahun_ajaran_id');
    }

    // Accessor untuk nama tahun ajaran
    public function getNamaTahunAjaranAttribute()
    {
        return $this->year . ' - ' . ucfirst($this->semester);
    }

    // Accessor untuk format tahun saja (alias untuk konsistensi)
    public function getTahunAttribute()
    {
        return $this->year;
    }
}
