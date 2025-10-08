@props(['title', 'description', 'icon' => '⚠️'])

<div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showErrorModal') }" x-show="show" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" x-on:click="show = false"></div>
        
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full mx-auto transform transition-all">
            <!-- Header -->
            <div class="bg-gradient-to-r from-red-500 to-rose-600 rounded-t-2xl p-6 text-center">
                <div class="text-6xl mb-4">{{ $icon }}</div>
                <h2 class="text-2xl font-bold text-white">{{ $title }}</h2>
            </div>
            
            <!-- Body -->
            <div class="p-8 text-center">
                <p class="text-gray-600 text-lg leading-relaxed mb-8">
                    {{ $description }}
                </p>
                
                <!-- Error Icon Animation -->
                <div class="flex justify-center mb-6">
                    <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                </div>
                
                <!-- Action Button -->
                <button 
                    x-on:click="show = false"
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-4 px-8 rounded-xl transition-colors duration-200 transform hover:scale-105"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>