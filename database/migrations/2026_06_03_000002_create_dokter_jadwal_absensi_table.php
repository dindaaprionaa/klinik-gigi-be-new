<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokter_jadwal_absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokter_id')->constrained('dokters')->onDelete('cascade');
            $table->foreignId('jadwal_dokter_id')->constrained('jadwal_dokters')->onDelete('cascade');
            $table->date('tanggal');
            $table->time('jam_check_in')->nullable();
            $table->time('jam_check_out')->nullable();
            $table->timestamps();

            $table->unique(['dokter_id', 'jadwal_dokter_id', 'tanggal'], 'dokter_jadwal_absensi_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokter_jadwal_absensi');
    }
};
