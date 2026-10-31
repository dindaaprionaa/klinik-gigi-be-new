<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftaran_id')->nullable()->constrained('pendaftarans')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('judul');
            $table->text('pesan');
            $table->string('tipe'); // reminder, confirmation, update, alert
            $table->boolean('status_baca')->default(false);
            $table->dateTime('tanggal_baca')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status_baca');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasis');
    }
};
