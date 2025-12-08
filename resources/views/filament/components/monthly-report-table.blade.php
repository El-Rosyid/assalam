<div class="assessment-table-container">
    <table class="assessment-table-web">
        <thead>
            <tr>
                <th colspan="2" class="assessment-header">
                    CATATAN PERKEMBANGAN BULANAN
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <!-- Kolom Kiri: Foto Kegiatan (30%) -->
                <td class="photo-column">
                    <div class="photo-label">Foto Kegiatan</div>
                    @if(!empty($photos) && is_array($photos) && count($photos) > 0)
                        <div class="photo-grid-web">
                            @foreach(array_slice($photos, 0, 6) as $photo)
                                @php
                                    $photoUrl = null;
                                    if (\Storage::disk('public')->exists($photo)) {
                                        $photoUrl = \Storage::disk('public')->url($photo);
                                    }
                                @endphp
                                @if($photoUrl)
                                    <img src="{{ $photoUrl }}" 
                                         alt="Foto Kegiatan {{ $loop->iteration }}" 
                                         class="photo-item"
                                         onclick="openPhotoModal('{{ $photoUrl }}')">
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <svg class="empty-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Belum ada foto</span>
                        </div>
                    @endif
                </td>
                
                <!-- Kolom Kanan: Catatan Guru (70%) -->
                <td class="description-column">
                    <div class="catatan-label">Catatan dari Guru</div>
                    @if(!empty($catatan))
                        <div class="catatan-content">
                            {!! nl2br(e($catatan)) !!}
                        </div>
                    @else
                        <div class="empty-state-text">
                            <svg class="empty-icon-small" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Belum ada catatan dari guru</span>
                        </div>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
</div>

<style>
    .assessment-table-container {
        width: 100%;
        margin: 0;
    }
    
    .assessment-table-web {
        width: 100%;
        border-collapse: collapse;
        border: 2px solid #e5e7eb;
        background: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .assessment-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: bold;
        font-size: 14px;
        padding: 12px 16px;
        text-align: center;
        letter-spacing: 0.5px;
        border: 2px solid #667eea;
    }
    
    .assessment-table-web td {
        border: 2px solid #e5e7eb;
        padding: 16px;
        vertical-align: top;
    }
    
    .photo-column {
        width: 30%;
        background: #f9fafb;
        text-align: center;
    }
    
    .description-column {
        width: 70%;
        background: white;
    }
    
    .photo-label,
    .catatan-label {
        font-weight: 600;
        font-size: 13px;
        color: #374151;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e5e7eb;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .photo-grid-web {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        margin-top: 8px;
    }
    
    .photo-item {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .photo-item:hover {
        transform: scale(1.05);
        border-color: #667eea;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .catatan-content {
        font-size: 14px;
        line-height: 1.8;
        color: #1f2937;
        text-align: justify;
        white-space: pre-wrap;
    }
    
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        color: #9ca3af;
    }
    
    .empty-icon {
        width: 64px;
        height: 64px;
        margin-bottom: 12px;
        opacity: 0.5;
    }
    
    .empty-state span {
        font-size: 14px;
        font-style: italic;
    }
    
    .empty-state-text {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 24px;
        color: #9ca3af;
        font-style: italic;
        font-size: 14px;
    }
    
    .empty-icon-small {
        width: 24px;
        height: 24px;
        opacity: 0.5;
    }
    
    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .assessment-table-web {
            background: #1f2937;
            border-color: #374151;
        }
        
        .photo-column {
            background: #111827;
        }
        
        .description-column {
            background: #1f2937;
        }
        
        .assessment-table-web td {
            border-color: #374151;
        }
        
        .photo-label,
        .catatan-label {
            color: #e5e7eb;
            border-color: #374151;
        }
        
        .photo-item {
            border-color: #374151;
        }
        
        .catatan-content {
            color: #e5e7eb;
        }
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .assessment-table-web {
            display: block;
        }
        
        .assessment-table-web tbody,
        .assessment-table-web tr,
        .assessment-table-web td {
            display: block;
            width: 100%;
        }
        
        .photo-column,
        .description-column {
            width: 100%;
        }
        
        .photo-grid-web {
            grid-template-columns: repeat(3, 1fr);
        }
    }
</style>

<!-- Modal lightbox sudah ada dari photo-gallery.blade.php -->
