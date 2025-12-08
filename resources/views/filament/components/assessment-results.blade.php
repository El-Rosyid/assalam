@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="p-6">
    {{-- Header Information --}}
    <div class="bg-gradient-to-r from-blue-50 to-green-50 p-6 rounded-lg border border-blue-200 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $student->nama_lengkap }}</h3>
                <p class="text-sm text-gray-600">NIS: {{ $student->nis }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-700">Semester: {{ $semester }}</p>
                <p class="text-sm text-gray-600">{{ $academicYear->nama_tahun_ajaran }}</p>
            </div>
            <div>
                <span class="px-3 py-1 rounded-full text-xs font-medium
                    {{ $status === 'selesai' ? 'bg-green-100 text-green-800' : 
                       ($status === 'sebagian' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                    {{ $status === 'selesai' ? '✅ Selesai' : 
                       ($status === 'sebagian' ? '⚠️ Sebagian' : '❌ Belum Dinilai') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Assessment Results Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900">Hasil Penilaian per Kriteria</h4>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            No
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nama Penilaian
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Rating
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Gambar Kegiatan
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Deskripsi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($details as $index => $detail)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $index + 1 }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $detail->assessmentVariable->name }}
                            </div>
                            @if($detail->assessmentVariable->dekripsi)
                            <div class="text-xs text-gray-500 mt-1">
                                {{ Str::limit($detail->assessmentVariable->dekripsi, 100) }}
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($detail->rating)
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    {{ $detail->rating === 'Berkembang Sesuai Harapan' ? 'bg-green-100 text-green-800' : 
                                       ($detail->rating === 'Sudah Berkembang' ? 'bg-blue-100 text-blue-800' : 
                                        ($detail->rating === 'Mulai Berkembang' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')) }}">
                                    {{ $detail->rating === 'Berkembang Sesuai Harapan' ? '✅ BSH' : 
                                       ($detail->rating === 'Sudah Berkembang' ? '🟢 SB' : 
                                        ($detail->rating === 'Mulai Berkembang' ? '🟡 MB' : '🔴 BB')) }}
                                </span>
                                <div class="text-xs text-gray-500 mt-1">{{ $detail->rating }}</div>
                            @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                                    Belum Dinilai
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($detail->images)
                                @php
                                    $images = is_string($detail->images) ? json_decode($detail->images, true) : $detail->images;
                                @endphp
                                @if($images && count($images) > 0)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($images as $image)
                                            <div class="relative group">
                                                <img src="{{ Storage::url($image) }}" 
                                                     alt="Dokumentasi" 
                                                     class="w-16 h-16 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-75 transition-opacity">
                                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-lg transition-all duration-200 flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">{{ count($images) }} foto</div>
                                @else
                                    <div class="text-sm text-gray-400 italic">Tidak ada gambar</div>
                                @endif
                            @else
                                <div class="text-sm text-gray-400 italic">Tidak ada gambar</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($detail->description)
                                <div class="text-sm text-gray-900 max-w-xs">
                                    {!! nl2br(e(Str::limit($detail->description, 150))) !!}
                                </div>
                                @if(strlen($detail->description) > 150)
                                    <button class="text-xs text-blue-600 hover:text-blue-800 mt-1" 
                                            onclick="alert('{{ addslashes($detail->description) }}')">
                                        Baca selengkapnya...
                                    </button>
                                @endif
                            @else
                                <div class="text-sm text-gray-400 italic">Belum ada deskripsi</div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Summary --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $details->count() }}</div>
                <div class="text-sm text-blue-600">Total Kriteria</div>
            </div>
        </div>
        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ $details->whereNotNull('rating')->count() }}</div>
                <div class="text-sm text-green-600">Sudah Dinilai</div>
            </div>
        </div>
        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
            <div class="text-center">
                <div class="text-2xl font-bold text-yellow-600">{{ $details->whereNull('rating')->count() }}</div>
                <div class="text-sm text-yellow-600">Belum Dinilai</div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Script for Image Viewing --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click handler for images to show in modal/lightbox
    document.querySelectorAll('img[alt="Dokumentasi"]').forEach(function(img) {
        img.addEventListener('click', function() {
            // Simple image viewer - you can enhance this with a proper lightbox library
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="relative max-w-4xl max-h-4xl p-4">
                    <img src="${this.src}" class="max-w-full max-h-full object-contain rounded-lg">
                    <button class="absolute top-2 right-2 text-white bg-black bg-opacity-50 rounded-full w-8 h-8 flex items-center justify-center hover:bg-opacity-75" onclick="this.closest('.fixed').remove()">
                        ×
                    </button>
                </div>
            `;
            document.body.appendChild(modal);
            
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        });
    });
});
</script>