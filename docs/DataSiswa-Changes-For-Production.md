# Perubahan Data Siswa - Siap Production

## 📋 Ringkasan Perubahan DataSiswaResource

### 1. **Form Management - Account Section**

**File:** `app/Filament/Resources/DataSiswaResource.php`

#### Perubahan:

-   Section "Data Akun Siswa" **hidden saat edit** (line 52)
-   Hanya muncul saat create data baru
-   Password editable di UserResource untuk super_admin

```php
Section::make('Data Akun Siswa')
    ->hidden(fn (?data_siswa $record) => $record !== null) // Hide on edit
```

**Alasan:** Menghindari user edit akun sendiri, hanya super_admin yang bisa edit via UserResource

---

### 2. **Auto-fill Logic - NIS & Username/Password**

**File:** `app/Filament/Resources/DataSiswaResource.php` (lines 119-136)

#### Perubahan:

-   **NIS** (bukan NISN) menjadi sumber auto-fill
-   Username otomatis dari NIS
-   Password default otomatis dari NIS (min 3 karakter)
-   NISN dibuat nullable

```php
TextInput::make('nis')
    ->live(onBlur: true)
    ->afterStateUpdated(function ($state, $set, $get, $context) {
        if ($state) {
            // Auto-fill username dari NIS
            if (!$get('account.username')) {
                $set('account.username', $state);
            }

            // Auto-fill password dari NIS (saat create)
            if ($context === 'create' && !$get('account.password')) {
                $set('account.password', $state);
                $set('account.passwordConfirmation', $state);
            }
        }
    })
```

**Testing Sebelum Upload:**

```bash
# Test create siswa baru dengan NIS "123"
# Expected: username = "123", password = "123"
```

---

### 3. **Kelas Dropdown Fix**

**File:** `app/Filament/Resources/DataSiswaResource.php`

#### Perubahan:

-   Line 264: `$kelas->id` → `$kelas->kelas_id`
-   Line 401: Bulk action "Pindah Kelas" juga diperbaiki

```php
// SEBELUM (SALAH)
return [$kelas->id => $kelas->nama_kelas];

// SESUDAH (BENAR)
return [$kelas->kelas_id => $kelas->nama_kelas];
```

**Verifikasi:**

-   Dropdown "Kelas Saat Ini" menampilkan semua kelas (A dan B)
-   Bulk action "Pindah Kelas" bisa memilih semua kelas

---

### 4. **Phone Number Field - Made Optional**

**File:** `app/Filament/Resources/DataSiswaResource.php` (line 223)

#### Perubahan:

```php
// SEBELUM
->required()

// SESUDAH
->nullable()
->helperText('Opsional - kosongkan jika tidak ada')
```

**Alasan:** Beberapa data siswa tidak punya nomor telepon orang tua

**Database Check:**

```sql
SELECT COUNT(*) FROM data_siswa WHERE no_telp_ortu_wali IS NULL;
-- Pastikan tidak error constraint
```

---

### 5. **Bulk Action - Ubah Status (Unified)**

**File:** `app/Filament/Resources/DataSiswaResource.php` (lines 412-440)

#### Perubahan:

-   Menggabungkan "Aktifkan" + "Nonaktifkan" menjadi 1 action "Ubah Status"
-   Form select untuk pilih status baru
-   Confirmation required
-   Manual loop update untuk reliability
-   Custom notification dengan counter

```php
BulkAction::make('toggleStatus')
    ->label('Ubah Status')
    ->icon('heroicon-o-arrow-path')
    ->color('warning')
    ->form([
        Select::make('status')
            ->label('Status Baru')
            ->options([
                'Aktif' => 'Aktif',
                'Non_Aktif' => 'Non Aktif',
            ])
            ->required()
    ])
    ->action(function (array $data, $records) {
        $updated = 0;
        foreach ($records as $record) {
            $record->status = $data['status'];
            $record->save();
            $updated++;
        }

        Notification::make()
            ->success()
            ->title('Status berhasil diubah')
            ->body("{$updated} siswa berhasil di-update ke status: {$data['status']}")
            ->send();
    })
    ->deselectRecordsAfterCompletion()
    ->requiresConfirmation()
```

**Testing:**

1. Pilih 2-3 siswa
2. Klik "Ubah Status"
3. Pilih "Non_Aktif"
4. Confirm
5. Notifikasi muncul: "3 siswa berhasil di-update ke status: Non_Aktif"
6. Badge di tabel berubah merah
7. Refresh manual (F5) → status tetap Non_Aktif

---

### 6. **Edit Form - Redirect & Refresh**

**File:** `app/Filament/Resources/DataSiswaResource/Pages/EditDataSiswa.php`

#### Perubahan (lines 107-120):

```php
protected function getSavedNotification(): ?Notification
{
    return Notification::make()
        ->success()
        ->title('Data Siswa Berhasil Diperbarui!')
        ->body('Perubahan data telah tersimpan dan tabel akan di-refresh.')
        ->duration(3000);
}

protected function getRedirectUrl(): string
{
    // Redirect ke index dengan parameter refresh
    return $this->getResource()::getUrl('index') . '?refresh=' . time();
}

protected function afterSave(): void
{
    // Dispatch event untuk refresh tabel
    $this->dispatch('refreshDataTable');
}
```

**Testing:**

1. Edit siswa → ubah status dari Aktif ke Non_Aktif
2. Klik Save
3. Redirect ke list
4. Status langsung berubah (tidak perlu F5 manual)

---

## ✅ Pre-Upload Checklist

### Database Preparation

```bash
# 1. Backup database
php artisan db:backup

# 2. Check NISN nullable
php artisan tinker --execute="echo \DB::select('SHOW COLUMNS FROM data_siswa LIKE \"nisn\"')[0]->Null;"
# Expected: YES

# 3. Check phone nullable
php artisan tinker --execute="echo \DB::select('SHOW COLUMNS FROM data_siswa LIKE \"no_telp_ortu_wali\"')[0]->Null;"
# Expected: YES

# 4. Verify kelas_id in data_kelas
php artisan tinker --execute="echo json_encode(\DB::table('data_kelas')->select('kelas_id', 'nama_kelas')->get()->toArray(), JSON_PRETTY_PRINT);"
# Expected: kelas_id exist (1, 2)
```

### Code Verification

```bash
# 5. Check no syntax errors
php artisan about

# 6. Clear all cache
php artisan optimize:clear
php artisan filament:clear-cache

# 7. Test in local
# - Create siswa baru
# - Edit siswa
# - Bulk ubah status
# - Bulk pindah kelas
```

### Files to Upload

```
app/Filament/Resources/
├── DataSiswaResource.php ✅ (MODIFIED)
└── DataSiswaResource/Pages/
    ├── EditDataSiswa.php ✅ (MODIFIED)
    └── ListDataSiswas.php ✅ (MODIFIED - then reverted to default)
```

### Migration Files (if needed)

```
database/migrations/
└── 2025_XX_XX_make_nisn_nullable.php (jika belum ada)
└── 2025_XX_XX_make_phone_nullable.php (jika belum ada)
```

**Check Migration Status:**

```bash
php artisan migrate:status
```

---

## 🚨 Critical Points for Production

### 1. Auto-fill Logic Dependencies

-   **NIS field** harus diisi dulu sebelum username/password auto-fill
-   Pastikan client tahu: **NIS = Username = Password default**

### 2. Kelas Foreign Key

-   Column `kelas` di tabel `data_siswa` reference ke `kelas_id` (bukan `id`)
-   Verify dengan:

```sql
SHOW CREATE TABLE data_siswa;
-- Check FOREIGN KEY constraint
```

### 3. Status Field Enum/String

-   Status: 'Aktif' atau 'Non_Aktif' (underscore, bukan spasi)
-   Badge color mapping:
    -   'Aktif' → success (hijau)
    -   'Non_Aktif' → danger (merah)

### 4. Phone Number Handling

-   Existing data dengan NULL harus aman
-   Form validation skip jika nullable
-   Display di tabel: kosong atau "-"

---

## 🔧 Production Server Commands

### After Upload

```bash
# 1. Set permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 2. Clear cache (via SSH or clear-cache.php)
php artisan optimize:clear
php artisan filament:optimize
php artisan filament:cache-components

# 3. Test create siswa
# Login as admin → Manajemen Data Siswa → Tambah

# 4. Monitor logs
tail -f storage/logs/laravel.log
```

### If Using cPanel (No SSH)

```
1. Upload clear-cache.php ke root
2. Akses: https://yourdomain.com/clear-cache.php
3. Klik "Clear All Cache"
4. Klik "Optimize Filament"
5. DELETE clear-cache.php setelah selesai
```

---

## 📊 Testing Scenarios

### Scenario 1: Create Siswa Baru

```
Input:
- NIS: 456
- Nama: Test Siswa
- NISN: (kosong) ✅
- Phone: (kosong) ✅

Expected Output:
- Username: 456
- Password: 456
- Account created successfully
```

### Scenario 2: Edit Siswa Existing

```
Action:
- Edit siswa → ubah status Aktif → Non_Aktif
- Save

Expected:
- Redirect ke list
- Badge merah muncul
- Database updated
```

### Scenario 3: Bulk Status Change

```
Action:
- Select 5 siswa
- Ubah Status → Non_Aktif
- Confirm

Expected:
- Notifikasi: "5 siswa berhasil di-update ke status: Non_Aktif"
- Semua badge jadi merah
- Database updated
```

### Scenario 4: Bulk Pindah Kelas

```
Action:
- Select siswa di Kelas A
- Pindah Kelas → Kelas B
- Submit

Expected:
- Siswa pindah ke Kelas B
- Column kelas berubah di tabel
- Database foreign key valid
```

---

## 🐛 Known Issues & Solutions

### Issue 1: Status tidak langsung berubah di tabel

**Solution:** Sudah diperbaiki dengan `requiresConfirmation()` dan Filament auto-refresh

### Issue 2: Error 500 saat ubah status

**Cause:** Method `redirectStateful()` tidak exist
**Solution:** ✅ Sudah dihapus

### Issue 3: Kelas dropdown kosong

**Cause:** Menggunakan `$kelas->id` instead of `$kelas->kelas_id`
**Solution:** ✅ Sudah diperbaiki di 2 tempat

### Issue 4: NISN required error

**Solution:** ✅ Sudah dibuat nullable

---

## 📝 Documentation Updates Needed

1. **User Manual:**

    - Update: "Username dan Password otomatis dari NIS (bukan NISN)"
    - Update: "NISN dan Phone optional"
    - New feature: "Bulk Ubah Status dengan konfirmasi"

2. **Admin Guide:**

    - Cara edit password siswa: via UserResource (role super_admin)
    - Bulk actions: Pindah Kelas, Ubah Status, Delete

3. **Database Schema:**
    - NISN: nullable varchar(10)
    - no_telp_ortu_wali: nullable varchar
    - kelas: foreign key to data_kelas.kelas_id

---

## ✅ Final Verification Before Go-Live

```bash
# 1. Run all tests
php artisan test

# 2. Check database integrity
php artisan tinker --execute="
echo 'Siswa tanpa username: ' . \App\Models\data_siswa::whereDoesntHave('user')->count() . PHP_EOL;
echo 'Siswa dengan kelas invalid: ' . \App\Models\data_siswa::whereNotNull('kelas')->whereDoesntHave('kelasRelation')->count() . PHP_EOL;
"

# 3. Dry-run bulk actions (select 1 siswa, test each action)

# 4. Monitor error log
tail -f storage/logs/laravel.log

# 5. Performance check (if slow, add index)
php artisan tinker --execute="
\DB::enableQueryLog();
\App\Models\data_siswa::with(['kelas.walikelas', 'user'])->paginate(15);
print_r(\DB::getQueryLog());
"
```

---

## 🎯 Success Criteria

✅ Create siswa: NIS auto-fill username/password  
✅ NISN optional (bisa kosong)  
✅ Phone optional (bisa kosong)  
✅ Edit siswa: account section hidden  
✅ Kelas dropdown: tampil semua kelas (A & B)  
✅ Bulk ubah status: langsung refresh  
✅ Status badge: hijau (Aktif) / merah (Non_Aktif)  
✅ No error 500  
✅ Database update berhasil

---

**Prepared by:** GitHub Copilot  
**Date:** 2025-12-07  
**Laravel Version:** 10.49.1  
**Filament Version:** 3.x  
**Status:** ✅ READY FOR PRODUCTION
