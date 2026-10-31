<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekam_medis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasien_id')->constrained('pasiens')->onDelete('cascade');
            $table->foreignId('dokter_id')->constrained('dokters')->onDelete('cascade');
            $table->foreignId('pendaftaran_id')->nullable()->constrained('pendaftarans')->onDelete('set null');
            $table->date('tanggal_kunjungan');
            $table->text('keluhan_utama');
            $table->text('diagnosis');
            $table->text('anamnesis')->nullable();
            $table->text('pemeriksaan_fisik')->nullable();
            $table->text('hasil_laboratorium')->nullable();
            $table->text('resep')->nullable();
            $table->text('tindakan')->nullable();
            $table->text('catatan_dokter')->nullable();
            $table->timestamps();

            $table->index('tanggal_kunjungan');
            $table->index('pasien_id');
            $table->index('dokter_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekam_medis');
    }
};
