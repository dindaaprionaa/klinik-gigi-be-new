<?php

namespace App\Http\Controllers\API\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Pendaftaran;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanKasirController extends Controller
{
    /**
     * Ringkasan keuangan dari transaksi lunas.
     */
    public function keuangan(Request $request)
    {
        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->toDateString());

        $base = Transaksi::paid()->whereBetween(DB::raw('DATE(paid_at)'), [$dari, $sampai]);

        $perMetode = (clone $base)
            ->select('metode_bayar', DB::raw('COUNT(*) as jumlah'), DB::raw('SUM(total) as total_rp'))
            ->groupBy('metode_bayar')
            ->get();

        return response()->json([
            'periode' => ['dari' => $dari, 'sampai' => $sampai],
            'total_transaksi' => (clone $base)->count(),
            'pendapatan' => (float) (clone $base)->sum('total'),
            'per_metode_bayar' => $perMetode,
        ]);
    }

    /**
     * Indikator operasional klinik (kunjungan & status pendaftaran).
     */
    public function operasional(Request $request)
    {
        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->toDateString());

        $kunjungan = Pendaftaran::query()
            ->whereBetween(DB::raw('DATE(tanggal_pendaftaran)'), [$dari, $sampai])
            ->select('status', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('status')
            ->get();

        $billing = Transaksi::query()
            ->whereBetween(DB::raw('DATE(created_at)'), [$dari, $sampai])
            ->select('status', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('status')
            ->get();

        return response()->json([
            'periode' => ['dari' => $dari, 'sampai' => $sampai],
            'pendaftaran_per_status' => $kunjungan,
            'transaksi_per_status' => $billing,
        ]);
    }
}
