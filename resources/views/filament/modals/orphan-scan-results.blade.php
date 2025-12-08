<div>
    <div class="space-y-4">
        <div class="bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-700 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-warning-600 dark:text-warning-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h4 class="font-semibold text-warning-800 dark:text-warning-200">Preview Mode</h4>
                    <p class="text-sm text-warning-700 dark:text-warning-300 mt-1">
                        This is a preview only. No files will be deleted. Use the "Cleanup Orphan Files" button to actually delete these files.
                    </p>
                </div>
            </div>
        </div>

        @php
            // Get all files from storage
            $storagePath = storage_path('app/public');
            $folders = ['siswa/foto', 'siswa/akta', 'siswa/kk', 'siswa/ijazah', 'custom-broadcasts'];
            
            $orphans = [];
            $totalSize = 0;
            
            foreach ($folders as $folder) {
                $folderPath = $storagePath . '/' . $folder;
                if (!file_exists($folderPath)) continue;
                
                $files = \Illuminate\Support\Facades\File::files($folderPath);
                foreach ($files as $file) {
                    $filename = $file->getFilename();
                    $relativePath = str_replace($storagePath . '/', '', $file->getPathname());
                    
                    // Check if file is referenced in database
                    $isOrphan = false;
                    
                    if (strpos($folder, 'siswa/') === 0) {
                        $column = str_replace('siswa/', 'dokumen_', $folder);
                        if ($column === 'dokumen_foto') $column = 'foto_siswa';
                        
                        $exists = \App\Models\data_siswa::withTrashed()
                            ->where($column, 'LIKE', '%' . $filename . '%')
                            ->exists();
                        
                        $isOrphan = !$exists;
                    } elseif ($folder === 'custom-broadcasts') {
                        $exists = \App\Models\CustomBroadcast::where('file_path', 'LIKE', '%' . $filename . '%')
                            ->exists();
                        $isOrphan = !$exists;
                    }
                    
                    if ($isOrphan) {
                        $size = $file->getSize();
                        $orphans[] = [
                            'path' => $relativePath,
                            'name' => $filename,
                            'size' => $size,
                            'size_formatted' => $size < 1024 ? $size . ' B' : 
                                              ($size < 1048576 ? number_format($size / 1024, 2) . ' KB' : 
                                              number_format($size / 1048576, 2) . ' MB'),
                            'folder' => $folder,
                            'modified' => date('Y-m-d H:i:s', $file->getMTime())
                        ];
                        $totalSize += $size;
                    }
                }
            }
            
            $totalSizeFormatted = $totalSize < 1024 ? $totalSize . ' B' : 
                                ($totalSize < 1048576 ? number_format($totalSize / 1024, 2) . ' KB' : 
                                number_format($totalSize / 1048576, 2) . ' MB');
        @endphp

        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <div class="grid grid-cols-2 gap-4 text-center">
                <div>
                    <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                        {{ count($orphans) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Orphan Files</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-danger-600 dark:text-danger-400">
                        {{ $totalSizeFormatted }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Size</div>
                </div>
            </div>
        </div>

        @if(count($orphans) > 0)
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @foreach($orphans as $orphan)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <span class="font-mono text-sm font-medium truncate">{{ $orphan['name'] }}</span>
                                </div>
                                <div class="flex items-center gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    <span>📁 {{ $orphan['folder'] }}</span>
                                    <span>📅 {{ $orphan['modified'] }}</span>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400">
                                    {{ $orphan['size_formatted'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <svg class="w-16 h-16 mx-auto text-success-500 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-lg font-semibold text-success-600 dark:text-success-400">All Clean!</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">No orphan files found in storage.</p>
            </div>
        @endif
    </div>
</div>
