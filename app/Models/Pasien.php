<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pasien extends Model
{
    use HasFactory;

    protected $table = 'pasiens';

    protected $fillable = [
        'no_pendaftaran',
        'nama',
        'no_identitas',
        'jenis_kelamin',
        'tanggal_lahir',
        'alamat',
        'no_telepon',
        'email',
        'status_pernikahan',
        'pekerjaan',
        'agama',
        'berat_badan',
        'tinggi_badan',
        'golongan_darah',
        'alergi',
        'riwayat_penyakit',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function pendaftaran()
    {
        return $this->hasMany(Pendaftaran::class);
    }

    public function rekamMedis()
    {
        return $this->hasMany(RekamMedis::class);
    }
}