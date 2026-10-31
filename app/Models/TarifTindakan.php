<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TarifTindakan extends Model
{
    protected $fillable = [
        'kode',
        'nama',
        'harga',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    public function transaksiItems(): HasMany
    {
        return $this->hasMany(TransaksiItem::class);
    }
}
