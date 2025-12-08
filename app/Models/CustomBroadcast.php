<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomBroadcast extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'target_ids' => 'array',
        'sent_at' => 'datetime',
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CustomBroadcastLog::class);
    }

    public function sentLogs(): HasMany
    {
        return $this->hasMany(CustomBroadcastLog::class)->where('status', 'sent');
    }

    public function failedLogs(): HasMany
    {
        return $this->hasMany(CustomBroadcastLog::class)->where('status', 'failed');
    }

    public function pendingLogs(): HasMany
    {
        return $this->hasMany(CustomBroadcastLog::class)->where('status', 'pending');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSending($query)
    {
        return $query->where('status', 'sending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Helper Methods
    public function markAsSending(): void
    {
        $this->update([
            'status' => 'sending',
            'sent_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    public function incrementSent(): void
    {
        $this->increment('sent_count');
        
        // Auto mark as completed jika semua sudah terkirim/gagal
        if (($this->sent_count + $this->failed_count) >= $this->total_recipients) {
            $this->markAsCompleted();
        }
    }

    public function incrementFailed(): void
    {
        $this->increment('failed_count');
        
        // Auto mark as completed jika semua sudah terkirim/gagal
        if (($this->sent_count + $this->failed_count) >= $this->total_recipients) {
            $this->markAsCompleted();
        }
    }

    // Accessors
    public function getPendingCountAttribute(): int
    {
        return $this->total_recipients - $this->sent_count - $this->failed_count;
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_recipients == 0) {
            return 0;
        }
        
        return (int) round(($this->sent_count / $this->total_recipients) * 100);
    }

    public function getTargetTypeTextAttribute(): string
    {
        return match($this->target_type) {
            'all' => 'Semua Siswa',
            'class' => 'Per Kelas',
            'individual' => 'Per Siswa',
            default => 'Unknown',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'draft' => '📝 Draft',
            'sending' => '📤 Mengirim',
            'completed' => '✅ Selesai',
            'failed' => '❌ Gagal',
            default => $this->status,
        };
    }

    // Get recipients based on target type
    public function getRecipients()
    {
        return match($this->target_type) {
            'all' => data_siswa::all(),
            'class' => data_siswa::whereIn('kelas', $this->target_ids)->get(),
            'individual' => data_siswa::whereIn('nis', $this->target_ids)->get(),
            default => collect(),
        };
    }
}
