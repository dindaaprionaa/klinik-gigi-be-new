<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokters', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('no_identitas')->unique();
            $table->string('spesialisasi');
            $table->string('no_lisensi')->unique();
            $table->string('no_telepon');
            $table->string('email')->unique();
            $table->text('alamat');
            $table->time('jam_praktek_mulai');
            $table->time('jam_praktek_selesai');
            $table->string('hari_libur')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokters');
    }
};
