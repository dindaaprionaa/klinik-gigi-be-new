<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendaftarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasien_id')->constrained('pasiens')->onDelete('cascade');
            $table->foreignId('dokter_id')->constrained('dokters')->onDelete('cascade');
            $table->foreignId('jadwal_dokter_id')->nullable()->constrained('jadwal_dokters')->onDelete('set null');
            $table->date('tanggal_pendaftaran');
            $table->time('jam_kunjungan');
            $table->text('keluhan');
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled'])->default('pending');
            $table->string('no_antrian')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('tanggal_pendaftaran');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendaftarans');
    }
};
