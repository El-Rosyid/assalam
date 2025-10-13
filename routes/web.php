<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/about', function () {
    return view('about');
});
Route::get('/contact', function () {
    return view('contact');
});
Route::get('/home', function () {
    return view('home');
});
Route::get('/profile', function () {
    return view('profile');
});
Route::get('/sekolah', function () {
    return view('sekolah');
});

Route::get('/siswa', function () {
    return view('siswa');
});
Route::get('/guru', function () {
    return view('guru');
});
Route::get('/kelas', function () {
    return view('kelas');
});

// Report Card Routes
Route::middleware('auth')->group(function () {
    Route::get('/download/raport/{siswa}', [App\Http\Controllers\ReportCardController::class, 'downloadRaport'])
        ->name('download.raport');
    Route::get('/preview/raport/{siswa}', [App\Http\Controllers\ReportCardController::class, 'previewRaport'])
        ->name('preview.raport');
    Route::get('/print/raport/{siswa}', [App\Http\Controllers\ReportCardController::class, 'printRaport'])
        ->name('print.raport');
});


