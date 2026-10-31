<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dokter extends Model
{
    use HasFactory;

    protected $table = 'dokters';

    protected $fillable = [
        'nama',
        'no_identitas',
        'spesialisasi',
        'no_lisensi',
        'no_telepon',
        'email',
        'alamat',
        'jam_praktek_mulai',
        'jam_praktek_selesai',
        'hari_libur',
        'status',
        'sedang_istirahat',
    ];

    protected $casts = [
        'status' => 'boolean',
        'sedang_istirahat' => 'boolean',
    ];

    public function jadwalDokter()
    {
        return $this->hasMany(JadwalDokter::class);
    }

    public function pendaftaran()
    {
        return $this->hasMany(Pendaftaran::class);
    }

    public function rekamMedis()
    {
        return $this->hasMany(RekamMedis::class);
    }

    
}
