<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyReportBroadcast extends Model
{
    use HasFactory;
    
    protected $table = 'monthly_report_broadcasts';
    
    protected $fillable = [
        'monthly_report_id',
        'siswa_nis',
        'phone_number',
        'message',
        'status',
        'response',
        'error_message',
        'retry_count',
        'sent_at'
    ];
    
    protected $casts = [
        'sent_at' => 'datetime',
        'retry_count' => 'integer'
    ];
    
    // Relationships
    public function monthlyReport()
    {
        return $this->belongsTo(monthly_reports::class, 'monthly_report_id');
    }
    
    public function siswa()
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
    
    // Helper methods
    public function markAsSent($response = null)
    {
        $this->update([
            'status' => 'sent',
            'response' => $response,
            'sent_at' => now(),
        ]);
    }
    
    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }
}
