<?php

namespace App\Console\Commands;

use App\Models\data_siswa;
use App\Models\monthly_reports;
use App\Services\WhatsAppNotificationService;
use Illuminate\Console\Command;

class WhatsAppBroadcastTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:whatsapp-broadcast 
                            {monthly_report_id? : ID dari monthly report untuk di-test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test WhatsApp broadcast untuk monthly report (tanpa benar-benar menyimpan data)';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppNotificationService $whatsappService): int
    {
        $this->info('🧪 Testing WhatsApp Broadcast System...');
        $this->newLine();
        
        // Cek apakah fitur broadcast aktif
        if (!$whatsappService->isBroadcastEnabled()) {
            $this->warn('⚠️  WhatsApp broadcast DISABLED di .env (WHATSAPP_BROADCAST_ENABLED=false)');
            $this->info('💡 Untuk mengaktifkan, set WHATSAPP_BROADCAST_ENABLED=true di file .env');
            return Command::FAILURE;
        }
        
        $this->info('✅ WhatsApp broadcast ENABLED');
        $this->newLine();
        
        // Ambil monthly report untuk test
        $monthlyReportId = $this->argument('monthly_report_id');
        
        if ($monthlyReportId) {
            $monthlyReport = monthly_reports::find($monthlyReportId);
            
            if (!$monthlyReport) {
                $this->error("❌ Monthly report dengan ID {$monthlyReportId} tidak ditemukan!");
                return Command::FAILURE;
            }
        } else {
            // Ambil monthly report yang terakhir dengan catatan
            $monthlyReport = monthly_reports::whereNotNull('catatan')
                ->where('catatan', '!=', '')
                ->latest()
                ->first();
            
            if (!$monthlyReport) {
                $this->error('❌ Tidak ada monthly report dengan catatan untuk di-test!');
                $this->info('💡 Silakan buat monthly report terlebih dahulu atau berikan ID spesifik');
                return Command::FAILURE;
            }
        }
        
        $this->info("📄 Monthly Report ID: {$monthlyReport->id}");
        $this->info("📅 Bulan/Tahun: {$monthlyReport->month}/{$monthlyReport->year}");
        $this->newLine();
        
        // Ambil data siswa
        $siswa = $monthlyReport->siswa;
        
        if (!$siswa) {
            $this->error('❌ Data siswa tidak ditemukan!');
            return Command::FAILURE;
        }
        
        $this->info("👤 Siswa: {$siswa->nama}");
        $this->info("🏫 Kelas: " . ($siswa->kelasInfo->nama_kelas ?? 'N/A'));
        $this->newLine();
        
        // Cek nomor telepon
        $phoneNumber = $siswa->no_telp_ortu_wali;
        
        if (empty($phoneNumber)) {
            $this->error("❌ Siswa {$siswa->nama} tidak memiliki nomor telepon!");
            return Command::FAILURE;
        }
        
        $this->info("📱 Nomor asli: {$phoneNumber}");
        
        // Validasi nomor
        $validatedPhone = $whatsappService->validatePhoneNumber($phoneNumber);
        
        if (!$validatedPhone) {
            $this->error("❌ Nomor telepon tidak valid!");
            return Command::FAILURE;
        }
        
        $this->info("✅ Nomor tervalidasi: {$validatedPhone}");
        $this->newLine();
        
        // Format pesan
        $message = $whatsappService->formatMonthlyReportMessage($monthlyReport);
        
        $this->info('📝 Preview Pesan:');
        $this->line('─────────────────────────────────────────────────');
        $this->line($message);
        $this->line('─────────────────────────────────────────────────');
        $this->newLine();
        
        // Konfirmasi pengiriman
        if (!$this->confirm('Apakah Anda ingin mengirim WhatsApp ini? (REAL SEND)', false)) {
            $this->warn('⏭️  Pengiriman dibatalkan (dry run only)');
            return Command::SUCCESS;
        }
        
        $this->info('📤 Mengirim WhatsApp...');
        
        // Kirim WhatsApp
        $result = $whatsappService->sendWhatsApp($validatedPhone, $message);
        
        if ($result['success']) {
            $this->info('✅ WhatsApp berhasil dikirim!');
            $this->newLine();
            $this->info('📊 Response dari Fonnte:');
            $this->line(json_encode($result['response'] ?? [], JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        } else {
            $this->error('❌ Gagal mengirim WhatsApp!');
            $this->error('Error: ' . ($result['error'] ?? 'Unknown error'));
            $this->newLine();
            
            if (isset($result['response'])) {
                $this->info('📊 Response dari Fonnte:');
                $this->line(json_encode($result['response'], JSON_PRETTY_PRINT));
            }
            
            return Command::FAILURE;
        }
    }
}
