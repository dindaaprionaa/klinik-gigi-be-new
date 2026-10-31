<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\Dokter;
use App\Models\Pendaftaran;
use App\Models\RekamMedis;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DashboardController extends Controller
{
    public function overview()
    {
        $totalPasien = Pasien::count();
        $totalDokter = Dokter::where('status', true)->count();
        $totalPendaftaran = Pendaftaran::count();
        $pendaftaranHariIni = Pendaftaran::whereDate('tanggal_pendaftaran', now())->count();

        return response()->json([
            'totalPasien'        => $totalPasien,
            'totalDokter'        => $totalDokter,
            'totalPendaftaran'   => $totalPendaftaran,
            'pendaftaranHariIni' => $pendaftaranHariIni,
        ], 200);
    }

    public function statistikPasien()
    {
        $pasienBaru  = Pasien::whereDate('created_at', now())->count();
        $totalPasien = Pasien::count();
        $pasienAktif = Pendaftaran::where('status', '!=', 'cancelled')
            ->distinct()
            ->count('pasien_id');

        return response()->json([
            'pasienBaru'  => $pasienBaru,
            'totalPasien' => $totalPasien,
            'pasienAktif' => $pasienAktif,
        ], 200);
    }

    public function statistikDokter()
    {
        $totalDokter  = Dokter::count();
        $dokterAktif  = Dokter::where('status', true)->count();
        $spesialisasi = Dokter::select('spesialisasi')
            ->groupBy('spesialisasi')
            ->with(['jadwalDokter'])
            ->get();

        return response()->json([
            'totalDokter'  => $totalDokter,
            'dokterAktif'  => $dokterAktif,
            'spesialisasi' => $spesialisasi,
        ], 200);
    }

    public function statistikPendaftaran()
    {
        $byStatus = Pendaftaran::select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->get();

        $hariIni = Pendaftaran::whereDate('tanggal_pendaftaran', now())
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->get();

        return response()->json([
            'byStatus' => $byStatus,
            'hariIni'  => $hariIni,
        ], 200);
    }

    public function pendaftaranTerbaru()
    {
        $pendaftarans = Pendaftaran::with(['pasien', 'dokter'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json(['data' => $pendaftarans], 200);
    }

    public function aktivitasHariIni()
    {
        $start = now('Asia/Jakarta')->startOfDay()->setTimezone('UTC');
        $end   = now('Asia/Jakarta')->endOfDay()->setTimezone('UTC');

        $pendaftaranBaru = Pendaftaran::with(['pasien', 'dokter'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($p) {
                return [
                    'type'                => 'pendaftaran_baru',
                    'description'         => 'Pendaftaran baru oleh ' . ($p->pasien->nama ?? '-') .
                                             ' ke dr. ' . ($p->dokter->nama ?? '-'),
                    'tanggal_pendaftaran' => optional($p->tanggal_pendaftaran)->format('Y-m-d'),
                    'status'              => $p->status,
                    'created_at'          => $p->created_at,
                ];
            });

        $jadwalHariIni = Pendaftaran::with(['pasien', 'dokter'])
            ->whereBetween('tanggal_pendaftaran', [$start, $end])
            ->orderBy('jam_kunjungan')
            ->get()
            ->map(function ($p) {
                return [
                    'type'                => 'jadwal_periksa',
                    'description'         => 'Jadwal periksa ' . ($p->pasien->nama ?? '-') .
                                             ' ke dr. ' . ($p->dokter->nama ?? '-') .
                                             ' jam ' . Carbon::parse($p->jam_kunjungan)->format('H:i'),
                    'tanggal_pendaftaran' => optional($p->tanggal_pendaftaran)->format('Y-m-d'),
                    'status'              => $p->status,
                    'created_at'          => $p->created_at,
                ];
            });

        $rekamMedis = RekamMedis::with(['pasien', 'dokter'])
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->map(function ($r) {
                return [
                    'type'              => 'rekam_medis',
                    'description'       => 'Rekam medis pasien ' . ($r->pasien->nama ?? '-') .
                                           ' oleh dr. ' . ($r->dokter->nama ?? '-'),
                    'tanggal_kunjungan' => optional($r->tanggal_kunjungan)->format('Y-m-d'),
                    'status'            => null,
                    'created_at'        => $r->created_at,
                ];
            });

        $all = collect()
            ->merge($pendaftaranBaru)
            ->merge($jadwalHariIni)
            ->merge($rekamMedis)
            ->sortByDesc('created_at')
            ->values();

        return response()->json($all, 200);
    }

    public function admin()
    {
        $totalPasien        = Pasien::count();
        $totalDokter        = Dokter::where('status', true)->count();
        $totalPendaftaran   = Pendaftaran::count();
        $pendaftaranHariIni = Pendaftaran::whereDate('tanggal_pendaftaran', now())->count();
        $pendaftaranPending = Pendaftaran::where('status', 'pending')->count();
        $pasienBaru         = Pasien::whereDate('created_at', now())->count();
        $pasienAktif        = Pendaftaran::where('status', '!=', 'cancelled')->distinct()->count('pasien_id');
        $dokterSeluruh      = Dokter::count();
        $spesialisasi       = Dokter::select('spesialisasi', DB::raw('count(*) as total'))
            ->groupBy('spesialisasi')
            ->get();

        return response()->json([
            'totalPasien'        => $totalPasien,
            'totalDokter'        => $totalDokter,
            'totalPendaftaran'   => $totalPendaftaran,
            'pendaftaranHariIni' => $pendaftaranHariIni,
            'pendaftaranPending' => $pendaftaranPending,
            'statistikPasien'    => [
                'pasienBaruHariIni' => $pasienBaru,
                'pasienAktif'       => $pasienAktif,
                'totalPasien'       => $totalPasien,
            ],
            'statistikDokter'    => [
                'totalDokterSeluruh' => $dokterSeluruh,
                'dokterAktif'        => $totalDokter,
                'spesialisasi'       => $spesialisasi,
            ],
        ], 200);
    }

    public function pasien(Request $request)
    {
        $user   = $request->user();
        $pasien = Pasien::where('email', $user->email)->first();

        if (!$pasien) {
            return response()->json(['message' => 'Data pasien tidak ditemukan'], 404);
        }

        $pendaftaranTerbaru = Pendaftaran::where('pasien_id', $pasien->id)
            ->with(['dokter', 'jadwalDokter'])
            ->orderBy('created_at', 'desc')
            ->first();

        $totalKunjungan = RekamMedis::where('pasien_id', $pasien->id)->count();

        return response()->json([
            'pasien'             => array_merge($pasien->toArray(), [
                'photo_url' => $this->photoUrlForUser($user->id),
            ]),
            'pendaftaranTerbaru' => $pendaftaranTerbaru,
            'totalKunjungan'     => $totalKunjungan,
        ], 200);
    }

    /**
     * GET /admin/analytics
     * Kunjungan pasien 7 hari terakhir untuk line chart.
     *
     * Response: { chart: [ { hari: 'Sen', tanggal: '2025-04-17', pasien: 5 }, ... ] }
     */
    public function analytics()
    {
        $days = collect();

        for ($i = 6; $i >= 0; $i--) {
            $date  = Carbon::now()->subDays($i);
            $count = Pendaftaran::whereDate('tanggal_pendaftaran', $date->toDateString())->count();

            $days->push([
                'hari'    => $date->isoFormat('ddd'),
                'tanggal' => $date->toDateString(),
                'pasien'  => $count,
            ]);
        }

        return response()->json([
            'chart' => $days->values(),
        ]);
    }

    /**
     * GET /admin/chart-data?range=week|month|year
     * Data statistik pendaftaran untuk bar chart.
     *
     * Response: [ { name: 'Sen', pendaftaran: 5, pasien: 3 }, ... ]
     */
    public function chartData(Request $request)
    {
        $range = $request->query('range', 'month');

        $data = match ($range) {
            'week'  => $this->chartDataWeek(),
            'year'  => $this->chartDataYear(),
            default => $this->chartDataMonth(),
        };

        return response()->json($data);
    }

    /* ── private helpers ─────────────────────────────── */

    private function chartDataWeek(): array
    {
        $start = Carbon::now()->startOfWeek();
        $end   = Carbon::now()->endOfWeek();

        $rows = Pendaftaran::select(
                DB::raw('DATE(tanggal_pendaftaran) as tgl'),
                DB::raw('COUNT(*) as total_pendaftaran'),
                DB::raw('COUNT(DISTINCT pasien_id) as total_pasien')
            )
            ->whereBetween('tanggal_pendaftaran', [$start->toDateString(), $end->toDateString()])
            ->groupBy('tgl')
            ->orderBy('tgl')
            ->get()
            ->keyBy('tgl');

        $result = [];
        for ($i = 0; $i < 7; $i++) {
            $date     = $start->copy()->addDays($i);
            $key      = $date->toDateString();
            $row      = $rows->get($key);
            $result[] = [
                'name'        => $date->isoFormat('ddd'),
                'pendaftaran' => $row ? (int) $row->total_pendaftaran : 0,
                'pasien'      => $row ? (int) $row->total_pasien      : 0,
            ];
        }

        return $result;
    }

    private function chartDataMonth(): array
    {
        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();
        $weeks = (int) ceil($end->day / 7);

        $rows = Pendaftaran::select(
                DB::raw('WEEK(tanggal_pendaftaran, 1) as week_num'),
                DB::raw('COUNT(*) as total_pendaftaran'),
                DB::raw('COUNT(DISTINCT pasien_id) as total_pasien')
            )
            ->whereBetween('tanggal_pendaftaran', [$start->toDateString(), $end->toDateString()])
            ->groupBy('week_num')
            ->orderBy('week_num')
            ->get()
            ->values();

        return $rows->map(fn ($row, $i) => [
            'name'        => 'Minggu ke-' . ($i + 1),
            'pendaftaran' => (int) $row->total_pendaftaran,
            'pasien'      => (int) $row->total_pasien,
        ])->toArray() ?: $this->emptyWeeks($weeks);
    }

    private function chartDataYear(): array
    {
        $year = Carbon::now()->year;

        $rows = Pendaftaran::select(
                DB::raw('MONTH(tanggal_pendaftaran) as bulan'),
                DB::raw('COUNT(*) as total_pendaftaran'),
                DB::raw('COUNT(DISTINCT pasien_id) as total_pasien')
            )
            ->whereYear('tanggal_pendaftaran', $year)
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->keyBy('bulan');

        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3  => 'Mar', 4  => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7  => 'Jul', 8  => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $result = [];
        foreach ($monthNames as $num => $name) {
            $row      = $rows->get($num);
            $result[] = [
                'name'        => $name,
                'pendaftaran' => $row ? (int) $row->total_pendaftaran : 0,
                'pasien'      => $row ? (int) $row->total_pasien      : 0,
            ];
        }

        return $result;
    }

    private function emptyWeeks(int $n): array
    {
        return array_map(fn ($i) => [
            'name'        => "Minggu ke-$i",
            'pendaftaran' => 0,
            'pasien'      => 0,
        ], range(1, $n));
    }

    private function photoUrlForUser(int $userId): ?string
    {
        $dir = public_path('assets/profile');

        if (!File::isDirectory($dir)) {
            return null;
        }

        $matches = File::glob($dir . DIRECTORY_SEPARATOR . 'user-' . $userId . '.*');

        return $matches ? asset('assets/profile/' . basename($matches[0])) : null;
    }
}
