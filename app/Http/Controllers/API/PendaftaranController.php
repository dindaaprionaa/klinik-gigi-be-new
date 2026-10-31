<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pendaftaran;
use App\Models\Pasien;
use App\Models\Dokter;
use App\Models\RekamMedis;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PendaftaranController extends Controller
{
    public function index(Request $request)
    {
        $query = Pendaftaran::with(['pasien', 'dokter', 'jadwalDokter']);

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter tanggal
        if ($request->filled('tanggal_pendaftaran')) {
            $query->whereDate('tanggal_pendaftaran', $request->tanggal_pendaftaran);
        }

        // Filter pencarian nama pasien / dokter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('pasien', fn($p) => $p->where('nama', 'like', "%{$search}%"))
                  ->orWhereHas('dokter', fn($d) => $d->where('nama', 'like', "%{$search}%"))
                  ->orWhere('no_antrian', 'like', "%{$search}%");
            });
        }

        $query->orderBy('created_at', 'asc')
              ->orderBy('id', 'asc');

        $pendaftarans = $query->paginate(15);

        $pendaftarans->getCollection()->transform(function ($item) {
            $estimasi = $this->hitungEstimasiAntrian($item);
            $item->estimasi_jam_kehadiran = $estimasi['estimasi_jam_kehadiran'] ?? $item->jam_kunjungan;
            return $item;
        });

        return response()->json($pendaftarans, 200);
    }

    public function store(Request $request)
    {
        // Normalisasi format jam_kunjungan dari HH:MM:SS ke HH:MM
        if ($request->filled('jam_kunjungan')) {
            $jam = substr((string) $request->jam_kunjungan, 0, 5);
            $request->merge(['jam_kunjungan' => $jam]);
        }

        // 🟢 FIX: Gunakan after_or_equal:yesterday agar tidak gagal akibat perbedaan timezone server
        $validator = Validator::make($request->all(), [
            'pasien_id'           => 'required|exists:pasiens,id',
            'dokter_id'           => 'required|exists:dokters,id',
            'jadwal_dokter_id'    => 'required|exists:jadwal_dokters,id',
            'tanggal_pendaftaran' => 'required|date|after_or_equal:yesterday',
            'jam_kunjungan'       => 'required|date_format:H:i',
            'keluhan'             => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $dokter = Dokter::find($request->dokter_id);
        if ($dokter && $dokter->sedang_istirahat) {
            return response()->json([
                'message' => 'Dokter sedang istirahat. Silakan pilih dokter lain atau coba lagi nanti.',
            ], 422);
        }

        // 🟢 FIX: Menghubungkan ID Pasien secara otomatis jika dikirim null oleh user login
        $user = $request->user();
        $pasienId = $request->pasien_id;

        if ($user && $user->role === 'pasien') {
            $pasien = Pasien::where('email', $user->email)->first();
            if ($pasien) {
                $pasienId = $pasien->id;
            }
        }

        // Generator nomor antrean dinamis
        $pendaftaran = Pendaftaran::create([
            'pasien_id'           => $pasienId,
            'dokter_id'           => $request->dokter_id,
            'jadwal_dokter_id'    => $request->jadwal_dokter_id,
            'tanggal_pendaftaran' => $request->tanggal_pendaftaran,
            'jam_kunjungan'       => $request->jam_kunjungan,
            'keluhan'             => $request->keluhan ?: 'Konsultasi umum',
            'status'              => 'pending',
            'no_antrian'          => $this->generateNoAntrian($request->tanggal_pendaftaran),
        ]);

        return response()->json([
            'message' => 'Pendaftaran berhasil dibuat',
            'data'    => $pendaftaran->load(['pasien', 'dokter']),
        ], 201);
    }

    public function show(Pendaftaran $pendaftaran)
    {
        return response()->json([
            'data' => $pendaftaran->load(['pasien', 'dokter', 'rekamMedis']),
        ], 200);
    }

    public function update(Request $request, Pendaftaran $pendaftaran)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|in:pending,confirmed,checked_in,completed,cancelled',
            'keluhan' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pendaftaran->update($request->all());

        return response()->json([
            'message' => 'Pendaftaran updated successfully',
            'data' => $pendaftaran,
        ], 200);
    }

    public function destroy(Pendaftaran $pendaftaran)
    {
        $pendaftaran->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Pendaftaran cancelled successfully',
        ], 200);
    }

    public function getByPasien($pasien_id)
    {
        $pendaftarans = Pendaftaran::where('pasien_id', $pasien_id)
            ->with(['dokter', 'jadwalDokter'])
            ->orderBy('tanggal_pendaftaran', 'desc')
            ->paginate(10);

        return response()->json($pendaftarans, 200);
    }

    public function getByDokter($dokter_id)
    {
        $pendaftarans = Pendaftaran::where('dokter_id', $dokter_id)
            ->with(['pasien'])
            ->orderBy('tanggal_pendaftaran', 'desc')
            ->paginate(10);

        return response()->json($pendaftarans, 200);
    }

    private function generateNoAntrian($tanggalPendaftaran = null)
    {
        $targetDate = $tanggalPendaftaran ? Carbon::parse($tanggalPendaftaran) : now();
        $formattedDate = $targetDate->format('Ymd');

        $count = Pendaftaran::whereDate('tanggal_pendaftaran', $targetDate->format('Y-m-d'))->count() + 1;

        return 'ANT-' . $formattedDate . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    private function hitungEstimasiAntrian(Pendaftaran $antrian): array
    {
        $menitPerPasien = 15;
        $jamDasar = $this->parseJamKunjungan($antrian);

        $statusAktif = ['pending', 'confirmed', 'checked_in'];
        $antrianDidepan = $this->hitungAntrianDidepan($antrian, $statusAktif);

        if ($antrian->status === 'checked_in') {
            return [
                'antrian_didepan' => 0,
                'estimasi_waktu_menit' => 0,
                'estimasi_jam_kehadiran' => null,
                'estimasi_waktu_label' => 'Sudah hadir di klinik',
            ];
        }

        $labelSuffix = $antrian->status === 'pending' ? ' (menunggu konfirmasi petugas)' : '';

        if (!$jamDasar) {
            return [
                'antrian_didepan' => $antrianDidepan,
                'estimasi_waktu_menit' => $antrianDidepan * $menitPerPasien,
                'estimasi_jam_kehadiran' => null,
                'estimasi_waktu_label' => '—' . $labelSuffix,
            ];
        }

        $jamKehadiran = $jamDasar->copy()->addMinutes($antrianDidepan * $menitPerPasien);
        $jamKehadiranStr = $jamKehadiran->format('H:i');

        return [
            'antrian_didepan' => $antrianDidepan,
            'estimasi_waktu_menit' => $antrianDidepan * $menitPerPasien,
            'estimasi_jam_kehadiran' => $jamKehadiranStr,
            'estimasi_waktu_label' => $this->formatJamKehadiranLabel($jamKehadiranStr) . $labelSuffix,
        ];
    }

    private function hitungAntrianDidepan(Pendaftaran $antrian, array $statuses): int
    {
        return Pendaftaran::where('dokter_id', $antrian->dokter_id)
            ->whereDate('tanggal_pendaftaran', $antrian->tanggal_pendaftaran)
            ->whereIn('status', $statuses)
            ->where(function ($q) use ($antrian) {
                $q->where('created_at', '<', $antrian->created_at)
                    ->orWhere(function ($q2) use ($antrian) {
                        $q2->where('created_at', $antrian->created_at)
                            ->where('id', '<', $antrian->id);
                    });
            })
            ->count();
    }

    private function parseJamKunjungan(Pendaftaran $antrian): ?Carbon
    {
        $jam = $antrian->jam_kunjungan;

        if (!$jam && $antrian->jadwalDokter) {
            $jam = $antrian->jadwalDokter->jam_mulai;
        }

        if (!$jam) {
            return null;
        }

        $jam = substr((string) $jam, 0, 8);

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, strlen($jam) === 5 ? substr($jam, 0, 5) : $jam);
            } catch (\Exception $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($jam);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function formatJamKehadiranLabel(string $jam): string
    {
        return 'Datang pukul ' . $jam . ' WIB';
    }

    public function verifikasi(Request $request, $id)
    {
        $pendaftaran = Pendaftaran::find($id);

        if (!$pendaftaran) {
            return response()->json(['message' => 'Pendaftaran tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:confirmed,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pendaftaran->update([
            'status' => $request->status === 'confirmed' ? 'confirmed' : 'cancelled',
        ]);

        return response()->json([
            'message' => 'Pendaftaran ' . $request->status . ' successfully',
            'data' => $pendaftaran,
        ], 200);
    }

    public function riwayat(Request $request)
    {
        $user = $request->user();
        $pasien = Pasien::where('email', $user->email)->first();

        if (!$pasien) {
            return response()->json(['message' => 'Data pasien tidak ditemukan'], 404);
        }

        $riwayatPendaftaran = Pendaftaran::where('pasien_id', $pasien->id)
            ->with(['dokter', 'jadwalDokter', 'rekamMedis'])
            ->orderBy('tanggal_pendaftaran', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($riwayatPendaftaran as $antrian) {
            foreach ($this->hitungEstimasiAntrian($antrian) as $key => $value) {
                $antrian->setAttribute($key, $value);
            }
        }

        $riwayatIds = collect($riwayatPendaftaran)->pluck('id')->toArray();

        $standaloneRekamMedis = RekamMedis::where('pasien_id', $pasien->id)
            ->where(function($q) use ($riwayatIds) {
                $q->whereNull('pendaftaran_id')
                  ->orWhereNotIn('pendaftaran_id', $riwayatIds);
            })
            ->with(['dokter'])
            ->get();

        $riwayatArray = $riwayatPendaftaran->toArray();

        $standaloneMapped = $standaloneRekamMedis->map(function ($rm) {
            return [
                'id' => 'rm_' . $rm->id,
                'tanggal_pendaftaran' => $rm->tanggal_kunjungan ? $rm->tanggal_kunjungan->format('Y-m-d') : null,
                'jam_kunjungan' => '00:00:00',
                'keluhan' => $rm->keluhan_utama,
                'status' => 'completed',
                'no_antrian' => '-',
                'dokter' => $rm->dokter ? $rm->dokter->toArray() : null,
                'rekam_medis' => $rm->toArray(),
                'estimasi_jam_kehadiran' => '00:00',
            ];
        })->toArray();

        $riwayat = collect($riwayatArray)->concat($standaloneMapped)
            ->sortByDesc('tanggal_pendaftaran')
            ->values()
            ->all();

        return response()->json(['data' => $riwayat], 200);
    }

    public function antrian(Request $request)
    {
        $user = $request->user();
        $pasien = Pasien::where('email', $user->email)->first();

        if (!$pasien) {
            return response()->json(['message' => 'Data pasien tidak ditemukan'], 404);
        }

        $antrian = Pendaftaran::where('pasien_id', $pasien->id)
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->with(['dokter', 'jadwalDokter'])
            ->orderBy('tanggal_pendaftaran', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$antrian) {
            return response()->json(['message' => 'Tidak ada antrian'], 404);
        }

        foreach ($this->hitungEstimasiAntrian($antrian) as $key => $value) {
            $antrian->setAttribute($key, $value);
        }

        $tanggalDaftar = $antrian->getRawOriginal('tanggal_pendaftaran');

        return response()->json([
            'data' => array_merge($antrian->toArray(), [
                'tanggal_pendaftaran' => $tanggalDaftar,
            ]),
        ], 200);
    }

    public function antrianDokter(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $dokter = Dokter::whereRaw('LOWER(email) = ?', [strtolower($user->email)])->first();

        if (!$dokter) {
            return response()->json([
                'message' => 'Dokter tidak ditemukan. Pastikan email akun login Anda sama dengan email di data dokter.',
                'debug_info' => [
                    'email_user_login' => $user->email,
                ]
            ], 404);
        }

        $antrian = Pendaftaran::where('dokter_id', $dokter->id)
            ->with(['pasien', 'jadwalDokter'])
            ->orderBy('tanggal_pendaftaran', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($antrian as $item) {
            try {
                foreach ($this->hitungEstimasiAntrian($item) as $key => $value) {
                    $item->setAttribute($key, $value);
                }
            } catch (\Exception $e) {
                $item->setAttribute('antrian_didepan', 0);
                $item->setAttribute('estimasi_waktu_label', '—');
            }
        }

        return response()->json([
            'data' => $antrian
        ], 200);
    }

    public function updateStatusAntrian(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,checked_in,completed,cancelled'
        ]);

        $pendaftaran = Pendaftaran::find($id);

        if (!$pendaftaran) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $pendaftaran->update([
            'status' => $request->status
        ]);

        return response()->json([
            'message' => 'Status berhasil diupdate',
            'data' => $pendaftaran
        ]);
    }
}
