# Custom Broadcast WhatsApp - Quick Start Guide

## 📋 Overview

Fitur Custom Broadcast memungkinkan admin mengirim pesan WhatsApp custom ke:

-   ✅ **Semua Siswa** - Broadcast ke seluruh orang tua
-   ✅ **Per Kelas** - Target kelas tertentu (bisa multiple)
-   ✅ **Per Siswa** - Pilih siswa individual

## 🚀 Setup

### 1. Jalankan Migration

```bash
php artisan migrate
```

Migration akan membuat 2 tabel:

-   `custom_broadcasts` - Data broadcast utama
-   `custom_broadcast_logs` - Tracking per penerima

### 2. Pastikan Queue Worker Running

```bash
# Development
php artisan queue:work

# Production (via cron - sudah setup)
* * * * * /opt/alt/php82/usr/bin/php /home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan queue:work database --stop-when-empty --tries=3 --max-jobs=5 >> /dev/null 2>&1
```

## 📱 Cara Menggunakan

### 1. Buat Broadcast Baru

**Menu:** WhatsApp → Broadcast WhatsApp → [+ Broadcast Baru]

**Form:**

1. **Judul Broadcast**: Untuk referensi internal (contoh: "Pengumuman Libur Natal")
2. **Isi Pesan**: Tulis pesan dengan placeholder:
    - `{nama_siswa}` → Diganti dengan nama siswa
    - `{nama_kelas}` → Diganti dengan nama kelas
    - `{nis}` → Diganti dengan NIS siswa

**Contoh Pesan:**

```
Assalamualaikum Bapak/Ibu Wali {nama_siswa},

Kami informasikan bahwa TK ABA Assalam akan libur pada:
📅 Tanggal: 25 Desember 2025
🎄 Acara: Libur Natal

Untuk siswa kelas {nama_kelas}, mohon membawa kartu laporan saat masuk kembali.

Terima kasih atas perhatiannya.
```

3. **Gambar (Opsional)**: Upload 1 foto (max 2MB)
4. **Kirim Ke**: Pilih target

    - **Semua Siswa**: Otomatis ke semua
    - **Per Kelas**: Pilih 1 atau lebih kelas
    - **Per Siswa**: Search dan pilih siswa tertentu

5. Klik **[Simpan]** → Status: Draft

### 2. Kirim Broadcast

1. Setelah disimpan, akan redirect ke **Detail Page**
2. Review informasi broadcast
3. Klik **[📤 Kirim Sekarang]**
4. Konfirmasi: "Kirim ke X orang tua?"
5. Klik **[Ya, Kirim Sekarang]**

**Proses:**

-   Status berubah: Draft → Sending → Completed
-   Progress real-time: "145/150 terkirim (96%)"
-   Tab detail: Terkirim | Gagal | Pending

### 3. Monitor Status

**Di List Page:**

-   Progress bar per broadcast
-   Badge status (Draft/Mengirim/Selesai)
-   Count terkirim/gagal

**Di Detail Page:**

-   Stats: Total/Terkirim/Gagal/Progress%
-   Tabel logs dengan tab filter
-   Lihat error message untuk yang gagal

### 4. Retry Failed Messages

Jika ada pesan gagal:

1. Buka Detail Page broadcast
2. Klik **[🔄 Kirim Ulang yang Gagal]**
3. Konfirmasi
4. System akan retry pengiriman

## 🎨 Fitur UI

### List Table

```
+----+------------------+--------+-----------+----------+
| ID | Judul            | Target | Penerima  | Status   |
+----+------------------+--------+-----------+----------+
| #3 | Libur Natal      | 👥 Semua| 150/150   | ✅ Selesai|
| #2 | Info Acara       | 🏫 TK A | 25/30     | 📤 Kirim  |
|    |                  |        | (5 gagal) |          |
| #1 | Pengumuman Draft | 👤 3    | 0/3       | 📝 Draft |
+----+------------------+--------+-----------+----------+
```

### Detail Page Sections

1. **Informasi Broadcast** - Metadata
2. **Statistik Pengiriman** - Progress bars & counts
3. **Isi Pesan** - Preview message
4. **Gambar Attachment** - Jika ada foto
5. **Detail Pengiriman** (Tab) - Logs per siswa

### Actions

-   **📤 Kirim Sekarang** - Draft only
-   **🔄 Kirim Ulang yang Gagal** - Completed with failures
-   **✏️ Edit** - Draft only
-   **🗑️ Hapus** - Draft only

## 📊 Database Schema

### custom_broadcasts

-   `user_id` - Admin creator
-   `title` - Internal reference
-   `message` - Template with placeholders
-   `image_path` - Optional attachment
-   `target_type` - all/class/individual
-   `target_ids` - JSON array of IDs
-   `status` - draft/sending/completed/failed
-   `total_recipients` - Total count
-   `sent_count` - Successfully sent
-   `failed_count` - Failed count

### custom_broadcast_logs

-   `custom_broadcast_id` - Parent FK
-   `siswa_nis` - Student FK
-   `phone_number` - Validated phone (62xxx)
-   `message` - Final message (placeholders replaced)
-   `status` - pending/sent/failed
-   `response` - Fonnte API response JSON
-   `error_message` - Error if failed
-   `retry_count` - Number of attempts

## 🔧 Technical Details

### Queue Processing

-   Job: `SendCustomBroadcastJob`
-   Tries: 3 attempts
-   Backoff: 60 seconds between retries
-   Auto-stop when empty (safe for shared hosting)

### Message Formatting

-   Placeholders replaced per recipient
-   Auto-add footer: "_Pesan otomatis dari {sekolah}_"
-   Support WhatsApp markdown (_bold_, _italic_)

### Image Handling

-   Storage: `public/custom-broadcasts/`
-   Convert to public URL for Fonnte API
-   Single image per broadcast

### Phone Validation

-   Clean non-numeric characters
-   Convert 0812 → 62812
-   Skip invalid numbers automatically

## ⚠️ Important Notes

1. **Draft vs Sent**: Hanya draft yang bisa diedit/dihapus
2. **Queue Required**: Pastikan queue worker running (via cron)
3. **Image Public URL**: Development (localhost) - image tidak terkirim, Production - OK
4. **Placeholder Case-Sensitive**: `{nama_siswa}` bukan `{Nama_Siswa}`
5. **Retry Limit**: Max 3x retry per message, setelah itu mark as failed

## 📁 Files Created

```
database/migrations/
├── 2025_12_05_000001_create_custom_broadcasts_table.php
└── 2025_12_05_000002_create_custom_broadcast_logs_table.php

app/Models/
├── CustomBroadcast.php
└── CustomBroadcastLog.php

app/Jobs/
└── SendCustomBroadcastJob.php

app/Services/
└── WhatsAppNotificationService.php (updated)

app/Filament/Resources/
├── CustomBroadcastResource.php
├── CustomBroadcastResource/Pages/
│   ├── ListCustomBroadcasts.php
│   ├── CreateCustomBroadcast.php
│   ├── EditCustomBroadcast.php
│   └── ViewCustomBroadcast.php
└── CustomBroadcastResource/RelationManagers/
    └── LogsRelationManager.php

resources/views/filament/custom/
└── message-preview.blade.php
```

## 🎯 Next Steps

1. ✅ Run migration: `php artisan migrate`
2. ✅ Test create broadcast (Draft)
3. ✅ Test send to small group (1-2 siswa)
4. ✅ Monitor logs & queue
5. ✅ Test retry failed
6. ✅ Production deployment

---

**Created:** 5 Desember 2025  
**Version:** 1.0.0
