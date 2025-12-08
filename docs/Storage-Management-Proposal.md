# Storage Management - Proposal & Implementation Plan

## Overview

Proposal untuk 3 fitur storage management:

1. **Admin Menu** - Dashboard untuk cleanup storage (Super Admin only)
2. **Auto-delete Old Files** - Hapus file lama saat upload baru
3. **Auto-delete on Record Delete** - Hapus file saat record dihapus

---

## FITUR 1: Storage Management Dashboard (Super Admin Only)

### 🎯 Tujuan

-   Super Admin bisa lihat statistik storage usage
-   Cleanup file orphan dengan UI friendly
-   Restore siswa dari recycle bin
-   Monitor auto-prune schedule

### 📊 UI Design Proposal

```
┌─────────────────────────────────────────────────────────────┐
│  🗄️ Storage Management (Super Admin Only)                   │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  📊 STORAGE STATISTICS                                        │
│  ┌────────────┬────────────┬────────────┬────────────┐      │
│  │ Foto Siswa │   Dokumen  │  Broadcast │   Total    │      │
│  │   12.4 MB  │   8.2 MB   │   2.1 MB   │  22.7 MB   │      │
│  └────────────┴────────────┴────────────┴────────────┘      │
│                                                               │
│  👥 STUDENT DATA                                              │
│  - Active Students: 3                                         │
│  - In Recycle Bin: 4 (will auto-delete in 90 days)          │
│  - Total: 7                                                   │
│                                                               │
│  🗑️ CLEANUP ACTIONS                                          │
│  [🔍 Scan Orphan Files]  [🧹 Cleanup Now]  [📋 View Logs]   │
│                                                               │
│  📅 SCHEDULED TASKS                                           │
│  ✅ Auto-Prune: Daily 03:00 (Next: 23 hours)                 │
│  ✅ Orphan Cleanup: Weekly Sunday 02:00 (Next: 1 day)        │
│                                                               │
│  🔄 RECYCLE BIN MANAGEMENT                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ NIS        │ Nama    │ Deleted    │ Action           │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │ 2103040009 │ tama    │ 0 days ago │ [Restore] [Del]  │   │
│  │ 2103040089 │ faiz    │ 0 days ago │ [Restore] [Del]  │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

### 🏗️ Implementation Plan

**Step 1: Create Filament Page**

```php
// app/Filament/Pages/StorageManagement.php

class StorageManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationLabel = 'Storage Management';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 99;

    // ⚠️ IMPORTANT: Super Admin Only
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    protected function getHeaderActions(): array
    {
        return [
            // Action: Scan Orphan Files
            Actions\Action::make('scan')
                ->label('Scan Orphan Files')
                ->icon('heroicon-o-magnifying-glass')
                ->action(function () {
                    // Run storage:cleanup-orphan-files --dry-run
                    $output = Artisan::call('storage:cleanup-orphan-files', ['--dry-run' => true]);
                    // Parse output and show in modal
                }),

            // Action: Cleanup Now
            Actions\Action::make('cleanup')
                ->label('Cleanup Orphan Files')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete Orphan Files?')
                ->modalDescription('This will permanently delete files that have no owner.')
                ->action(function () {
                    Artisan::call('storage:cleanup-orphan-files');
                    Notification::make()
                        ->title('Cleanup completed!')
                        ->success()
                        ->send();
                }),

            // Action: View Logs
            Actions\Action::make('logs')
                ->label('View Logs')
                ->icon('heroicon-o-document-text')
                ->url(route('log-viewer.index')), // If using log viewer package
        ];
    }

    protected function getViewData(): array
    {
        return [
            'stats' => $this->getStorageStats(),
            'studentStats' => $this->getStudentStats(),
            'trashedStudents' => $this->getTrashedStudents(),
            'scheduledTasks' => $this->getScheduledTasks(),
        ];
    }

    private function getStorageStats(): array
    {
        $directories = [
            'siswa/foto' => 'Foto Siswa',
            'siswa/akta' => 'Dokumen Akta',
            'siswa/kk' => 'Dokumen KK',
            'siswa/ijazah' => 'Dokumen Ijazah',
            'custom-broadcasts' => 'Broadcast Files',
        ];

        $stats = [];
        $total = 0;

        foreach ($directories as $dir => $label) {
            $size = $this->getDirectorySize($dir);
            $stats[$label] = $this->formatBytes($size);
            $total += $size;
        }

        $stats['Total'] = $this->formatBytes($total);

        return $stats;
    }

    private function getStudentStats(): array
    {
        return [
            'active' => data_siswa::count(),
            'trashed' => data_siswa::onlyTrashed()->count(),
            'total' => data_siswa::withTrashed()->count(),
        ];
    }

    private function getTrashedStudents()
    {
        return data_siswa::onlyTrashed()
            ->select(['nis', 'nama_lengkap', 'deleted_at'])
            ->get()
            ->map(fn($s) => [
                'nis' => $s->nis,
                'nama' => $s->nama_lengkap,
                'deleted_days' => now()->diffInDays($s->deleted_at),
                'auto_delete_in' => 90 - now()->diffInDays($s->deleted_at),
            ]);
    }
}
```

**Step 2: Create Blade View**

```blade
<!-- resources/views/filament/pages/storage-management.blade.php -->

<x-filament-panels::page>
    {{-- Storage Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        @foreach($stats as $label => $size)
            <x-filament::card>
                <div class="text-center">
                    <div class="text-2xl font-bold">{{ $size }}</div>
                    <div class="text-sm text-gray-500">{{ $label }}</div>
                </div>
            </x-filament::card>
        @endforeach
    </div>

    {{-- Student Stats --}}
    <x-filament::card class="mb-6">
        <h3 class="text-lg font-semibold mb-4">👥 Student Data</h3>
        <div class="grid grid-cols-3 gap-4">
            <div>Active: <strong>{{ $studentStats['active'] }}</strong></div>
            <div>Recycle Bin: <strong>{{ $studentStats['trashed'] }}</strong></div>
            <div>Total: <strong>{{ $studentStats['total'] }}</strong></div>
        </div>
    </x-filament::card>

    {{-- Trashed Students Table --}}
    <x-filament::card>
        <h3 class="text-lg font-semibold mb-4">🗑️ Recycle Bin</h3>
        <table class="w-full">
            <thead>
                <tr>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Deleted</th>
                    <th>Auto-Delete In</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($trashedStudents as $student)
                    <tr>
                        <td>{{ $student['nis'] }}</td>
                        <td>{{ $student['nama'] }}</td>
                        <td>{{ $student['deleted_days'] }} days ago</td>
                        <td>{{ $student['auto_delete_in'] }} days</td>
                        <td>
                            <button wire:click="restore('{{ $student['nis'] }}')">Restore</button>
                            <button wire:click="forceDelete('{{ $student['nis'] }}')">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-filament::card>
</x-filament-panels::page>
```

### ✅ Pros & Cons

**Pros:**

-   ✅ Centralized storage management
-   ✅ Super Admin only (secure)
-   ✅ Visual statistics
-   ✅ Easy restore from UI
-   ✅ One-click cleanup

**Cons:**

-   ⚠️ Perlu install package tambahan untuk log viewer
-   ⚠️ Scanning large storage bisa lambat (perlu cache)
-   ⚠️ Butuh proper authorization check

---

## FITUR 2: Auto-Delete Old Files on Update

### 🎯 Tujuan

Saat admin upload foto/dokumen baru untuk siswa, file lama otomatis terhapus.

### 📝 Current Behavior (Tanpa Auto-Delete)

```
Upload 1: foto_siswa = "siswa/foto/210_v1.jpg" (500KB)
Upload 2: foto_siswa = "siswa/foto/210_v2.jpg" (600KB)
Upload 3: foto_siswa = "siswa/foto/210_v3.jpg" (700KB)

Storage Result:
- siswa/foto/210_v1.jpg ❌ ORPHAN (tidak terpakai)
- siswa/foto/210_v2.jpg ❌ ORPHAN (tidak terpakai)
- siswa/foto/210_v3.jpg ✅ ACTIVE (terpakai)

Total Wasted: 1.1 MB per siswa
```

### ✅ Proposed Behavior (Dengan Auto-Delete)

```
Upload 1: foto_siswa = "siswa/foto/210_v1.jpg" (500KB)
Upload 2: foto_siswa = "siswa/foto/210_v2.jpg" (600KB)
         → DELETE siswa/foto/210_v1.jpg ✅
Upload 3: foto_siswa = "siswa/foto/210_v3.jpg" (700KB)
         → DELETE siswa/foto/210_v2.jpg ✅

Storage Result:
- siswa/foto/210_v3.jpg ✅ ACTIVE (terpakai)

Total Wasted: 0 MB
```

### 🏗️ Implementation Plan

**Option A: Model Observer (Recommended)**

```php
// app/Observers/DataSiswaObserver.php

namespace App\Observers;

use App\Models\data_siswa;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DataSiswaObserver
{
    /**
     * Handle the data_siswa "updating" event.
     * Dipanggil SEBELUM data diupdate
     */
    public function updating(data_siswa $siswa): void
    {
        // Get original values (sebelum update)
        $original = $siswa->getOriginal();

        // File columns yang perlu dimonitor
        $fileColumns = [
            'foto_siswa',
            'dokumen_akta',
            'dokumen_kk',
            'dokumen_ijazah',
        ];

        foreach ($fileColumns as $column) {
            // Cek apakah file berubah
            if ($siswa->isDirty($column)) {
                $oldFile = $original[$column] ?? null;
                $newFile = $siswa->$column;

                // Jika ada file lama DAN file baru berbeda
                if (!empty($oldFile) && $oldFile !== $newFile) {
                    $this->deleteOldFile($oldFile, $siswa->nis, $column);
                }
            }
        }
    }

    /**
     * Delete old file from storage
     */
    private function deleteOldFile(string $filePath, string $nis, string $column): void
    {
        // Clean path
        $filePath = str_replace(['storage/', '/storage/'], '', $filePath);

        // Check if file exists
        if (Storage::disk('public')->exists($filePath)) {
            // Delete file
            Storage::disk('public')->delete($filePath);

            // Log deletion
            Log::info("Old file auto-deleted on update", [
                'nis' => $nis,
                'column' => $column,
                'file' => $filePath,
                'size' => Storage::disk('public')->size($filePath) ?? 0,
            ]);
        }
    }
}
```

**Register Observer:**

```php
// app/Providers/EventServiceProvider.php

use App\Models\data_siswa;
use App\Observers\DataSiswaObserver;

public function boot(): void
{
    data_siswa::observe(DataSiswaObserver::class);
}
```

**Option B: Filament FileUpload Hook**

```php
// app/Filament/Resources/DataSiswaResource.php

Forms\Components\FileUpload::make('foto_siswa')
    ->label('Foto Siswa')
    ->image()
    ->directory('siswa/foto')
    ->maxSize(2048)
    // Hook: Before upload (delete old file)
    ->afterStateUpdated(function ($state, $record, $component) {
        if ($record && $record->foto_siswa && $record->foto_siswa !== $state) {
            // Delete old file
            $oldPath = str_replace(['storage/', '/storage/'], '', $record->foto_siswa);
            Storage::disk('public')->delete($oldPath);

            Log::info("Old foto_siswa deleted", ['nis' => $record->nis]);
        }
    })
    // Alternative: Use deleteOldFileOnUpdate() method
    ->deleteOldFileOnUpdate(),
```

### ✅ Pros & Cons

**Option A (Model Observer):**

-   ✅ Centralized logic (1 tempat)
-   ✅ Berlaku untuk semua update (via Filament, API, Tinker)
-   ✅ Mudah maintain
-   ✅ Auto-log setiap deletion
-   ⚠️ Perlu test thoroughly (bisa accidentally delete file)

**Option B (Filament Hook):**

-   ✅ Filament built-in method `deleteOldFileOnUpdate()`
-   ✅ Lebih simple code
-   ⚠️ Hanya berlaku via Filament (tidak via API/Tinker)
-   ⚠️ Perlu apply ke setiap FileUpload field

### ⚠️ Risks & Mitigation

**Risk 1: Accidentally Delete Active File**

```
Scenario: Update NIS siswa → file path berubah → detect as "old file" → delete
Solution: Only delete if isDirty() pada file column, bukan other columns
```

**Risk 2: Concurrent Upload**

```
Scenario: 2 admin upload bersamaan → race condition → delete wrong file
Solution: Use DB transaction + file locking
```

**Risk 3: Cannot Undo**

```
Scenario: Admin upload wrong file → auto-delete old file → cannot rollback
Solution: Implement "File Versioning" (keep last 2 versions for 7 days)
```

### 💡 Recommendation: Use Option A (Observer) + Soft File Delete

```php
// Enhanced version with "soft delete" for files

private function deleteOldFile(string $filePath, string $nis, string $column): void
{
    $filePath = str_replace(['storage/', '/storage/'], '', $filePath);

    if (Storage::disk('public')->exists($filePath)) {
        // Option 1: Hard delete (permanent)
        // Storage::disk('public')->delete($filePath);

        // Option 2: Soft delete (move to .trash for 7 days)
        $trashPath = '.trash/' . date('Y-m-d') . '/' . basename($filePath);
        Storage::disk('public')->move($filePath, $trashPath);

        // Schedule cleanup .trash folder after 7 days
        // (Add to Kernel.php scheduled tasks)

        Log::info("Old file moved to trash", [
            'nis' => $nis,
            'column' => $column,
            'from' => $filePath,
            'to' => $trashPath,
        ]);
    }
}
```

---

## FITUR 3: Auto-Delete Files on Record Delete

### 🎯 Tujuan

Saat siswa di-delete (soft atau force), file otomatis terhapus.

### 📝 Current Implementation

Sudah ada di `app/Models/data_siswa.php`:

```php
protected static function boot()
{
    parent::boot();

    static::deleting(function ($siswa) {
        // Cek apakah force delete atau soft delete
        if ($siswa->isForceDeleting()) {
            // FORCE DELETE - Cleanup files
            $siswa->cleanupFiles();
        }
        // SOFT DELETE - Keep files (untuk recovery)
    });
}

protected function cleanupFiles(): void
{
    $fileColumns = ['foto_siswa', 'dokumen_akta', 'dokumen_kk', 'dokumen_ijazah'];

    foreach ($fileColumns as $column) {
        if (!empty($this->$column)) {
            $filePath = str_replace(['storage/', '/storage/'], '', $this->$column);
            Storage::disk('public')->delete($filePath);
        }
    }
}
```

### ✅ Enhancement Proposal

**Current Behavior:**

-   ✅ Soft Delete → Keep files (Good for recovery)
-   ✅ Force Delete → Delete files (Good for cleanup)
-   ✅ Auto-Prune (90 days) → Delete files (Good for automation)

**Proposed Enhancement: Add Confirmation UI**

```php
// app/Filament/Resources/DataSiswaResource.php

Tables\Actions\ForceDeleteAction::make()
    ->label('Hapus Permanent')
    ->modalHeading('Hapus Permanent Data Siswa')
    ->modalDescription(function ($record) {
        $fileCount = 0;
        $fileSize = 0;
        $fileColumns = ['foto_siswa', 'dokumen_akta', 'dokumen_kk', 'dokumen_ijazah'];

        foreach ($fileColumns as $column) {
            if (!empty($record->$column)) {
                $fileCount++;
                $filePath = str_replace(['storage/', '/storage/'], '', $record->$column);
                if (Storage::disk('public')->exists($filePath)) {
                    $fileSize += Storage::disk('public')->size($filePath);
                }
            }
        }

        return "⚠️ PERHATIAN: Data akan dihapus PERMANENT beserta:\n" .
               "- {$fileCount} file (" . $this->formatBytes($fileSize) . ")\n" .
               "- Semua related data (assessments, attendance, growth records)\n\n" .
               "Tindakan ini TIDAK BISA dibatalkan!";
    })
    ->requiresConfirmation()
    ->modalIcon('heroicon-o-trash')
    ->modalIconColor('danger')
    // Add confirmation input
    ->modalSubmitActionLabel('Ya, Hapus Permanent')
    ->form([
        Forms\Components\TextInput::make('confirmation')
            ->label('Ketik "HAPUS" untuk konfirmasi')
            ->required()
            ->rule('in:HAPUS')
            ->helperText('Ketik kata "HAPUS" (huruf besar) untuk melanjutkan'),
    ])
    ->successNotificationTitle('Data siswa berhasil dihapus permanent'),
```

### 💡 Alternative: Move to Archive Instead of Delete

```php
protected function cleanupFiles(): void
{
    $fileColumns = ['foto_siswa', 'dokumen_akta', 'dokumen_kk', 'dokumen_ijazah'];

    foreach ($fileColumns as $column) {
        if (!empty($this->$column)) {
            $filePath = str_replace(['storage/', '/storage/'], '', $this->$column);

            if (Storage::disk('public')->exists($filePath)) {
                // Option 1: Delete permanent
                // Storage::disk('public')->delete($filePath);

                // Option 2: Archive (move to archive folder)
                $archivePath = 'archive/' . $this->nis . '/' . basename($filePath);
                Storage::disk('public')->move($filePath, $archivePath);

                // Add metadata for archive
                Storage::disk('public')->put(
                    'archive/' . $this->nis . '/metadata.json',
                    json_encode([
                        'nis' => $this->nis,
                        'nama' => $this->nama_lengkap,
                        'deleted_at' => now(),
                        'deleted_by' => auth()->id(),
                        'files' => [$column => $archivePath],
                    ])
                );

                Log::info("File archived", ['nis' => $this->nis, 'file' => $archivePath]);
            }
        }
    }
}
```

---

## SUMMARY & RECOMMENDATIONS

### 🎯 Recommended Implementation Priority

**Phase 1: High Priority (Implement Now)**

1. ✅ **Auto-delete old files on update** (Model Observer)

    - Risk: Medium
    - Impact: High (prevent orphan files)
    - Complexity: Low
    - **Action:** Use Observer dengan soft-delete (move to .trash)

2. ✅ **Storage Management Dashboard** (Super Admin Page)
    - Risk: Low
    - Impact: High (better visibility & control)
    - Complexity: Medium
    - **Action:** Create Filament Page dengan stats & actions

**Phase 2: Medium Priority (Next Sprint)** 3. ✅ **Enhanced Force Delete Confirmation**

-   Risk: Low
-   Impact: Medium (prevent accidental deletion)
-   Complexity: Low
-   **Action:** Add file count & size info to modal

**Phase 3: Low Priority (Future Enhancement)** 4. ⏳ **File Versioning System**

-   Risk: Low
-   Impact: Low (nice to have)
-   Complexity: High
-   **Action:** Keep last 2 versions for 7 days

5. ⏳ **Archive System (Instead of Delete)**
    - Risk: Low
    - Impact: Medium (audit trail)
    - Complexity: High
    - **Action:** Move files to archive folder instead of delete

### ⚠️ Important Considerations

**1. Backup Before Implementation**

```bash
# Backup database
php artisan db:backup

# Backup storage
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/app/public/
```

**2. Test in Staging First**

-   Test auto-delete on update dengan berbagai skenario
-   Test concurrent uploads
-   Test rollback mechanism

**3. Monitor Disk Space**

-   Add alerts untuk storage >80% full
-   Cleanup .trash folder setiap 7 hari
-   Archive old files ke external storage (S3/Google Drive)

**4. User Communication**

-   Beritahu admin tentang perubahan behavior
-   Add tooltip/helper text di form
-   Document di user manual

### 📋 Implementation Checklist

**Before Starting:**

-   [ ] Backup database dan storage
-   [ ] Create feature branch: `feature/storage-auto-cleanup`
-   [ ] Setup staging environment untuk testing

**Phase 1 Implementation:**

-   [ ] Create DataSiswaObserver with auto-delete logic
-   [ ] Add soft-delete untuk files (.trash folder)
-   [ ] Add scheduled task untuk cleanup .trash (7 days)
-   [ ] Create StorageManagement Filament Page
-   [ ] Add authorization (Super Admin only)
-   [ ] Add statistics dan cleanup actions
-   [ ] Add restore siswa feature dari UI
-   [ ] Write unit tests

**Testing:**

-   [ ] Test upload file → old file deleted
-   [ ] Test concurrent uploads
-   [ ] Test soft delete → files kept
-   [ ] Test force delete → files deleted
-   [ ] Test auto-prune → files deleted after 90 days
-   [ ] Test orphan cleanup command
-   [ ] Test Storage Management UI (all actions)

**Deployment:**

-   [ ] Deploy ke staging
-   [ ] Test dengan real data
-   [ ] Train admin tentang fitur baru
-   [ ] Deploy ke production
-   [ ] Monitor logs for 1 week

**Documentation:**

-   [ ] Update user manual
-   [ ] Update technical documentation
-   [ ] Add changelog entry

---

## 💬 Questions for Discussion

**Q1: Auto-delete old files on update - Immediate or Soft Delete?**

-   Option A: Delete immediately (free space faster)
-   Option B: Move to .trash for 7 days (safer, bisa rollback)
-   **Recommendation:** Option B (move to .trash)

**Q2: Storage Management - Who can access?**

-   Option A: Super Admin only (most secure)
-   Option B: Super Admin + Admin (with limited actions)
-   **Recommendation:** Option A (Super Admin only)

**Q3: Force Delete - Archive or Permanent Delete?**

-   Option A: Permanent delete (free space)
-   Option B: Move to archive folder (audit trail)
-   **Recommendation:** Option A untuk now, Option B untuk future

**Q4: Should we implement file versioning?**

-   Keep last 2-3 versions untuk 7 days?
-   Or just keep 1 version (current)?
-   **Recommendation:** Keep 1 version di .trash untuk 7 days (simpler)

---

## 🚀 Next Steps

**Immediate Actions:**

1. Review proposal ini
2. Diskusi dengan team tentang priority dan approach
3. Pilih implementation option untuk setiap fitur
4. Create development timeline
5. Setup backup strategy

**Development:**

1. Implement Phase 1 (Observer + Dashboard)
2. Test extensively di staging
3. Deploy ke production dengan monitoring
4. Collect feedback dari admin users
5. Iterate untuk Phase 2 & 3

---

**Created:** 5 Desember 2025  
**Status:** 📝 PROPOSAL - Waiting for Review & Approval  
**Next Review:** Diskusi dengan user untuk finalize approach
