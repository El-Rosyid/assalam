<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\RaportController;

Route::get('/', function () {
    $sekolah = App\Models\Sekolah::first();
    return view('custom.login', compact('sekolah'));
});

// GET /login - redirect to home with login form
Route::get('/login', function () {
    $sekolah = App\Models\Sekolah::first();
    return view('custom.login', compact('sekolah'));
})->name('login')->middleware('guest');

// Handle custom login
Route::post('/login', function (Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended('/admin');
    }

    return back()->withErrors([
        'username' => 'Username atau password salah.',
    ])->withInput($request->only('username'));
})->name('custom.login')->middleware('guest');

// Handle logout
Route::post('/logout', function (Illuminate\Http\Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->name('logout');

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
Route::get('/guru', function () {
    return view('guru');
});
Route::get('/kelas', function () {
    return view('kelas');
});

// Report Card Routes
Route::middleware('auth')->group(function () {
    // Route untuk melihat PDF inline di browser (seperti website jurnal)
    Route::get('/view-raport/{siswa}', [RaportController::class, 'viewPDFInline'])
        ->name('view.raport.inline');
    
    // (routes for raport display remain above)
});