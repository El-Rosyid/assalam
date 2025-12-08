<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class academic_year extends Model
{
    use HasFactory;
    protected $table = 'academic_year';
    protected $primaryKey = 'tahun_ajaran_id';
    protected $guarded = [];
    public $timestamps = true;

    protected $fillable = [
        'year',
        'semester',
        'is_active',
        'tanggal_penerimaan_raport',
        'pembagian_raport'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tanggal_penerimaan_raport' => 'date',
        'pembagian_raport' => 'date'
    ];

    // Relationship ke kelas
    public function kelas()
    {
        return $this->hasMany(data_kelas::class, 'tahun_ajaran_id');
    }
    
    // Relationship ke student assessments
    public function assessments()
    {
        return $this->hasMany(student_assessment::class, 'tahun_ajaran_id', 'tahun_ajaran_id');
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
    
    // Static method untuk get active academic year
    public static function getActive()
    {
        return self::where('is_active', true)->first();
    }
    
    // Static method untuk get active or latest
    public static function getActiveOrLatest()
    {
        $active = self::where('is_active', true)->first();
        
        if ($active) {
            return $active;
        }
        
        // Fallback to latest
        return self::orderBy('year', 'desc')
            ->orderByRaw("FIELD(semester, 'Genap', 'Ganjil')")
            ->first();
    }
}
