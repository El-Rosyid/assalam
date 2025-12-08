<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Info -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-blue-500 dark:text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div class="text-sm text-blue-700 dark:text-blue-200">
                    <span class="font-medium">Kelas: {{ $kelas }}</span> • 
                    <span>Periode: 
                        @php
                            $months = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                            ];
                        @endphp
                        {{ $months[$month] }} {{ $year }}
                    </span> • 
                    <span>Wali Kelas: {{ $kelasData->walikelas->nama_lengkap ?? 'Tidak ada' }}</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Total Siswa</div>
                        <div class="text-2xl font-bold text-blue-600">{{ $kelasData->siswa()->count() }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Sudah Diisi</div>
                        <div class="text-2xl font-bold text-green-600">
                            @php
                                // Gunakan kelas_id (primary key) bukan id
                                $completed = \App\Models\monthly_reports::where('data_kelas_id', $kelasData->kelas_id)
                                    ->where('month', $month)
                                    ->where('year', $year)
                                    ->whereNotNull('catatan')
                                    ->where('catatan', '!=', '')
                                    ->count();
                            @endphp
                            {{ $completed }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Progress</div>
                        <div class="text-2xl font-bold text-yellow-600">
                            @php
                                $total = $kelasData->siswa()->count();
                                $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
                            @endphp
                            {{ $percentage }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        {{ $this->table }}
    </div>
</x-filament-panels::page>