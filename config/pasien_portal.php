<?php

/**
 * Path relatif setelah prefix /api untuk portal pasien.
 * Samakan dengan frontend/pasien/api.js (PASIEN_API_PATHS).
 */
return [
    'paths' => [
        'dashboard' => '/pasien/dashboard',
        'profile' => '/pasien/profile',
        'profile_photo' => '/pasien/profile/photo',
        'dokters' => '/pasien/dokters',
        'jadwal_by_dokter' => '/pasien/dokter/{dokter_id}/jadwal',
        'daftar' => '/pasien/daftar',
        'appointments' => '/pasien/appointments',
        'antrian' => '/pasien/antrian',
    ],
];
