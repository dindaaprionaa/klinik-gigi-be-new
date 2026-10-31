<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pendaftaran extends Model
{
    use HasFactory;

    protected $table = 'pendaftarans';

    protected $fillable = [
        'pasien_id',
        'dokter_id',
        'jadwal_dokter_id',
        'tanggal_pendaftaran',
        'jam_kunjungan',
        'keluhan',
        'status',
        'no_antrian',
    ];

    protected $casts = [
        'tanggal_pendaftaran' => 'date',
    ];

    public function pasien()
    {
        return $this->belongsTo(Pasien::class);
    }

    public function dokter()
    {
        return $this->belongsTo(Dokter::class);
    }

    public function jadwalDokter()
    {
        return $this->belongsTo(JadwalDokter::class);
    }

    public function rekamMedis()
    {
        return $this->hasOne(RekamMedis::class);
    }

    public function notifikasi()
    {
        return $this->hasMany(Notifikasi::class);
    }

    public function transaksis()
    {
        return $this->hasMany(Transaksi::class);
    }
}
