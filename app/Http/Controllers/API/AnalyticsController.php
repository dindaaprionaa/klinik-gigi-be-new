<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Dokter;
use App\Models\Pendaftaran;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function index()
    {
        Carbon::setLocale('id');

        $today = Carbon::today();

        // TOTAL PASIEN
        $totalPasien = User::where('role', 'pasien')->count();

        // PENDAFTARAN HARI INI
        $pendaftaranHariIni = Pendaftaran::whereDate('tanggal_pendaftaran', $today)->count();

        // DOKTER AKTIF
        $dokterAktif = Dokter::where('status', 1)->count();

        // ✅ ANTRIAN (yang belum selesai)
        $antrian = Pendaftaran::whereDate('tanggal_pendaftaran', $today)
            ->whereIn('status', ['menunggu', 'diproses']) // sesuaikan dengan DB kamu
            ->count();

        // ✅ GRAFIK 7 HARI TERAKHIR
        $kunjungan = Pendaftaran::select(
                DB::raw('DATE(tanggal_pendaftaran) as tanggal'),
                DB::raw('COUNT(*) as total')
            )
            ->whereDate('tanggal_pendaftaran', '>=', Carbon::today()->subDays(6))
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $dataChart = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');

            $found = $kunjungan->firstWhere('tanggal', $date);

            $dataChart[] = [
                'hari' => Carbon::parse($date)->translatedFormat('l'),
                'pasien' => $found ? (int)$found->total : 0,
            ];
        }

        return response()->json([
            'summary' => [
                'total_pasien' => $totalPasien,
                'pendaftaran_hari_ini' => $pendaftaranHariIni,
                'dokter_aktif' => $dokterAktif,
                'antrian' => $antrian,
            ],
            'chart' => $dataChart,
        ]);
    }
}