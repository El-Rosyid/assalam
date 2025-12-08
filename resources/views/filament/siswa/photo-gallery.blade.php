<div>
    @if(!empty($photos) && is_array($photos))
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($photos as $photo)
                <div class="relative group">
                    <img src="{{ asset('storage/' . $photo) }}" 
                         alt="Foto kegiatan"
                         class="w-full h-32 object-cover rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer photo-thumbnail"
                         data-photo-url="{{ asset('storage/' . $photo) }}">>
                    
                    <!-- Overlay untuk zoom icon -->
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 rounded-lg flex items-center justify-center">
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Modal untuk preview gambar -->
        <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50 flex items-center justify-center p-4">
            <div class="relative max-w-4xl max-h-full">
                <img id="modalImage" src="" alt="Preview" class="max-w-full max-h-full object-contain rounded-lg">
                <button id="closeModalBtn" class="absolute top-4 right-4 text-white hover:text-gray-300 transition-colors">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Photo thumbnail click handlers
                const photoThumbnails = document.querySelectorAll('.photo-thumbnail');
                const modal = document.getElementById('imageModal');
                const modalImage = document.getElementById('modalImage');
                const closeBtn = document.getElementById('closeModalBtn');

                photoThumbnails.forEach(function(thumbnail) {
                    thumbnail.addEventListener('click', function() {
                        const photoUrl = this.getAttribute('data-photo-url');
                        modalImage.src = photoUrl;
                        modal.classList.remove('hidden');
                        document.body.style.overflow = 'hidden';
                    });
                });

                // Close modal handlers
                if (closeBtn) {
                    closeBtn.addEventListener('click', function() {
                        modal.classList.add('hidden');
                        document.body.style.overflow = 'auto';
                    });
                }

                // Close modal when clicking outside
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.classList.add('hidden');
                        document.body.style.overflow = 'auto';
                    }
                });

                // Close modal with Escape key
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') {
                        modal.classList.add('hidden');
                        document.body.style.overflow = 'auto';
                    }
                });
            });
        </script>

        <p class="text-sm text-gray-500 mt-4">
            {{ count($photos) }} foto kegiatan. Klik untuk memperbesar.
        </p>
    @else
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <p class="text-gray-500 text-sm mt-2">Belum ada foto kegiatan</p>
        </div>
    @endif
</div>