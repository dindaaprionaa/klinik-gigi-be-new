<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obats', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('satuan', 32)->default('pcs');
            $table->decimal('harga_jual', 14, 2)->default(0);
            $table->decimal('stok', 12, 2)->default(0);
            $table->decimal('stok_minimum', 12, 2)->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('tarif_tindakans', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->decimal('harga', 14, 2)->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_invoice')->unique();
            $table->foreignId('pendaftaran_id')->constrained('pendaftarans')->onDelete('restrict');
            $table->foreignId('rekam_medis_id')->nullable()->constrained('rekam_medis')->onDelete('set null');
            $table->foreignId('pasien_id')->constrained('pasiens')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('status', 20)->default('draft');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('diskon', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('metode_bayar', 40)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('paid_at');
        });

        Schema::create('transaksi_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksis')->onDelete('cascade');
            $table->string('jenis', 20);
            $table->foreignId('obat_id')->nullable()->constrained('obats')->onDelete('set null');
            $table->foreignId('tarif_tindakan_id')->nullable()->constrained('tarif_tindakans')->onDelete('set null');
            $table->string('nama_snapshot');
            $table->decimal('qty', 12, 2);
            $table->decimal('harga_satuan', 14, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();

            $table->index('transaksi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_items');
        Schema::dropIfExists('transaksis');
        Schema::dropIfExists('tarif_tindakans');
        Schema::dropIfExists('obats');
    }
};
