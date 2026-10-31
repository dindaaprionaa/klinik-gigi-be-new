<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\JadwalDokter;
use App\Models\Dokter;
use App\Models\Pendaftaran;
use App\Models\DokterJadwalAbsensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JadwalController extends Controller
{
    private const HARI_ORDER = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    private function getDokterForUser(Request $request): Dokter
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthorized');
        }

        $dokter = Dokter::where('email', $user->email)->first();

        if (!$dokter) {
            abort(404, 'Data dokter tidak ditemukan');
        }

        return $dokter;
    }

    private function assertOwnsJadwal(Dokter $dokter, JadwalDokter $jadwal): void
    {
        if ((int) $jadwal->dokter_id !== (int) $dokter->id) {
            abort(403, 'Anda tidak berhak mengubah jadwal ini');
        }
    }

    private function sortJadwalByHari($jadwal)
    {
        return $jadwal->sortBy(fn ($item) => array_search($item->hari, self::HARI_ORDER, true))->values();
    }

    private function hariIni(): string
    {
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        return $days[(int) now('Asia/Jakarta')->format('w')];
    }

    private function attachPasienDiperiksa($jadwal, Dokter $dokter)
    {
        $today = now()->toDateString();
        $hariIni = $this->hariIni();

        return $jadwal->map(function ($item) use ($dokter, $today, $hariIni) {
            $query = Pendaftaran::where('dokter_id', $dokter->id)
                ->where('jadwal_dokter_id', $item->id)
                ->where('status', 'completed');

            $item->pasien_diperiksa = $item->hari === $hariIni
                ? (clone $query)->whereDate('tanggal_pendaftaran', $today)->count()
                : $query->count();

            return $item;
        });
    }

    private function formatJamAbsensi($value): ?string
    {
        if (!$value) {
            return null;
        }

        return \Carbon\Carbon::parse($value)->format('H:i');
    }

    private function waktuKlikSekarang(): string
    {
        return now('Asia/Jakarta')->format('H:i');
    }

    private function attachAbsensiHariIni($jadwal, Dokter $dokter)
    {
        $today = now('Asia/Jakarta')->toDateString();
        $hariIni = $this->hariIni();

        $absensiMap = DokterJadwalAbsensi::where('dokter_id', $dokter->id)
            ->whereDate('tanggal', $today)
            ->get()
            ->keyBy('jadwal_dokter_id');

        return $jadwal->map(function ($item) use ($hariIni, $absensiMap) {
            $item->sudah_check_in = false;
            $item->sudah_check_out = false;
            $item->jam_check_in_hari_ini = null;
            $item->jam_check_out_hari_ini = null;

            if ($item->hari === $hariIni && $absensiMap->has($item->id)) {
                $absensi = $absensiMap[$item->id];
                $item->sudah_check_in = (bool) $absensi->jam_check_in;
                $item->sudah_check_out = (bool) $absensi->jam_check_out;
                $item->jam_check_in_hari_ini = $this->formatJamAbsensi($absensi->jam_check_in);
                $item->jam_check_out_hari_ini = $this->formatJamAbsensi($absensi->jam_check_out);
            }

            return $item;
        });
    }

    private function attachAbsensiForList($jadwals)
    {
        $today = now('Asia/Jakarta')->toDateString();
        $hariIni = $this->hariIni();

        $absensiMap = DokterJadwalAbsensi::whereDate('tanggal', $today)
            ->get()
            ->keyBy('jadwal_dokter_id');

        return $jadwals->map(function ($item) use ($hariIni, $absensiMap) {
            $item->sudah_check_in = false;
            $item->sudah_check_out = false;
            $item->jam_check_in_hari_ini = null;
            $item->jam_check_out_hari_ini = null;

            if ($item->hari === $hariIni && $absensiMap->has($item->id)) {
                $absensi = $absensiMap[$item->id];
                $item->sudah_check_in = (bool) $absensi->jam_check_in;
                $item->sudah_check_out = (bool) $absensi->jam_check_out;
                $item->jam_check_in_hari_ini = $this->formatJamAbsensi($absensi->jam_check_in);
                $item->jam_check_out_hari_ini = $this->formatJamAbsensi($absensi->jam_check_out);
            }

            return $item;
        });
    }

    public function index(Request $request)
    {
        $perPage = min(100, max(1, (int) $request->input('per_page', 15)));
        $jadwals = JadwalDokter::with('dokter')->paginate($perPage);
        // attach today's absensi info for the returned collection
        $collection = $jadwals->getCollection();
        $collection = $this->attachAbsensiForList($collection);
        $jadwals->setCollection($collection);

        return response()->json($jadwals, 200);

    }

    public function indexPublic()
    {
        $jadwals = JadwalDokter::with('dokter')
            ->where('status', true)
            ->get();

        $jadwals = $this->attachAbsensiForList($jadwals);

        return response()->json([
            'data' => $jadwals,
        ], 200);
    }

    public function store(Request $request)
    {
       $validator = Validator::make($request->all(), [
    'dokter_id' => 'required|exists:dokters,id',
    'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',

    'jam_mulai' => [
        'required',
        'date_format:H:i',
        Rule::unique('jadwal_dokters')
            ->where(function ($query) use ($request) {
                return $query->where('dokter_id', $request->dokter_id)
                             ->where('hari', $request->hari);
            }),
    ],

    'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
    'kapasitas' => 'nullable|integer|min:1',
], [
    'jam_mulai.unique' => 'Jadwal dokter pada hari dan jam tersebut sudah ada.'
]);
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $jadwal = JadwalDokter::create($request->all());

    return response()->json([
        'message' => 'Jadwal created successfully',
        'data' => $jadwal->load('dokter'),
    ], 201);
    }

    public function show(JadwalDokter $jadwal)
    {
        return response()->json([
            'data' => $jadwal->load('dokter'),
        ], 200);
    }

    public function update(Request $request, JadwalDokter $jadwal)
    {
        $validator = Validator::make($request->all(), [
            'jam_mulai' => 'sometimes|required|date_format:H:i',
            'jam_selesai' => 'sometimes|required|date_format:H:i',
            'kapasitas' => 'nullable|integer|min:1',
            'status' => 'sometimes|required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $jadwal->update($request->all());

        return response()->json([
            'message' => 'Jadwal updated successfully',
            'data' => $jadwal,
        ], 200);
    }

    public function destroy(JadwalDokter $jadwal)
    {
        $jadwal->delete();

        return response()->json([
            'message' => 'Jadwal deleted successfully',
        ], 200);
    }

    public function getByDokter($dokter_id)
    {
        $dokter = Dokter::find($dokter_id);

        if ($dokter && $dokter->sedang_istirahat) {
            return response()->json([
                'data' => [],
                'message' => 'Dokter sedang istirahat',
            ], 200);
        }

        $jadwals = JadwalDokter::where('dokter_id', $dokter_id)
            ->where('status', true)
            ->get();

        // Fallback agar dropdown jadwal tetap terisi bila data lama status-nya belum diset.
        if ($jadwals->isEmpty()) {
            $jadwals = JadwalDokter::where('dokter_id', $dokter_id)->get();
        }

        return response()->json([
            'data' => $jadwals,
        ], 200);
    }

    public function getJadwalByLogin(Request $request)
    {
        $dokter = $this->getDokterForUser($request);

        $jadwal = $this->sortJadwalByHari(
            JadwalDokter::where('dokter_id', $dokter->id)->get()
        );

        $jadwal = $this->attachPasienDiperiksa($jadwal, $dokter);
        $jadwal = $this->attachAbsensiHariIni($jadwal, $dokter);

        return response()->json([
            'dokter' => [
                'id' => $dokter->id,
                'nama' => $dokter->nama,
                'spesialisasi' => $dokter->spesialisasi,
                'no_telepon' => $dokter->no_telepon,
                'email' => $dokter->email,
                'sedang_istirahat' => (bool) $dokter->sedang_istirahat,
            ],
            'jadwal' => $jadwal,
        ], 200);
    }

    public function recordAbsensiByLogin(Request $request, JadwalDokter $jadwal)
    {
        $dokter = $this->getDokterForUser($request);
        $this->assertOwnsJadwal($dokter, $jadwal);

        if ($jadwal->hari !== $this->hariIni()) {
            return response()->json([
                'message' => 'Check in/out hanya bisa dilakukan pada hari jadwal yang sesuai.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'tipe' => 'required|in:check_in,check_out',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tipe = $request->input('tipe');
        $today = now('Asia/Jakarta')->toDateString();
        $waktu = $this->waktuKlikSekarang();

        $absensi = DokterJadwalAbsensi::firstOrCreate(
            [
                'dokter_id' => $dokter->id,
                'jadwal_dokter_id' => $jadwal->id,
                'tanggal' => $today,
            ],
            [
                'jam_check_in' => null,
                'jam_check_out' => null,
            ]
        );

        if ($tipe === 'check_in') {
            if ($absensi->jam_check_in) {
                return response()->json([
                    'message' => 'Check in hari ini sudah dicatat.',
                ], 422);
            }

            $absensi->update(['jam_check_in' => $waktu]);
            $message = "Check in berhasil dicatat pukul {$waktu}";
        } else {
            if ($absensi->jam_check_out) {
                return response()->json([
                    'message' => 'Check out hari ini sudah dicatat.',
                ], 422);
            }

            $absensi->update(['jam_check_out' => $waktu]);
            $message = "Check out berhasil dicatat pukul {$waktu}";
        }

        return response()->json([
            'message' => $message,
            'absensi' => $absensi->fresh(),
        ], 200);
    }

    public function setIstirahatByLogin(Request $request)
    {
        $dokter = $this->getDokterForUser($request);

        $validator = Validator::make($request->all(), [
            'sedang_istirahat' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sedangIstirahat = $request->boolean('sedang_istirahat');
        $dokter->update(['sedang_istirahat' => $sedangIstirahat]);

        return response()->json([
            'message' => $sedangIstirahat
                ? 'Mode istirahat diaktifkan. Pasien tidak dapat mendaftar sementara.'
                : 'Anda kembali praktik. Jadwal dapat digunakan pasien kembali.',
            'dokter' => [
                'id' => $dokter->id,
                'nama' => $dokter->nama,
                'spesialisasi' => $dokter->spesialisasi,
                'no_telepon' => $dokter->no_telepon,
                'email' => $dokter->email,
                'sedang_istirahat' => (bool) $dokter->sedang_istirahat,
            ],
        ], 200);
    }

    public function storeByLogin(Request $request)
    {
        $dokter = $this->getDokterForUser($request);

        $validator = Validator::make($request->all(), [
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'jam_mulai' => [
                'required',
                'date_format:H:i',
                Rule::unique('jadwal_dokters')->where(function ($query) use ($request, $dokter) {
                    return $query->where('dokter_id', $dokter->id)
                        ->where('hari', $request->hari);
                }),
            ],
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'kapasitas' => 'nullable|integer|min:1',
            'status' => 'sometimes|boolean',
        ], [
            'jam_mulai.unique' => 'Jadwal pada hari dan jam tersebut sudah ada.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $jadwal = JadwalDokter::create([
            'dokter_id' => $dokter->id,
            'hari' => $request->hari,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'kapasitas' => $request->input('kapasitas', 10),
            'status' => $request->boolean('status', true),
        ]);

        return response()->json([
            'message' => 'Jadwal berhasil ditambahkan',
            'data' => $jadwal,
        ], 201);
    }

    public function updateByLogin(Request $request, JadwalDokter $jadwal)
    {
        $dokter = $this->getDokterForUser($request);
        $this->assertOwnsJadwal($dokter, $jadwal);

        $validator = Validator::make($request->all(), [
            'hari' => 'sometimes|required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'jam_mulai' => [
                'sometimes',
                'required',
                'date_format:H:i',
                Rule::unique('jadwal_dokters')
                    ->where(function ($query) use ($request, $dokter, $jadwal) {
                        return $query->where('dokter_id', $dokter->id)
                            ->where('hari', $request->input('hari', $jadwal->hari));
                    })
                    ->ignore($jadwal->id),
            ],
            'jam_selesai' => 'sometimes|required|date_format:H:i',
            'kapasitas' => 'nullable|integer|min:1',
            'status' => 'sometimes|boolean',
        ], [
            'jam_mulai.unique' => 'Jadwal pada hari dan jam tersebut sudah ada.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $mulai = $request->input('jam_mulai', $jadwal->jam_mulai);
        $selesai = $request->input('jam_selesai', $jadwal->jam_selesai);

        if (strtotime($selesai) <= strtotime($mulai)) {
            return response()->json([
                'errors' => ['jam_selesai' => ['Jam selesai harus setelah jam mulai.']],
            ], 422);
        }

        $jadwal->update($request->only(['hari', 'jam_mulai', 'jam_selesai', 'kapasitas', 'status']));

        return response()->json([
            'message' => 'Jadwal berhasil diperbarui',
            'data' => $jadwal->fresh(),
        ], 200);
    }

    public function destroyByLogin(Request $request, JadwalDokter $jadwal)
    {
        $dokter = $this->getDokterForUser($request);
        $this->assertOwnsJadwal($dokter, $jadwal);

        $jadwal->delete();

        return response()->json([
            'message' => 'Jadwal berhasil dihapus',
        ], 200);
    }
}
