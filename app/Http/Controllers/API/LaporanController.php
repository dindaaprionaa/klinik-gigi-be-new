<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\Pendaftaran;
use App\Models\RekamMedis;
use App\Models\Transaksi;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function laporanPasien(Request $request)
    {
        $query = Pasien::withCount(['pendaftaran', 'rekamMedis'])->orderByDesc('created_at');

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_akhir')) {
            $query->whereBetween('created_at', [
                $request->tanggal_mulai,
                $request->tanggal_akhir,
            ]);
        }

        if ($request->filled('jenis_kelamin')) {
            $query->where('jenis_kelamin', $request->jenis_kelamin);
        }

        if ($request->filled('status_pernikahan')) {
            $query->where('status_pernikahan', $request->status_pernikahan);
        }

        if ($request->filled('search')) {
            $search = '%' . str_replace(['%', '_'], ['\\%', '\\_'], trim($request->search)) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', $search)
                  ->orWhere('no_identitas', 'like', $search)
                  ->orWhere('no_pendaftaran', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('no_telepon', 'like', $search);
            });
        }

        $laporan = $query->get();

        $summary = [
            'total_pasien'          => Pasien::count(),
            'pasien_baru_hari_ini'  => Pasien::whereDate('created_at', now())->count(),
            'pasien_baru_bulan_ini' => Pasien::whereMonth('created_at', now())->whereYear('created_at', now())->count(),
            'jenis_kelamin'         => Pasien::select('jenis_kelamin')
                ->selectRaw('count(*) as total')
                ->groupBy('jenis_kelamin')
                ->get(),
        ];

        return response()->json([
            'summary' => $summary,
            'total'   => $laporan->count(),
            'data'    => $laporan,
        ], 200);
    }

    public function index(Request $request)
    {
        return $this->laporanPasien($request);
    }

    public function laporanRekamMedis(Request $request)
    {
        $query = RekamMedis::with(['pasien', 'dokter']);

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_akhir')) {
            $query->whereBetween('tanggal_kunjungan', [
                $request->tanggal_mulai,
                $request->tanggal_akhir,
            ]);
        }

        if ($request->filled('dokter_id')) {
            $query->where('dokter_id', $request->dokter_id);
        }

        if ($request->filled('pasien_id')) {
            $query->where('pasien_id', $request->pasien_id);
        }

        $laporan = $query->get();

        return response()->json([
            'total' => $laporan->count(),
            'data'  => $laporan,
        ], 200);
    }

    public function laporanDokter(Request $request)
    {
        $dokters = \App\Models\Dokter::all();

        $rekamMedisQuery = RekamMedis::select('dokter_id', \Illuminate\Support\Facades\DB::raw('count(*) as jumlah_pasien'));
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_akhir')) {
            $rekamMedisQuery->whereBetween('tanggal_kunjungan', [
                $request->tanggal_mulai,
                $request->tanggal_akhir,
            ]);
        }
        $pasienMap = $rekamMedisQuery->groupBy('dokter_id')->pluck('jumlah_pasien', 'dokter_id');

        $absenRecordsQuery = \App\Models\DokterJadwalAbsensi::whereNotNull('jam_check_in')
            ->orderBy('tanggal', 'asc');
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_akhir')) {
            $absenRecordsQuery->whereBetween('tanggal', [
                $request->tanggal_mulai,
                $request->tanggal_akhir,
            ]);
        }
        $absenRecordsMap = $absenRecordsQuery->get()->groupBy('dokter_id');

        $laporan = $dokters->map(function ($dokter) use ($pasienMap, $absenRecordsMap) {
            $records = $absenRecordsMap->get($dokter->id, collect());
            $riwayat_kehadiran = $records->map(function($record) {
                $checkIn = \Carbon\Carbon::parse($record->jam_check_in)->format('H:i');
                $checkOut = $record->jam_check_out ? \Carbon\Carbon::parse($record->jam_check_out)->format('H:i') : 'Belum';
                $tanggal = \Carbon\Carbon::parse($record->tanggal)->format('d/m/Y');
                return $tanggal . ': ' . $checkIn . ' - ' . $checkOut;
            })->values()->toArray();

            return [
                'dokter_id' => $dokter->id,
                'dokter' => $dokter,
                'jumlah_pasien' => $pasienMap[$dokter->id] ?? 0,
                'kehadiran' => $records->count(),
                'riwayat_kehadiran' => $riwayat_kehadiran,
            ];
        })->filter(function($item) {
            return $item['jumlah_pasien'] > 0 || $item['kehadiran'] > 0;
        })->values();

        return response()->json([
            'total' => $laporan->count(),
            'data'  => $laporan,
        ], 200);
    }

    public function laporanPendaftaran(Request $request)
    {
        $query = Pendaftaran::select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status');

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_akhir')) {
            $query->whereBetween('tanggal_pendaftaran', [
                $request->tanggal_mulai,
                $request->tanggal_akhir,
            ]);
        }

        $laporan = $query->get();

        return response()->json([
            'data' => $laporan,
        ], 200);
    }

    /* ─── Global Export PDF Router ─── */
    public function exportPDF(Request $request)
{
    $tipe = $request->input('tipe');

    if (!$tipe) {
        return response()->json([
            'message' => 'Tipe laporan wajib diisi (pasien, rekam-medis, dokter, pendaftaran, transaksi)'
        ], 400);
    }

    switch ($tipe) {
        case 'pasien':
            return $this->exportPdfPasien($request);

        case 'rekam-medis':
            return $this->exportPdfRekamMedis($request);

        case 'dokter':
            return $this->exportPdfDokter($request);

        case 'pendaftaran':
            return $this->exportPdfPendaftaran($request);

        case 'transaksi':
            return $this->exportPdfTransaksi($request);

        default:
            return response()->json([
                'message' => 'Tipe laporan tidak valid'
            ], 400);
    }
}

    /* ─── Styles Dasar PDF ─── */
    private function getPdfStyles()
    {
        return '
            body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; line-height: 1.4; color: #333; margin: 0; padding: 0; }
            .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #334155; padding-bottom: 8px; }
            .header h1 { font-size: 18px; margin: 0 0 4px 0; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
            .header p { margin: 0; font-size: 11px; color: #64748b; }
            .section { margin-bottom: 16px; width: 100%; display: block; }
            .section h3 { font-size: 12px; margin: 0 0 6px 0; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
            .meta-table { width: 100%; margin-bottom: 12px; border: none; }
            .meta-table td { border: none; padding: 3px 0; vertical-align: top; font-size: 11px; }
            .meta-label { color: #64748b; width: 15%; font-weight: bold; }
            .meta-value { color: #1e293b; width: 85%; }
            table.data-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
            table.data-table th, table.data-table td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
            table.data-table th { background-color: #f8fafc; color: #475569; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
            table.data-table tr:nth-child(even) { background-color: #fdfdfd; }
            .text-center { text-align: center !important; }
            .text-right { text-align: right !important; }
            .badge { padding: 3px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; display: inline-block; }
            .badge-success { background-color: #dcfce7; color: #14532d; }
            .badge-danger { background-color: #fee2e2; color: #7f1d1d; }
            .badge-warning { background-color: #fef9c3; color: #713f12; }
            .badge-info { background-color: #e0f2fe; color: #0c4a6e; }
        ';
    }

    /* ─── Export PDF Pasien ─── */
    private function exportPdfPasien(Request $request)
    {
        $query = Pasien::withCount(['pendaftaran', 'rekamMedis'])->orderByDesc('created_at');

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_akhir')) {
            $query->whereBetween('created_at', [
                $request->tanggal_mulai,
                $request->tanggal_akhir,
            ]);
        }

        if ($request->filled('jenis_kelamin')) {
            $query->where('jenis_kelamin', $request->jenis_kelamin);
        }

        if ($request->filled('status_pernikahan')) {
            $query->where('status_pernikahan', $request->status_pernikahan);
        }

        if ($request->filled('search')) {
            $search = '%' . str_replace(['%', '_'], ['\\%', '\\_'], trim($request->search)) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', $search)
                  ->orWhere('no_identitas', 'like', $search)
                  ->orWhere('no_pendaftaran', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('no_telepon', 'like', $search);
            });
        }

        $data = $query->get();
        $filters = $request->only(['tanggal_mulai', 'tanggal_akhir', 'jenis_kelamin', 'status_pernikahan', 'search']);

        $html = '<!doctype html><html><head><meta charset="utf-8"><style>' . $this->getPdfStyles() . '</style></head><body>' .
                '<div class="header"><h1>Laporan Data Pasien</h1><p>Dicetak pada: ' . now()->format('d/m/Y H:i') . '</p></div>' .
                '<div class="section"><h3>Parameter & Filter</h3><table class="meta-table">' .
                '<tr><td class="meta-label">Periode:</td><td class="meta-value">' . ($filters['tanggal_mulai'] ?? '-') . ' s/d ' . ($filters['tanggal_akhir'] ?? '-') . '</td></tr>' .
                '<tr><td class="meta-label">Jenis Kelamin:</td><td class="meta-value">' . (ucfirst($filters['jenis_kelamin'] ?? 'Semua')) . '</td></tr>' .
                '<tr><td class="meta-label">Pencarian:</td><td class="meta-value">' . ($filters['search'] ?? '-') . '</td></tr>' .
                '</table></div>' .
                '<div class="section"><h3>Data Hasil Laporan</h3>' .
                '<table class="data-table"><thead><tr>' .
                '<th class="text-center" width="5%">No</th><th>No. RM / Reg</th><th>Nama Pasien</th><th>Identitas (NIK)</th>' .
                '<th>L/P</th><th>No. Telepon</th><th class="text-center">Kunjungan</th><th class="text-center">Rekam Medis</th>' .
                '</tr></thead><tbody>';

        foreach ($data as $index => $p) {
            $html .= '<tr>' .
                '<td class="text-center">' . ($index + 1) . '</td>' .
                '<td>' . ($p->no_pendaftaran ?? '-') . '</td>' .
                '<td><strong>' . $p->nama . '</strong></td>' .
                '<td>' . ($p->no_identitas ?? '-') . '</td>' .
                '<td>' . ($p->jenis_kelamin === 'L' ? 'Laki-laki' : ($p->jenis_kelamin === 'P' ? 'Perempuan' : '-')) . '</td>' .
                '<td>' . ($p->no_telepon ?? '-') . '</td>' .
                '<td class="text-center">' . ($p->pendaftaran_count ?? 0) . '</td>' .
                '<td class="text-center">' . ($p->rekam_medis_count ?? 0) . '</td>' .
                '</tr>';
        }

        $html .= '</tbody></table></div></body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        return $pdf->download('laporan_pasien_' . now()->format('Ymd_His') . '.pdf');
    }

    /* ─── Export PDF Rekam Medis ─── */
    private function exportPdfRekamMedis(Request $request)
    {
        $query = RekamMedis::with(['pasien', 'dokter']);

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_akhir')) {
            $query->whereBetween('tanggal_kunjungan', [
                $request->tanggal_mulai,
                $request->tanggal_akhir,
            ]);
        }

        if ($request->filled('dokter_id')) {
            $query->where('dokter_id', $request->dokter_id);
        }

        if ($request->filled('pasien_id')) {
            $query->where('pasien_id', $request->pasien_id);
        }

        $data = $query->orderByDesc('tanggal_kunjungan')->get();

        $html = '<!doctype html><html><head><meta charset="utf-8"><style>' . $this->getPdfStyles() . '</style></head><body>' .
                '<div class="header"><h1>Laporan Rekam Medis</h1><p>Dicetak pada: ' . now()->format('d/m/Y H:i') . '</p></div>' .
                '<div class="section"><h3>Parameter & Filter</h3><table class="meta-table">' .
                '<tr><td class="meta-label">Periode:</td><td class="meta-value">' . ($request->tanggal_mulai ?? '-') . ' s/d ' . ($request->tanggal_akhir ?? '-') . '</td></tr>' .
                '</table></div>' .
                '<div class="section"><h3>Data Hasil Laporan</h3>' .
                '<table class="data-table"><thead><tr>' .
                '<th class="text-center" width="5%">No</th><th width="12%">Tgl Kunjungan</th><th>Nama Pasien</th><th>Dokter</th>' .
                '<th>Keluhan</th><th>Diagnosa</th>' .
                '</tr></thead><tbody>';

        foreach ($data as $index => $rm) {
            $html .= '<tr>' .
                '<td class="text-center">' . ($index + 1) . '</td>' .
                '<td>' . ($rm->tanggal_kunjungan ? Carbon::parse($rm->tanggal_kunjungan)->format('d/m/Y') : '-') . '</td>' .
                '<td><strong>' . ($rm->pasien?->nama ?? '-') . '</strong></td>' .
                '<td>' . ($rm->dokter?->nama ?? '-') . '</td>' .
                '<td>' . ($rm->keluhan ?? '-') . '</td>' .
                '<td>' . ($rm->diagnosa ?? '-') . '</td>' .
                '</tr>';
        }

        $html .= '</tbody></table></div></body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        return $pdf->download('laporan_rekam_medis_' . now()->format('Ymd_His') . '.pdf');
    }

    /* ─── Export PDF Dokter ─── */
    private function exportPdfDokter(Request $request)
    {
        $dokters = \App\Models\Dokter::all();

        $rekamMedisQuery = RekamMedis::select('dokter_id', \Illuminate\Support\Facades\DB::raw('count(*) as jumlah_pasien'));
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_akhir')) {
            $rekamMedisQuery->whereBetween('tanggal_kunjungan', [
                $request->tanggal_mulai,
                $request->tanggal_akhir,
            ]);
        }
        $pasienMap = $rekamMedisQuery->groupBy('dokter_id')->pluck('jumlah_pasien', 'dokter_id');

        $absenRecordsQuery = \App\Models\DokterJadwalAbsensi::whereNotNull('jam_check_in')
            ->orderBy('tanggal', 'asc');
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_akhir')) {
            $absenRecordsQuery->whereBetween('tanggal', [
                $request->tanggal_mulai,
                $request->tanggal_akhir,
            ]);
        }
        $absenRecordsMap = $absenRecordsQuery->get()->groupBy('dokter_id');

        $data = $dokters->map(function ($dokter) use ($pasienMap, $absenRecordsMap) {
            $records = $absenRecordsMap->get($dokter->id, collect());
            $riwayat_kehadiran = $records->map(function($record) {
                $checkIn = \Carbon\Carbon::parse($record->jam_check_in)->format('H:i');
                $checkOut = $record->jam_check_out ? \Carbon\Carbon::parse($record->jam_check_out)->format('H:i') : 'Belum';
                $tanggal = \Carbon\Carbon::parse($record->tanggal)->format('d/m/Y');
                return $tanggal . ': ' . $checkIn . ' - ' . $checkOut;
            })->values()->toArray();

            return [
                'dokter' => $dokter,
                'jumlah_pasien' => $pasienMap[$dokter->id] ?? 0,
                'kehadiran' => $records->count(),
                'riwayat_kehadiran' => $riwayat_kehadiran,
            ];
        })->filter(function($item) {
            return $item['jumlah_pasien'] > 0 || $item['kehadiran'] > 0;
        })->values();

        $html = '<!doctype html><html><head><meta charset="utf-8"><style>' . $this->getPdfStyles() . '</style></head><body>' .
                '<div class="header"><h1>Laporan Kunjungan Dokter</h1><p>Dicetak pada: ' . now()->format('d/m/Y H:i') . '</p></div>' .
                '<div class="section"><h3>Parameter & Filter</h3><table class="meta-table">' .
                '<tr><td class="meta-label">Periode:</td><td class="meta-value">' . ($request->tanggal_mulai ?? '-') . ' s/d ' . ($request->tanggal_akhir ?? '-') . '</td></tr>' .
                '</table></div>' .
                '<div class="section"><h3>Data Hasil Laporan</h3>' .
                '<table class="data-table"><thead><tr>' .
                '<th class="text-center" width="5%">No</th><th>Nama Dokter</th>' .
                '<th class="text-center">Jumlah Pasien</th><th class="text-center">Kehadiran</th><th>Riwayat Kehadiran (Per Hari)</th>' .
                '</tr></thead><tbody>';

        foreach ($data as $index => $doc) {
            $riwayatHtml = implode('<br>', $doc['riwayat_kehadiran']);
            if (empty($riwayatHtml)) $riwayatHtml = '-';

            $html .= '<tr>' .
                '<td class="text-center">' . ($index + 1) . '</td>' .
                '<td><strong>' . ($doc['dokter']?->nama ?? 'Dokter ID: ' . $doc['dokter']?->id) . '</strong></td>' .
                '<td class="text-center">' . ($doc['jumlah_pasien'] ?? 0) . '</td>' .
                '<td class="text-center">' . ($doc['kehadiran'] ?? 0) . '</td>' .
                '<td>' . $riwayatHtml . '</td>' .
                '</tr>';
        }

        $html .= '</tbody></table></div></body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        return $pdf->download('laporan_dokter_' . now()->format('Ymd_His') . '.pdf');
    }

    /* ─── Export PDF Pendaftaran ─── */
    private function exportPdfPendaftaran(Request $request)
    {
        $query = Pendaftaran::select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status');

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_akhir')) {
            $query->whereBetween('tanggal_pendaftaran', [
                $request->tanggal_mulai,
                $request->tanggal_akhir,
            ]);
        }

        $data = $query->get();
        $totalPendaftaran = $data->sum('total');

        $html = '<!doctype html><html><head><meta charset="utf-8"><style>' . $this->getPdfStyles() . '</style></head><body>' .
                '<div class="header"><h1>Laporan Pendaftaran</h1><p>Dicetak pada: ' . now()->format('d/m/Y H:i') . '</p></div>' .
                '<div class="section"><h3>Parameter & Filter</h3><table class="meta-table">' .
                '<tr><td class="meta-label">Periode:</td><td class="meta-value">' . ($request->tanggal_mulai ?? '-') . ' s/d ' . ($request->tanggal_akhir ?? '-') . '</td></tr>' .
                '<tr><td class="meta-label">Total Transaksi:</td><td class="meta-value"><strong>' . $totalPendaftaran . '</strong></td></tr>' .
                '</table></div>' .
                '<div class="section"><h3>Rekapitulasi Status Pendaftaran</h3>' .
                '<table class="data-table"><thead><tr>' .
                '<th class="text-center" width="8%">No</th><th>Status Pendaftaran</th><th class="text-center" width="25%">Total Pendaftaran</th>' .
                '</tr></thead><tbody>';

        foreach ($data as $index => $item) {
            $html .= '<tr>' .
                '<td class="text-center">' . ($index + 1) . '</td>' .
                '<td><span style="text-transform:capitalize;"><strong>' . ($item->status ?? '-') . '</strong></span></td>' .
                '<td class="text-center">' . ($item->total ?? 0) . '</td>' .
                '</tr>';
        }

        $html .= '</tbody></table></div></body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        return $pdf->download('laporan_pendaftaran_' . now()->format('Ymd_His') . '.pdf');
    }

    /* ─── Export PDF Transaksi ─── */
    private function exportPdfTransaksi(Request $request)
    {
        $query = Transaksi::with([
            'pendaftaran.pasien',
            'pendaftaran.dokter',
            'pasien',
        ])->latest();

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_akhir')) {
            $query->whereBetween('created_at', [
                $request->tanggal_mulai . ' 00:00:00',
                $request->tanggal_akhir . ' 23:59:59',
            ]);
        } elseif ($request->filled('tanggal_mulai')) {
            $query->whereDate('created_at', '>=', $request->tanggal_mulai);
        } elseif ($request->filled('tanggal_akhir')) {
            $query->whereDate('created_at', '<=', $request->tanggal_akhir);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('metode_bayar')) {
            $query->where('metode_bayar', $request->metode_bayar);
        }

        if ($request->filled('search')) {
            $search = '%' . str_replace(['%', '_'], ['\\%', '\\_'], trim($request->search)) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nomor_invoice', 'like', $search)
                  ->orWhereHas('pendaftaran.pasien', fn ($q2) => $q2->where('nama', 'like', $search))
                  ->orWhereHas('pasien', fn ($q2) => $q2->where('nama', 'like', $search));
            });
        }

        $data = $query->get();
        $filters = $request->only(['tanggal_mulai', 'tanggal_akhir', 'status', 'metode_bayar', 'search']);
        $totalPendapatan = $data->where('status', Transaksi::STATUS_PAID)->sum('total');

        $statusLabel = [
            'pending_billing' => 'Menunggu Tagihan',
            'billed'          => 'Tagihan Siap',
            'draft'           => 'Draft Konfirmasi',
            'paid'            => 'Lunas',
            'cancelled'       => 'Dibatalkan',
        ];

        $html = '<!doctype html><html><head><meta charset="utf-8"><style>' . $this->getPdfStyles() . '</style></head><body>' .
                '<div class="header"><h1>Laporan Keuangan & Transaksi</h1><p>Dicetak pada: ' . now()->format('d/m/Y H:i') . '</p></div>' .
                '<div class="section"><h3>Parameter & Filter</h3><table class="meta-table">' .
                '<tr><td class="meta-label">Periode:</td><td class="meta-value">' . ($filters['tanggal_mulai'] ?? '-') . ' s/d ' . ($filters['tanggal_akhir'] ?? '-') . '</td></tr>' .
                '<tr><td class="meta-label">Total Transaksi:</td><td class="meta-value">' . $data->count() . ' data</td></tr>' .
                '<tr><td class="meta-label">Pendapatan:</td><td class="meta-value"><strong>Rp ' . number_format($totalPendapatan, 0, ',', '.') . ' (Hanya Status Lunas)</strong></td></tr>' .
                '</table></div>' .
                '<div class="section"><h3>Data Hasil Transaksi</h3>' .
                '<table class="data-table"><thead><tr>' .
                '<th class="text-center" width="4%">No</th><th>No. Invoice</th><th>Pasien</th><th>Dokter</th>' .
                '<th>Metode</th><th>Subtotal</th><th>Diskon</th><th>Total</th><th class="text-center">Status</th>' .
                '</tr></thead><tbody>';

        foreach ($data as $index => $t) {
            $namaPasien = $t->pendaftaran?->pasien?->nama ?? $t->pasien?->nama ?? '-';
            $namaDokter = $t->pendaftaran?->dokter?->nama ?? '-';
            $statusText = $statusLabel[$t->status] ?? ucfirst($t->status);

            $statusClass = 'badge-warning';
            if ($t->status === 'paid') {
                $statusClass = 'badge-success';
            } elseif ($t->status === 'cancelled') {
                $statusClass = 'badge-danger';
            }

            $html .= '<tr>' .
                '<td class="text-center">' . ($index + 1) . '</td>' .
                '<td>' . ($t->nomor_invoice ?? '-') . '</td>' .
                '<td><strong>' . $namaPasien . '</strong></td>' .
                '<td>' . $namaDokter . '</td>' .
                '<td>' . ($t->metode_bayar ? ucfirst($t->metode_bayar) : '-') . '</td>' .
                '<td>Rp ' . number_format($t->subtotal ?? 0, 0, ',', '.') . '</td>' .
                '<td>Rp ' . number_format($t->diskon ?? 0, 0, ',', '.') . '</td>' .
                '<td><strong>Rp ' . number_format($t->total ?? 0, 0, ',', '.') . '</strong></td>' .
                '<td class="text-center"><span class="badge ' . $statusClass . '">' . $statusText . '</span></td>' .
                '</tr>';
        }

        $html .= '</tbody></table></div></body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        return $pdf->download('laporan_transaksi_' . now()->format('Ymd_His') . '.pdf');
    }

    /* ─── GET /admin/laporan/keuangan ─── */
    public function keuangan(Request $request)
    {
        $query = Transaksi::with([
            'pendaftaran.pasien',
            'pendaftaran.dokter',
            'pasien',
        ])->latest();

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_akhir')) {
            $query->whereBetween('created_at', [
                $request->tanggal_mulai . ' 00:00:00',
                $request->tanggal_akhir . ' 23:59:59',
            ]);
        } elseif ($request->filled('tanggal_mulai')) {
            $query->whereDate('created_at', '>=', $request->tanggal_mulai);
        } elseif ($request->filled('tanggal_akhir')) {
            $query->whereDate('created_at', '<=', $request->tanggal_akhir);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('metode_bayar')) {
            $query->where('metode_bayar', $request->metode_bayar);
        }

        if ($request->filled('search')) {
            $search = '%' . str_replace(['%', '_'], ['\\%', '\\_'], trim($request->search)) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nomor_invoice', 'like', $search)
                  ->orWhereHas('pendaftaran.pasien', fn ($q2) => $q2->where('nama', 'like', $search))
                  ->orWhereHas('pasien', fn ($q2) => $q2->where('nama', 'like', $search));
            });
        }

        $laporan = $query->get();

        $summary = [
            'total_transaksi'   => $laporan->count(),
            'total_pendapatan'  => $laporan->where('status', Transaksi::STATUS_PAID)->sum('total'),
            'transaksi_lunas'   => $laporan->where('status', Transaksi::STATUS_PAID)->count(),
            'transaksi_pending' => $laporan->whereIn('status', [
                Transaksi::STATUS_PENDING_BILLING,
                Transaksi::STATUS_BILLED,
                Transaksi::STATUS_DRAFT,
            ])->count(),
            'distribusi_status' => $laporan
                ->groupBy('status')
                ->map(fn ($group, $status) => [
                    'status' => $status,
                    'total'  => $group->count(),
                ])
                ->values(),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'total'   => $laporan->count(),
            'data'    => $laporan->values(),
        ]);
    }
}
