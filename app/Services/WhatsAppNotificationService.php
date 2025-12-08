<?php

namespace App\Services;

use App\Models\data_siswa;
use App\Models\monthly_reports;
use App\Models\Sekolah;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    protected $fonnteApiUrl;
    protected $fonnteToken;
    
    public function __construct()
    {
        // Support both FONNTE_API_TOKEN and legacy FONNTE_TOKEN env names
        // Use config() to read from cached config OR fallback to env() if config not set
        $this->fonnteToken = config('services.fonnte.token') ?: env('FONNTE_API_TOKEN') ?: env('FONNTE_TOKEN');
        $this->fonnteApiUrl = config('services.fonnte.url') ?: env('FONNTE_URL', 'https://api.fonnte.com/send');
    }
    
    /**
     * Format pesan WhatsApp untuk laporan bulanan
     * @param bool $includeFullReport - True untuk menyertakan isi lengkap laporan
     */
    public function formatMonthlyReportMessage(monthly_reports $monthlyReport, bool $includeFullReport = true): string
    {
        $siswa = $monthlyReport->siswa;
        $sekolah = sekolah::first();
        
        // Format bulan dan tahun dalam Bahasa Indonesia
        $bulanIndonesia = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        $bulan = $bulanIndonesia[$monthlyReport->month] ?? $monthlyReport->month;
        $tahun = $monthlyReport->year;
        
        // Link website sekolah
        $websiteLink = $sekolah->website ?? 'www.abaassalam.my.id';
        
        // Format pesan header
        $message = "🔔 *Laporan Bulanan TK ABA Assalam*\n\n";
        $message .= "Assalamualaikum Yth. Orang Tua/Wali dari:\n";
        $message .= "*{$siswa->nama_lengkap}*\n";
        $message .= "Periode: *{$bulan} {$tahun}*\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━\n\n";
        
        // Jika includeFullReport = true, tampilkan isi laporan
        if ($includeFullReport) {
            // Catatan Bulanan dari Guru
            if (!empty($monthlyReport->catatan)) {
                $message .= "📝 *Catatan Bulanan:*\n";
                $message .= $this->formatText($monthlyReport->catatan);
                $message .= "\n\n";
            }
            
            // Capaian Perkembangan (jika ada field ini)
            if (!empty($monthlyReport->capaian_perkembangan)) {
                $message .= "📊 *Capaian Perkembangan:*\n";
                $message .= $this->formatText($monthlyReport->capaian_perkembangan);
                $message .= "\n\n";
            }
            
            // Stimulasi untuk Orang Tua (jika ada field ini)
            if (!empty($monthlyReport->stimulasi_orangtua)) {
                $message .= "💡 *Stimulasi untuk Orang Tua:*\n";
                $message .= $this->formatText($monthlyReport->stimulasi_orangtua);
                $message .= "\n\n";
            }
            
            // Info foto kegiatan
            if (!empty($monthlyReport->photos)) {
                $photoCount = count($monthlyReport->photos);
                $message .= "📷 *Foto Kegiatan:* {$photoCount} foto\n";
                $message .= "_Foto akan dikirim bersamaan dengan pesan ini_\n\n";
            }
            
            $message .= "━━━━━━━━━━━━━━━━━━━\n\n";
        }
        
        // Footer
        $message .= "Untuk melihat detail lengkap, silakan login ke:\n";
        $message .= "🌐 {$websiteLink}\n\n";
        $message .= "Terima kasih atas perhatian dan kerjasamanya.\n\n";
        $message .= "_Pesan otomatis dari {$sekolah->nama_sekolah}_";
        
        return $message;
    }
    
    /**
     * Format text untuk WhatsApp (trim dan limit panjang jika perlu)
     */
    private function formatText(string $text, int $maxLength = 500): string
    {
        $text = trim($text);
        
        // Limit panjang text untuk menghindari pesan terlalu panjang
        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength) . '...';
        }
        
        return $text;
    }
    
    /**
     * Validasi nomor telepon siswa
     */
    public function validatePhoneNumber(?string $phoneNumber): ?string
    {
        if (empty($phoneNumber)) {
            return null;
        }
        
        // Bersihkan nomor dari karakter non-numerik
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Jika dimulai dengan 0, ganti dengan 62
        if (substr($cleaned, 0, 1) === '0') {
            $cleaned = '62' . substr($cleaned, 1);
        }
        
        // Jika tidak dimulai dengan 62, tambahkan
        if (substr($cleaned, 0, 2) !== '62') {
            $cleaned = '62' . $cleaned;
        }
        
        // Validasi panjang nomor (minimal 10 digit setelah kode negara)
        if (strlen($cleaned) < 10) {
            return null;
        }
        
        return $cleaned;
    }
    
    /**
     * Kirim pesan WhatsApp via Fonnte API
     * @param string $phoneNumber - Nomor telepon tujuan
     * @param string $message - Pesan teks
     * @param array|null $imageUrls - Array URL gambar yang akan dikirim (max 5 images)
     */
    public function sendWhatsApp(string $phoneNumber, string $message, ?array $imageUrls = null, ?string $documentUrl = null): array
    {
        try {
            $payload = [
                'target' => $phoneNumber,
                'message' => $message,
                'countryCode' => '62',
            ];
            
            // Priority: Document file dulu, baru image
            if (!empty($documentUrl)) {
                // Untuk dokumen (PDF, DOC, XLSX, dll) gunakan parameter 'file'
                $payload['file'] = $documentUrl;
            } elseif (!empty($imageUrls)) {
                // Jika tidak ada dokumen, kirim gambar
                // Fonnte support single image via 'url' atau multiple via 'file[]'
                // Ambil max 5 gambar pertama (limit Fonnte)
                $images = array_slice($imageUrls, 0, 5);
                
                // Untuk single image gunakan 'url', untuk multiple gunakan 'file'
                if (count($images) === 1) {
                    $payload['url'] = $images[0];
                } else {
                    // Multiple images - join dengan pipe separator
                    $payload['url'] = implode('|', $images);
                }
            }
            
            // Log request payload
            Log::info('WhatsApp API Request', [
                'phone' => $phoneNumber,
                'token' => substr($this->fonnteToken, 0, 10) . '...',
                'url' => $this->fonnteApiUrl,
                'payload_keys' => array_keys($payload),
                'has_document' => !empty($documentUrl),
                'has_images' => !empty($imageUrls),
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => $this->fonnteToken,
            ])->post($this->fonnteApiUrl, $payload);
            
            $result = $response->json();
            
            // Log response
            Log::info('WhatsApp API Response', [
                'phone' => $phoneNumber,
                'status_code' => $response->status(),
                'response' => $result,
            ]);
            
            if ($response->successful() && ($result['status'] ?? false)) {
                return [
                    'success' => true,
                    'response' => $result,
                ];
            }
            
            return [
                'success' => false,
                'error' => $result['reason'] ?? 'Unknown error',
                'response' => $result,
            ];
            
        } catch (\Exception $e) {
            Log::error('WhatsApp send failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get full URLs untuk gambar dari monthly report
     * Converts storage paths to public URLs
     */
    public function getImageUrls(monthly_reports $monthlyReport): array
    {
        if (empty($monthlyReport->photos)) {
            return [];
        }
        
        $urls = [];
        $baseUrl = env('APP_URL', 'https://abaassalam.my.id');
        
        foreach ($monthlyReport->photos as $photoPath) {
            // Skip jika null atau empty
            if (empty($photoPath)) {
                continue;
            }
            
            // Jika sudah full URL, gunakan langsung
            if (str_starts_with($photoPath, 'http://') || str_starts_with($photoPath, 'https://')) {
                $urls[] = $photoPath;
            } 
            // Jika path dimulai dengan 'public/', convert ke URL
            elseif (str_starts_with($photoPath, 'public/')) {
                $publicPath = str_replace('public/', 'storage/', $photoPath);
                $urls[] = rtrim($baseUrl, '/') . '/' . ltrim($publicPath, '/');
            } 
            // Path relatif tanpa 'public/' prefix (default dari Filament FileUpload)
            else {
                $urls[] = rtrim($baseUrl, '/') . '/storage/' . ltrim($photoPath, '/');
            }
        }
        
        return $urls;
    }
    
    /**
     * Format pesan custom broadcast dengan placeholder replacement
     * Placeholder yang didukung: {nama_siswa}, {nama_kelas}, {nis}
     */
    public function formatCustomMessage(string $messageTemplate, data_siswa $siswa, string $judulPesan = ''): string
    {
        $sekolah = Sekolah::first();
        
        // Get kelas info
        $kelasInfo = $siswa->kelasInfo;
        $namaKelas = $kelasInfo->nama_kelas ?? 'Unknown';
        
        // Format tanggal Indonesia
        $tanggal = \Carbon\Carbon::now()->isoFormat('dddd, D MMMM YYYY');
        
        // Build template pesan formal
        $formalMessage = "Assalamu'alaikum Wr. Wb.\n";
        $formalMessage .= "Yth. Bapak/Ibu Wali Murid dari *{$siswa->nama_lengkap}* (Kelas {$namaKelas}),\n\n";
        $formalMessage .= "Berikut kami sampaikan informasi terbaru:\n\n";
        
        if (!empty($judulPesan)) {
            $formalMessage .= "📌 *{$judulPesan}*\n\n";
        }
        
        $formalMessage .= "{$messageTemplate}\n\n";
        $formalMessage .= "───────────────────────\n";
        $formalMessage .= "📅 Tanggal: {$tanggal}\n";
        $formalMessage .= "🏫 Pengirim: {$sekolah->nama_sekolah}\n";
        $formalMessage .= "───────────────────────\n\n";
        $formalMessage .= "Terima kasih atas perhatian dan kerja samanya.\n";
        $formalMessage .= "Wassalamu'alaikum Wr. Wb.";
        
        return $formalMessage;
    }
    
    /**
     * Cek apakah fitur broadcast aktif
     */
    public function isBroadcastEnabled(): bool
    {
        return env('WHATSAPP_BROADCAST_ENABLED', false);
    }
}
