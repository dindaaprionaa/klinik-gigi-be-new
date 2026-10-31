<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
        if (!Schema::hasColumn('transaksis', 'bukti_transfer')) {
            $table->string('bukti_transfer')->nullable()->after('metode_bayar');
        }
        if (!Schema::hasColumn('transaksis', 'catatan_pasien')) {
            $table->text('catatan_pasien')->nullable()->after('bukti_transfer');
        }
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          Schema::table('transaksis', function (Blueprint $table) {
        $table->dropColumn(['bukti_transfer', 'catatan_pasien']);
    });
    }
};
