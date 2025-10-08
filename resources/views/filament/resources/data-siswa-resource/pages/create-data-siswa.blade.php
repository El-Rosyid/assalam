<x-filament-panels::page>
    <div>
        <!-- Form content will be injected by Filament -->
        {{ $this->form }}
        
        <!-- Success Modal -->
        <div x-data="{ showSuccess: @entangle('showSuccessModal') }" 
             x-show="showSuccess" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black opacity-50" @click="showSuccess = false"></div>
                
                <div class="relative bg-white rounded-3xl shadow-2xl max-w-lg w-full mx-auto transform transition-all scale-100">
                    <!-- Success Header -->
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-t-3xl p-8 text-center">
                        <div class="text-8xl mb-4 animate-bounce">🎊</div>
                        <h2 class="text-3xl font-bold text-white">Selamat!</h2>
                    </div>
                    
                    <!-- Success Body -->
                    <div class="p-10 text-center">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Data Siswa Berhasil Ditambahkan!</h3>
                        <p class="text-gray-600 text-lg leading-relaxed mb-8">
                            Data siswa baru telah berhasil disimpan ke dalam sistem. Akun pengguna juga telah dibuat dengan sukses.
                        </p>
                        
                        <!-- Success Checkmark Animation -->
                        <div class="flex justify-center mb-8">
                            <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center animate-pulse">
                                <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="space-y-3">
                            <button 
                                @click="window.location.href='/admin/data-siswas'"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-8 rounded-2xl transition-all duration-200 transform hover:scale-105 shadow-lg"
                            >
                                Lihat Semua Data
                            </button>
                            <button 
                                @click="window.location.href='/admin/data-siswas/create'"
                                class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-8 rounded-2xl transition-all duration-200"
                            >
                                Tambah Data Lagi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Modal -->
        <div x-data="{ showError: @entangle('showErrorModal') }" 
             x-show="showError" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black opacity-50" @click="showError = false"></div>
                
                <div class="relative bg-white rounded-3xl shadow-2xl max-w-lg w-full mx-auto transform transition-all scale-100">
                    <!-- Error Header -->
                    <div class="bg-gradient-to-r from-red-500 to-rose-600 rounded-t-3xl p-8 text-center">
                        <div class="text-8xl mb-4 animate-pulse">💥</div>
                        <h2 class="text-3xl font-bold text-white">Gagal!</h2>
                    </div>
                    
                    <!-- Error Body -->
                    <div class="p-10 text-center">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Gagal Menyimpan Data!</h3>
                        <p class="text-gray-600 text-lg leading-relaxed mb-8">
                            Terjadi kesalahan saat menyimpan data: <br>
                            <span class="font-mono text-red-600 text-sm">{{ $errorMessage }}</span>
                        </p>
                        
                        <!-- Error Icon Animation -->
                        <div class="flex justify-center mb-8">
                            <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center animate-pulse">
                                <svg class="w-16 h-16 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Action Button -->
                        <button 
                            @click="showError = false"
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-4 px-8 rounded-2xl transition-all duration-200 transform hover:scale-105 shadow-lg"
                        >
                            Coba Lagi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <x-filament-actions::modals />
</x-filament-panels::page>