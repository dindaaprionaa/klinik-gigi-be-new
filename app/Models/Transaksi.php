<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaksi extends Model
{
    protected $table = 'transaksis';

    // ── Status constants ──
    public const STATUS_PENDING_BILLING = 'pending_billing'; // rekam medis sudah diisi, admin belum input tagihan
    public const STATUS_BILLED          = 'billed';          // admin sudah input tagihan, pasien belum bayar
    public const STATUS_DRAFT           = 'draft';           // pasien sudah kirim pembayaran, menunggu konfirmasi
    public const STATUS_PAID            = 'paid';            // lunas
    public const STATUS_CANCELLED       = 'cancelled';       // dibatalkan

    protected $fillable = [
        'nomor_invoice',
        'pendaftaran_id',
        'rekam_medis_id',
        'pasien_id',
        'user_id',
        'status',
        'subtotal',
        'diskon',
        'total',
        'metode_bayar',
        'paid_at',
        'bukti_transfer',
        'catatan_pasien',
        'catatan_kasir',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'diskon'   => 'decimal:2',
            'total'    => 'decimal:2',
            'paid_at'  => 'datetime',
        ];
    }

    protected $appends = ['bukti_transfer_url', 'status_label'];

    public function getBuktiTransferUrlAttribute(): ?string
    {
        return $this->bukti_transfer
            ? \Illuminate\Support\Facades\Storage::url($this->bukti_transfer)
            : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_BILLING => 'Menunggu Input Tagihan',
            self::STATUS_BILLED          => 'Tagihan Siap',
            self::STATUS_DRAFT           => 'Menunggu Konfirmasi',
            self::STATUS_PAID            => 'Lunas',
            self::STATUS_CANCELLED       => 'Dibatalkan',
            default                      => $this->status,
        };
    }

    // ── Relationships ──

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }

    public function rekamMedis(): BelongsTo
    {
        return $this->belongsTo(RekamMedis::class);
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransaksiItem::class);
    }

    // ── Scopes ──

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopePendingBilling($query)
    {
        return $query->where('status', self::STATUS_PENDING_BILLING);
    }

    public function scopeBilled($query)
    {
        return $query->where('status', self::STATUS_BILLED);
    }

    // ── Helpers ──

    public function canBeInputTagihan(): bool
    {
        return $this->status === self::STATUS_PENDING_BILLING;
    }

    public function canBePaidByPasien(): bool
    {
        return $this->status === self::STATUS_BILLED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING_BILLING,
            self::STATUS_BILLED,
            self::STATUS_DRAFT,
        ]);
    }

    public function canBeConfirmedPaid(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
