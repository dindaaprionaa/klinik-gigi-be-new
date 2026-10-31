<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah default sekaligus tambah 2 nilai enum baru
        DB::statement("
            ALTER TABLE transaksis
            MODIFY COLUMN status ENUM(
                'pending_billing',
                'billed',
                'draft',
                'paid',
                'cancelled'
            ) NOT NULL DEFAULT 'pending_billing'
        ");
    }

    public function down(): void
    {
        // Pastikan tidak ada data pending_billing/billed sebelum rollback
        DB::statement("
            UPDATE transaksis
            SET status = 'draft'
            WHERE status IN ('pending_billing', 'billed')
        ");

        DB::statement("
            ALTER TABLE transaksis
            MODIFY COLUMN status ENUM(
                'draft',
                'paid',
                'cancelled'
            ) NOT NULL DEFAULT 'draft'
        ");
    }
};
