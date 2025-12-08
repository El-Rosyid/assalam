@if (!empty($photos) && count($photos) > 0)
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-4">
        @foreach ($photos as $index => $photo)
            @if (!empty($photo))
                <div class="relative group">
                    <img 
                        src="{{ Storage::url($photo) }}" 
                        alt="Foto Kegiatan {{ $index + 1 }}"
                        class="w-full h-32 object-cover rounded-lg shadow-sm hover:shadow-md transition-all cursor-pointer border border-gray-200 photo-thumbnail"
                        data-photo-url="{{ Storage::url($photo) }}"
                        onclick="openPhotoModal('{{ Storage::url($photo) }}')"
                    >
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all rounded-lg flex items-center justify-center pointer-events-none">
                        <svg class="w-10 h-10 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                        </svg>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <!-- Modal untuk preview gambar -->
    <div id="photoPreviewModal" class="fixed inset-0 bg-black bg-opacity-90 hidden z-[9999] items-center justify-center p-4" onclick="closePhotoModal()">
        <div class="relative max-w-7xl w-full h-full flex items-center justify-center">
            <img id="photoPreviewImage" src="" alt="Preview" class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl">
            <button 
                onclick="closePhotoModal()"
                class="absolute top-4 right-4 text-white bg-red-600 hover:bg-red-700 rounded-full p-3 transition-all shadow-lg"
                title="Tutup (ESC)"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>

    @push('scripts')
    <script>
        function openPhotoModal(photoUrl) {
            const modal = document.getElementById('photoPreviewModal');
            const modalImage = document.getElementById('photoPreviewImage');
            
            if (modal && modalImage) {
                modalImage.src = photoUrl;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }
        }

        function closePhotoModal() {
            const modal = document.getElementById('photoPreviewModal');
            
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = 'auto';
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePhotoModal();
            }
        });
    </script>
    @endpush
@else
    <div class="text-center py-8 text-gray-500 bg-gray-50 dark:bg-gray-900 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <p class="text-sm font-medium">Belum ada foto kegiatan untuk periode ini</p>
    </div>
@endif