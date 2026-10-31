<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Obat extends Model
{
    protected $fillable = [
        'kode',
        'nama',
        'satuan',
        'harga_jual',
        'stok',
        'stok_minimum',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'harga_jual' => 'decimal:2',
            'stok' => 'decimal:2',
            'stok_minimum' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    public function transaksiItems(): HasMany
    {
        return $this->hasMany(TransaksiItem::class);
    }
}
