<div class="filament-widget">
    @php
        $data = $this->getData();
    @endphp
    
    @if(!empty($data))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-primary-500 to-primary-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Profil Siswa
                </h2>
            </div>
            
            <!-- Content Grid -->
            <div class="p-6">
                <div class="grid grid-cols-1 gap-6">
                    <!-- Foto Profil -->
                    <div class="flex flex-col items-center justify-center space-y-4">
                        @if(!empty($data['avatar']))
                            <!-- Avatar dari users table -->
                            <img 
                                src="{{ Storage::url($data['avatar']) }}" 
                                alt="Foto Profil {{ $data['nama'] }}"
                                class="w-32 h-32 rounded-full object-cover shadow-lg border-4 border-primary-200 dark:border-primary-700"
                                onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-32 h-32 rounded-full bg-gradient-to-br from-primary-100 to-primary-200 dark:from-primary-900 dark:to-primary-800 flex items-center justify-center text-5xl font-bold text-primary-600 dark:text-primary-400 shadow-lg\'>{{ substr($data['nama'], 0, 1) }}</div>';"
                            >
                        @else
                            <!-- Fallback: Initial placeholder -->
                            <div class="w-32 h-32 rounded-full bg-gradient-to-br from-primary-100 to-primary-200 dark:from-primary-900 dark:to-primary-800 flex items-center justify-center text-5xl font-bold text-primary-600 dark:text-primary-400 shadow-lg">
                                {{ substr($data['nama'], 0, 1) }}
                            </div>
                        @endif
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $data['nama'] }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $data['kelas'] }}</p>
                        </div>
                    </div>
                    
                    <!-- Data Diri -->
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <!-- NIS -->
                            <!-- <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">NIS</p>
                                        <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $data['nis'] }}</p>
                                    </div>
                                </div>
                            </div> -->
                            
                            <!-- NISN -->
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">NISN</p>
                                        <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $data['nisn'] }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Jenis Kelamin -->
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Jenis Kelamin</p>
                                        <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $data['jenis_kelamin'] }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tempat, Tanggal Lahir -->
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Tempat, Tanggal Lahir</p>
                                        <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $data['tempat_lahir'] }}, {{ $data['tanggal_lahir'] }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Agama -->
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-full bg-pink-100 dark:bg-pink-900 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Agama</p>
                                        <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $data['agama'] }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Orang Tua -->
                            <!-- <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 sm:col-span-2">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-1">Orang Tua</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <p class="text-xs text-gray-400">Ayah:</p>
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $data['nama_ayah'] }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-400">Ibu:</p>
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $data['nama_ibu'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
