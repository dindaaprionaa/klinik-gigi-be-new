<?php

namespace App\Http\Controllers\API\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Pendaftaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Alur kasir terhadap pendaftaran & antrian (backend API saja).
 */
class PendaftaranFlowController extends Controller
{
    public function antrianHariIni(Request $request)
    {
        $tanggal = $request->input('tanggal', now()->toDateString());

        $q = Pendaftaran::query()
            ->with(['pasien', 'dokter', 'jadwalDokter'])
            ->whereDate('tanggal_pendaftaran', $tanggal)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->orderBy('no_antrian');

        if ($request->filled('dokter_id')) {
            $q->where('dokter_id', (int) $request->input('dokter_id'));
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 50)));

        return response()->json($q->paginate($perPage));
    }

    public function checkIn(Request $request, string $id)
    {
        $pendaftaran = Pendaftaran::find($id);

        if (! $pendaftaran) {
            return response()->json(['message' => 'Pendaftaran tidak ditemukan'], 404);
        }

        if (! in_array($pendaftaran->status, ['pending', 'confirmed'], true)) {
            return response()->json([
                'message' => 'Hanya pendaftaran berstatus menunggu atau terkonfirmasi yang dapat check-in.',
            ], 422);
        }

        $pendaftaran->update(['status' => 'checked_in']);

        return response()->json([
            'message' => 'Check-in berhasil. Nomor antrean tetap: '.$pendaftaran->no_antrian,
            'data' => $pendaftaran->fresh()->load(['pasien', 'dokter', 'jadwalDokter']),
        ]);
    }

    /**
     * Registrasi kunjungan di klinik (pasien sudah ada di master).
     */
    public function kunjunganLangsung(Request $request)
    {
        $v = Validator::make($request->all(), [
            'pasien_id' => 'required|exists:pasiens,id',
            'dokter_id' => 'required|exists:dokters,id',
            'jadwal_dokter_id' => 'required|exists:jadwal_dokters,id',
            'tanggal_pendaftaran' => 'required|date',
            'jam_kunjungan' => 'required|date_format:H:i',
            'keluhan' => 'required|string',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $pendaftaran = Pendaftaran::create(array_merge($v->validated(), [
            'status' => 'confirmed',
            'no_antrian' => $this->generateNoAntrian(),
        ]));

        return response()->json([
            'message' => 'Kunjungan terdaftar. Nomor antrean: '.$pendaftaran->no_antrian,
            'data' => $pendaftaran->load(['pasien', 'dokter', 'jadwalDokter']),
        ], 201);
    }

    private function generateNoAntrian(): string
    {
        $today = now()->format('Ymd');
        $count = Pendaftaran::whereDate('created_at', now())->count() + 1;

        return 'ANT-'.$today.'-'.str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }
}
