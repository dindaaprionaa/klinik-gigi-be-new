<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DokterJadwalAbsensi extends Model
{
    use HasFactory;

    protected $table = 'dokter_jadwal_absensi';

    protected $fillable = [
        'dokter_id',
        'jadwal_dokter_id',
        'tanggal',
        'jam_check_in',
        'jam_check_out',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function dokter()
    {
        return $this->belongsTo(Dokter::class);
    }

    public function jadwalDokter()
    {
        return $this->belongsTo(JadwalDokter::class, 'jadwal_dokter_id');
    }
}
