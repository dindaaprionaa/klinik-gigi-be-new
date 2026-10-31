<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::redirect('/', '/admin/dashboard');
    Route::view('/dashboard', 'admin.dashboard')->name('admin.dashboard');
    Route::view('/dokter', 'admin.dokter')->name('admin.dokter');
    Route::view('/pasien', 'admin.pasien')->name('admin.pasien');
    Route::view('/jadwal', 'admin.jadwal')->name('admin.jadwal');
    Route::view('/pendaftaran', 'admin.pendaftaran')->name('admin.pendaftaran');
    Route::view('/rekam-medis', 'admin.rekam-medis')->name('admin.rekam_medis');
    Route::view('/laporan', 'admin.laporan')->name('admin.laporan');
});

Route::middleware(['auth', 'role:pasien'])->prefix('pasien')->group(function () {
    Route::view('/profil', 'pasien.profile')->name('pasien.profile_page');
    Route::view('/daftar-berobat', 'pasien.daftar-berobat')->name('pasien.daftar_page');
    Route::view('/riwayat', 'pasien.riwayat')->name('pasien.riwayat_page');
});
