<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomBroadcastLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    // Relationships
    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(CustomBroadcast::class, 'custom_broadcast_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(data_siswa::class, 'siswa_nis', 'nis');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Helper Methods
    public function markAsSent(?string $response = null): void
    {
        $this->update([
            'status' => 'sent',
            'response' => $response,
            'sent_at' => now(),
        ]);

        // Update parent broadcast counter
        $this->broadcast->incrementSent();
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);

        // Update parent broadcast counter
        $this->broadcast->incrementFailed();
    }

    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }

    // Accessors
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending' => '⏳ Pending',
            'sent' => '✅ Terkirim',
            'failed' => '❌ Gagal',
            default => $this->status,
        };
    }
}
