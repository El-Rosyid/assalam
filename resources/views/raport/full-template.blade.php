{{-- Template lengkap dengan halaman awal --}}
@include('raport.cover-pages', [
    'siswa' => $siswa,
    'sekolah' => $sekolah,
    'kelasInfo' => $kelasInfo,
    'waliKelasInfo' => $waliKelasInfo
])

{{-- Content raport utama --}}
@include('raport.content', [
    'siswa' => $siswa,
    'sekolah' => $sekolah,
    'kelasInfo' => $kelasInfo,
    'waliKelasInfo' => $waliKelasInfo,
    'assessmentDetails' => $assessmentDetails,
    'growthRecords' => $growthRecords,
    'attendance' => $attendance
])