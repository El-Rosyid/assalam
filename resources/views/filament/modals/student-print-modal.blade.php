<div class="flex h-full">
    <!-- Left Panel - Print Settings -->
    <div class="w-2/5 bg-gray-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 p-6 flex flex-col">
        <div class="flex-1">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Pengaturan Cetak Raport</h3>
            
            <!-- Student Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 mb-6 border border-gray-200 dark:border-gray-700">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Informasi Siswa</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Nama:</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $siswa->nama_lengkap }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">NISN:</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $siswa->nisn }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Kelas:</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $siswa->kelas->nama_kelas ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Status Kelengkapan -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 mb-6 border border-gray-200 dark:border-gray-700">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Status Kelengkapan</h4>
                
                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-600">Kelengkapan Data</span>
                        <span class="font-medium">{{ number_format($completionPercentage, 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full transition-all duration-500" style="width: {{ $completionPercentage }}%"></div>
                    </div>
                </div>

                <!-- Status Message -->
                @if($completionPercentage >= 90)
                    <div class="flex items-center text-sm text-green-700 bg-green-50 rounded-lg p-3">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Raport siap dicetak</span>
                    </div>
                @elseif($completionPercentage >= 70)
                    <div class="flex items-center text-sm text-yellow-700 bg-yellow-50 rounded-lg p-3">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Data hampir lengkap</span>
                    </div>
                @else
                    <div class="flex items-center text-sm text-red-700 bg-red-50 rounded-lg p-3">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Data belum lengkap</span>
                    </div>
                @endif
            </div>

            <!-- Print Options -->
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Cetak</label>
                    <select id="print-type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="summary">Ringkasan Raport</option>
                        <option value="full">Raport Lengkap</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Semester</label>
                    <select id="semester" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="1">Semester 1</option>
                        <option value="2">Semester 2</option>
                    </select>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-400 dark:text-blue-300 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="text-sm text-blue-700 dark:text-blue-200">
                            <p class="font-medium mb-1">Catatan</p>
                            <p>Raport yang dicetak akan menggunakan data terkini yang tersedia. Pastikan semua data sudah lengkap untuk hasil optimal.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-3 pt-6 border-t border-gray-200">
            <button type="button" id="print-button" class="w-full bg-blue-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition-colors flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H3a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Cetak Raport
            </button>
            
            <button type="button" id="download-button" class="w-full bg-green-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-green-700 focus:ring-4 focus:ring-green-200 transition-colors flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Download PDF
            </button>
        </div>
    </div>

    <!-- Right Panel - Preview -->
    <div class="w-3/5 p-6 bg-white flex flex-col">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Preview Raport</h3>
            <div class="flex items-center space-x-2">
                <button type="button" id="zoom-out" class="p-2 text-gray-500 hover:text-gray-700 border border-gray-300 rounded">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                    </svg>
                </button>
                <span id="zoom-level" class="text-sm text-gray-600">100%</span>
                <button type="button" id="zoom-in" class="p-2 text-gray-500 hover:text-gray-700 border border-gray-300 rounded">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Preview Content -->
        <div class="flex-1 bg-gray-100 rounded-lg overflow-auto">
            <div id="preview-content" class="p-8">
                <!-- PDF Preview akan dimuat di sini -->
                <div class="bg-white shadow-lg rounded-lg min-h-full">
                    <div class="p-8">
                        <div class="text-center mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">RAPORT SISWA</h2>
                            <h3 class="text-xl font-semibold text-gray-700">{{ $siswa->kelas->sekolah->nama_sekolah ?? 'SEKOLAH' }}</h3>
                        </div>

                        <!-- Student Info -->
                        <div class="grid grid-cols-2 gap-8 mb-8">
                            <div class="space-y-2">
                                <div class="flex">
                                    <span class="w-24 text-gray-600">Nama</span>
                                    <span class="font-medium">: {{ $siswa->nama_lengkap }}</span>
                                </div>
                                <div class="flex">
                                    <span class="w-24 text-gray-600">NISN</span>
                                    <span class="font-medium">: {{ $siswa->nisn }}</span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex">
                                    <span class="w-24 text-gray-600">Kelas</span>
                                    <span class="font-medium">: {{ $siswa->kelas->nama_kelas ?? '-' }}</span>
                                </div>
                                <div class="flex">
                                    <span class="w-24 text-gray-600">Wali Kelas</span>
                                    <span class="font-medium">: {{ $siswa->kelas->waliKelas->nama_lengkap ?? '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Preview Message -->
                        <div class="text-center py-12 border-2 border-dashed border-gray-300 rounded-lg">
                            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Preview Raport</h3>
                            <p class="text-gray-600 mb-4">Klik tombol "Cetak Raport" atau "Download PDF" untuk melihat raport lengkap</p>
                            <p class="text-sm text-gray-500">
                                Kelengkapan data: <span class="font-medium">{{ number_format($completionPercentage, 1) }}%</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const printButton = document.getElementById('print-button');
    const downloadButton = document.getElementById('download-button');
    const printType = document.getElementById('print-type');
    const semester = document.getElementById('semester');
    
    // Print functionality
    printButton.addEventListener('click', function() {
        const type = printType.value;
        const sem = semester.value;
        const siswaId = {{ $siswa->id }};
        
        const printUrl = `/report-cards/print/${siswaId}?type=${type}&semester=${sem}`;
        window.open(printUrl, '_blank');
    });
    
    // Download functionality  
    downloadButton.addEventListener('click', function() {
        const type = printType.value;
        const sem = semester.value;
        const siswaId = {{ $siswa->id }};
        
        const downloadUrl = `/report-cards/download/${siswaId}?type=${type}&semester=${sem}`;
        window.location.href = downloadUrl;
    });
    
    // Zoom functionality
    let zoomLevel = 100;
    const zoomIn = document.getElementById('zoom-in');
    const zoomOut = document.getElementById('zoom-out');
    const zoomDisplay = document.getElementById('zoom-level');
    const previewContent = document.getElementById('preview-content');
    
    zoomIn.addEventListener('click', function() {
        if (zoomLevel < 200) {
            zoomLevel += 25;
            updateZoom();
        }
    });
    
    zoomOut.addEventListener('click', function() {
        if (zoomLevel > 50) {
            zoomLevel -= 25;
            updateZoom();
        }
    });
    
    function updateZoom() {
        zoomDisplay.textContent = zoomLevel + '%';
        previewContent.style.transform = `scale(${zoomLevel / 100})`;
        previewContent.style.transformOrigin = 'top center';
    }
});
</script>