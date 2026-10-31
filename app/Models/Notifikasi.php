<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasis';

    protected $fillable = [
        'pendaftaran_id',
        'user_id',
        'judul',
        'pesan',
        'tipe',
        'status_baca',
        'tanggal_baca',
    ];

    protected $casts = [
        'status_baca' => 'boolean',
        'tanggal_baca' => 'datetime',
    ];

    public function pendaftaran()
    {
        return $this->belongsTo(Pendaftaran::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
