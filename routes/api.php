<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PasienController;
use App\Http\Controllers\API\DokterController;
use App\Http\Controllers\API\JadwalController;
use App\Http\Controllers\API\PendaftaranController;
use App\Http\Controllers\API\RekamMedisController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\LaporanController;
use App\Http\Controllers\API\KasirTransaksiController;
use App\Http\Controllers\API\Kasir\ObatController as KasirObatController;
use App\Http\Controllers\API\Kasir\PendaftaranFlowController as KasirPendaftaranFlowController;
use App\Http\Controllers\API\Kasir\TarifTindakanController as KasirTarifTindakanController;
use App\Http\Controllers\API\Kasir\LaporanKasirController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH (PUBLIC)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::get('/public/dokter', [DokterController::class, 'indexForPasien']);
Route::get('/public/jadwal', [JadwalController::class, 'indexPublic']);

/*
|--------------------------------------------------------------------------
| PROTECTED (LOGIN REQUIRED)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    /*
    |----------------------------------------------------------------------
    | AUTH USER
    |----------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::get('/me',                  [AuthController::class, 'me']);
        Route::get('/profile',             [AuthController::class, 'profile']);
        Route::put('/profile',             [AuthController::class, 'updateProfile']);
        Route::post('/profile/photo',      [AuthController::class, 'updatePhoto']);
        Route::post('/logout',             [AuthController::class, 'logout']);
        Route::post('/change-password',    [AuthController::class, 'changePassword']);
    });

    /*
    |----------------------------------------------------------------------
    | ADMIN
    |----------------------------------------------------------------------
    */
    /*
    |----------------------------------------------------------------------
    | ADMIN
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin')->prefix('admin')->group(function () {

        // Dashboard
        Route::get('/dashboard',              [DashboardController::class, 'admin']);
        Route::get('/recent-activities',      [DashboardController::class, 'recentActivities']);
        Route::get('/chart-data',             [DashboardController::class, 'chartData']);
        Route::get('/aktivitas-hari-ini',     [DashboardController::class, 'aktivitasHariIni']);
        Route::get('/analytics',              [DashboardController::class, 'analytics']);

        // 1. Laporan (Kelompokkan di paling atas agar tidak tertabrak)
        Route::prefix('laporan')->group(function () {
        Route::get('/pasien',         [LaporanController::class, 'laporanPasien']);
        Route::get('/rekam-medis',    [LaporanController::class, 'laporanRekamMedis']);
        Route::get('/pendaftaran',    [LaporanController::class, 'laporanPendaftaran']);
        Route::get('/dokter',         [LaporanController::class, 'laporanDokter']);
        Route::get('/transaksi',      [LaporanController::class, 'keuangan']);

        // ✅ SATU EXPORT SAJA (BEST PRACTICE)
        Route::get('/export/pdf',     [LaporanController::class, 'exportPDF']);
    });

        // 2. Master Data
        Route::apiResource('/dokter',         DokterController::class);
        Route::apiResource('/pasien',         PasienController::class);
        Route::apiResource('/jadwal',         JadwalController::class);
        Route::apiResource('/obat',           KasirObatController::class);
        Route::apiResource('/tarif-tindakan', KasirTarifTindakanController::class);

        // 3. Pendaftaran (Rute spesifik diletakkan SEBELUM apiResource)
        Route::get('/pendaftaran/antrian-hari-ini',          [KasirPendaftaranFlowController::class, 'antrianHariIni']);
        Route::get('/pendaftaran/pasien/{pasien_id}',        [PendaftaranController::class, 'getByPasien']);
        Route::get('/pendaftaran/dokter/{dokter_id}',        [PendaftaranController::class, 'getByDokter']);
        Route::post('/kunjungan-langsung',                   [KasirPendaftaranFlowController::class, 'kunjunganLangsung']);

        // Baru masukkan API Resource & rute dengan parameter ID
        Route::apiResource('/pendaftaran',    PendaftaranController::class);
        Route::post('/pendaftaran/{id}/verifikasi',          [PendaftaranController::class, 'verifikasi']);
        Route::post('/pendaftaran/{id}/check-in',            [KasirPendaftaranFlowController::class, 'checkIn']);

        // 4. Rekam medis
        Route::apiResource('/rekam-medis',    RekamMedisController::class);

        // 5. Transaksi
        Route::get('/transaksi',                             [KasirTransaksiController::class, 'index']);
        Route::get('/transaksi/{transaksi}',                  [KasirTransaksiController::class, 'show']);
        Route::get('/transaksi/{transaksi}/invoice',          [KasirTransaksiController::class, 'invoice']);
        Route::post('/transaksi/{transaksi}/input-tagihan',   [KasirTransaksiController::class, 'inputTagihan']);
        Route::post('/transaksi/{transaksi}/bayar',           [KasirTransaksiController::class, 'bayar']);
        Route::post('/transaksi/{transaksi}/batal',           [KasirTransaksiController::class, 'batal']);
        Route::post('/transaksi/{transaksi}/ubah-status',     [KasirTransaksiController::class, 'ubahStatus']);
    });

    /*
    |----------------------------------------------------------------------
    | PASIEN
    |----------------------------------------------------------------------
    */
    Route::middleware('role:pasien')->prefix('pasien')->group(function () {

        // Profile
        Route::get('/profile',              [PasienController::class, 'profile']);
        Route::put('/profile',              [PasienController::class, 'updateProfile']);
        Route::post('/profile/photo',       [PasienController::class, 'updatePhoto']);

        // Dashboard
        Route::get('/dashboard',            [DashboardController::class, 'pasien']);

        // Dokter & jadwal
        Route::get('/dokters',              [DokterController::class, 'indexForPasien']);
        Route::get('/dokter/{dokter_id}/jadwal', [JadwalController::class, 'getByDokter']);

        // Pendaftaran
        Route::post('/daftar',              [PendaftaranController::class, 'store']);
        Route::get('/appointments',         [PendaftaranController::class, 'riwayat']);
        Route::get('/history',              [PendaftaranController::class, 'riwayat']); // alias
        Route::get('/antrian',              [PendaftaranController::class, 'antrian']);

        // ── Transaksi ──────────────────────────────────────────────────
        // Pasien hanya bisa: lihat daftar, lihat detail, kirim pembayaran
        // Pasien TIDAK bisa input-tagihan, bayar (konfirmasi), atau batal
        Route::get('/transaksi',            [KasirTransaksiController::class, 'indexPasien']); // ← method terpisah
        Route::get('/transaksi/{transaksi}', [KasirTransaksiController::class, 'showPasien']); // ← method terpisah
        Route::post('/transaksi',           [KasirTransaksiController::class, 'store']);        // kirim pembayaran
        Route::post('/transaksi/{transaksi}/ubah-status', [KasirTransaksiController::class, 'ubahStatus']);
        // ──────────────────────────────────────────────────────────────
    });

    /*
    |----------------------------------------------------------------------
    | DOKTER
    |----------------------------------------------------------------------
    */
    Route::middleware('role:dokter')->prefix('dokter')->group(function () {

        // Profile
        Route::get('/profile',              [DokterController::class, 'profile']);
        Route::put('/profile',              [DokterController::class, 'updateProfile']);
        Route::post('/password',            [DokterController::class, 'updatePassword']);

        // Dashboard & stats
        Route::get('/dashboard',            [DashboardController::class, 'overview']);
        Route::get('/stats',                [DokterController::class, 'statsDokter']);
        Route::get('/aktivitas-hari-ini',   [DokterController::class, 'aktivitasHariIni']);

        // Pasien & antrian
        Route::get('/pasien',               [PendaftaranController::class, 'getByDokter']);
        Route::get('/antrian',              [PendaftaranController::class, 'antrianDokter']);
        Route::put('/antrian/{id}',         [PendaftaranController::class, 'updateStatusAntrian']);
        Route::get('/pendaftaran/dokter/{dokter_id}', [PendaftaranController::class, 'getByDokter']);

        // Rekam medis — dokter simpan → otomatis buat transaksi pending_billing
        Route::post('/rekam-medis',         [RekamMedisController::class, 'storeFromDokter']);
        Route::get('/rekam-medis',          [RekamMedisController::class, 'getByDokterAuth']);
        Route::put('/rekam-medis/{id}',         [RekamMedisController::class, 'updateFromDokter']);
        Route::get('/rekam-medis/pendaftaran/{pendaftaran_id}', [RekamMedisController::class, 'showByPendaftaran']);
        Route::get('/riwayat',              [RekamMedisController::class, 'riwayatDokter']);

        // Jadwal — dokter kelola jadwal praktik sendiri
        Route::get('/jadwal',               [JadwalController::class, 'getJadwalByLogin']);
        Route::post('/jadwal',              [JadwalController::class, 'storeByLogin']);
        Route::post('/jadwal/{jadwal}/absensi', [JadwalController::class, 'recordAbsensiByLogin']);
        Route::put('/jadwal/{jadwal}',      [JadwalController::class, 'updateByLogin']);
        Route::delete('/jadwal/{jadwal}',   [JadwalController::class, 'destroyByLogin']);
        Route::put('/istirahat',            [JadwalController::class, 'setIstirahatByLogin']);
    });
});
