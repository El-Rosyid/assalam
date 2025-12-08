<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class data_siswa extends Model
{
    use HasFactory, SoftDeletes, Prunable;
    protected $table = 'data_siswa';

    // Natural Key Configuration
    protected $primaryKey = 'nis';
    public $incrementing = false;
    protected $keyType = 'string'; // Changed from int to match VARCHAR(15) in database

    /**
     * Boot the model.
     * Override newQuery to ensure proper key handling
     */
    protected static function boot()
    {
        parent::boot();
        
        // Event: Before deleting (soft delete)
        static::deleting(function ($siswa) {
            // Check if it's force delete
            if ($siswa->isForceDeleting()) {
                // Permanent delete - cleanup everything
                $siswa->cleanupFiles();
                $siswa->cleanupRelatedData();
            }
            // Soft delete - keep files and related data
        });
        
        // Event: After restoring
        static::restored(function ($siswa) {
            // Optional: Log restoration
            Log::info("Student restored", [
                'nis' => $siswa->nis,
                'nama' => $siswa->nama_lengkap
            ]);
        });
    }

    /**
     * Get the route key for the model.
     * This tells Laravel to use 'nis' for route model binding
     */
    public function getRouteKeyName()
    {
        return 'nis';
    }

    protected $guarded =[];
    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'nisn',
        'nis',
        'kelas', // Foreign key ke data_kelas
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

    protected $casts = [
        'nis' => 'string', // VARCHAR(15) identifier
        'nisn' => 'string', // VARCHAR(12) identifier
        'kelas' => 'integer',
        'anak_ke' => 'integer', // TINYINT UNSIGNED
        'jumlah_saudara' => 'integer', // TINYINT UNSIGNED
        'is_active' => 'boolean',
        'tanggal_lahir' => 'date',
        'tanggal_diterima' => 'date',
    ];

    // Define attributes to prevent undefined property access
    protected $attributes = [
        'kelas' => null,
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

     // Relasi ke student assessments
    public function studentAssessments()
    {
        return $this->hasMany(student_assessment::class, 'siswa_nis', 'nis');
    }

    // Relasi ke growth records
    public function growthRecords()
    {
        return $this->hasMany(GrowthRecord::class, 'siswa_nis', 'nis');
    }

    // Relasi ke attendance records
    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'siswa_nis', 'nis');
    }

    public function kelas()
    {
        return $this->belongsTo(data_kelas::class, 'kelas', 'kelas_id');
    }

     // Alias untuk konsistensi dengan kode lain yang menggunakan kelasInfo
    public function kelasInfo()
    {
        return $this->belongsTo(data_kelas::class, 'kelas', 'kelas_id');
    }

    // Accessor untuk nama kelas dengan fallback
    public function getNamaKelasAttribute()
    {
        try {
            return $this->kelas?->nama_kelas ?? 'Belum ada kelas';
        } catch (\Exception $e) {
            return 'Error loading kelas';
        }
    }

    // Helper untuk mendapatkan kelas dengan aman
    public function getKelasInfo()
    {
        try {
            return $this->kelas;
        } catch (\Exception $e) {
            return null;
        }
    }

    // Helper untuk mendapatkan wali kelas dengan aman
    public function getWaliKelasInfo()
    {
        try {
            $kelas = $this->kelas;
            return $kelas ? $kelas->walikelas : null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Scope untuk siswa yang belum punya kelas
     */
    public function scopeWithoutClass($query)
    {
        return $query->whereNull('kelas');
    }
    
    /**
     * Scope untuk siswa dengan kelas tertentu
     */
    public function scopeInClass($query, $kelasId)
    {
        return $query->where('kelas', $kelasId);
    }
    
    /**
     * Cleanup uploaded files when permanently deleting student
     */
    protected function cleanupFiles(): void
    {
        // List of file columns that might contain uploads
        $fileColumns = [
            'foto_siswa',
            'dokumen_akta',
            'dokumen_kk',
            'dokumen_ijazah',
            // Add other file columns here
        ];
        
        foreach ($fileColumns as $column) {
            if (!empty($this->$column)) {
                $filePath = $this->$column;
                
                // Remove 'storage/' or '/storage/' prefix if exists
                $filePath = str_replace(['storage/', '/storage/'], '', $filePath);
                
                // Delete from public disk
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                    Log::info("Deleted file: {$filePath} for student NIS: {$this->nis}");
                }
            }
        }
    }
    
    /**
     * Cleanup or handle related data when permanently deleting student
     */
    protected function cleanupRelatedData(): void
    {
        // Get counts before deletion for logging
        $relatedCount = [
            'assessments' => $this->studentAssessments()->count(),
            'growth_records' => $this->growthRecords()->count(),
            'attendance' => $this->attendanceRecords()->count(),
        ];
        
        if (array_sum($relatedCount) > 0) {
            Log::warning("Student being permanently deleted with related data", [
                'nis' => $this->nis,
                'nama' => $this->nama_lengkap,
                'related_data' => $relatedCount
            ]);
            
            // CASCADE DELETE: Delete all related data
            // This will trigger force delete on related models,
            // which will cleanup images automatically
            
            // Delete assessments (will also delete details and images via boot event)
            foreach ($this->studentAssessments as $assessment) {
                $assessment->forceDelete(); // Force delete to trigger image cleanup
            }
            
            // Delete growth records
            $this->growthRecords()->forceDelete();
            
            // Delete attendance records
            $this->attendanceRecords()->forceDelete();
            
            Log::info("Related data cleaned up for student: {$this->nis}");
        }
    }
    
    /**
     * Scope untuk siswa yang sudah di-soft delete
     */
    public function scopeOnlyTrashed($query)
    {
        return $query->whereNotNull('deleted_at');
    }
    
    /**
     * Scope untuk siswa aktif (belum dihapus)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
    
    /**
     * Get the prunable model query.
     * Siswa yang sudah >90 hari di recycle bin akan di-force delete otomatis
     */
    public function prunable()
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subDays(90));
    }
    
    /**
     * Prepare the model for pruning.
     * Method ini dipanggil sebelum model di-prune (force delete)
     */
    protected function pruning()
    {
        // Cleanup files dan related data sebelum permanent delete
        $this->cleanupFiles();
        $this->cleanupRelatedData();
        
        Log::info("Student auto-pruned (>90 days in recycle bin)", [
            'nis' => $this->nis,
            'nama' => $this->nama_lengkap,
            'deleted_at' => $this->deleted_at,
        ]);
    }
    
}
