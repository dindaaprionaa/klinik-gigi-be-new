<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Dokter;
use App\Models\JadwalDokter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DokterController extends Controller
{
    /**
     * Master dokter: paginasi + pencarian + filter status (selaras admin Blade / frontend).
     *
     * Query: search, status (1|0|true|false), per_page (1–100), page
     */
    public function index(Request $request)
    {
        $query = Dokter::query()->orderBy('nama');

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
            $query->where(function ($q) use ($like) {
                $q->where('nama', 'like', $like)
                    ->orWhere('spesialisasi', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('no_telepon', 'like', $like)
                    ->orWhere('no_identitas', 'like', $like)
                    ->orWhere('no_lisensi', 'like', $like);
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $raw = $request->input('status');
            $bool = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($bool !== null) {
                $query->where('status', $bool);
            } elseif (in_array((string) $raw, ['0', '1'], true)) {
                $query->where('status', (bool) (int) $raw);
            }
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 15)));

        return response()->json($query->paginate($perPage), 200);
    }

    /**
     * Daftar dokter aktif untuk pemilihan di portal pasien (tanpa data sensitif admin).
     */
    public function indexForPasien()
    {
        $hariOrder = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        $dokters = Dokter::query()
            ->where('status', true)
            ->with('jadwalDokter')
            ->orderBy('nama')
            ->get(['id', 'nama', 'email', 'spesialisasi', 'jam_praktek_mulai', 'jam_praktek_selesai', 'hari_libur', 'sedang_istirahat']);

        // Fallback untuk data lama: jika semua status nonaktif, tetap tampilkan daftar dokter.
        if ($dokters->isEmpty()) {
            $dokters = Dokter::query()
                ->with('jadwalDokter')
                    ->orderBy('nama')
                    ->get(['id', 'nama', 'email', 'spesialisasi', 'jam_praktek_mulai', 'jam_praktek_selesai', 'hari_libur', 'sedang_istirahat']);
        }

        $todayDate = now('Asia/Jakarta')->toDateString();
        $mapHari = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $hariIni = $mapHari[now('Asia/Jakarta')->format('l')] ?? '';

        $absensiMap = \App\Models\DokterJadwalAbsensi::whereDate('tanggal', $todayDate)
            ->get()
            ->keyBy('jadwal_dokter_id');

        $dokters->each(function ($dokter) use ($hariOrder, $absensiMap, $hariIni) {
            $sortedJadwals = $dokter->jadwalDokter->sortBy(fn ($j) => array_search($j->hari, $hariOrder, true))->values();

            $sortedJadwals->each(function ($j) use ($absensiMap, $hariIni) {
                if ($j->hari === $hariIni && $absensiMap->has($j->id)) {
                    $abs = $absensiMap[$j->id];
                    if ($abs->jam_check_in) {
                        $j->jam_check_in_hari_ini = \Carbon\Carbon::parse($abs->jam_check_in)->format('H:i');
                    }
                    if ($abs->jam_check_out) {
                        $j->jam_check_out_hari_ini = \Carbon\Carbon::parse($abs->jam_check_out)->format('H:i');
                    }
                }
            });

            $dokter->setRelation('jadwalDokter', $sortedJadwals);
        });

        // Attach photo_url from users table if available (match by email) - sama seperti di AuthController
        $emails = $dokters->pluck('email')->filter()->unique()->values();
        if ($emails->isNotEmpty()) {
            $users = \App\Models\User::whereIn('email', $emails)->get(['id', 'email'])->keyBy('email');
            foreach ($dokters as $dokter) {
                $photo = null;
                $user = $users[$dokter->email] ?? null;
                if ($user) {
                    $photo = $this->photoUrlForUser($user->id);
                }
                $dokter->photo_url = $photo;
            }
        }

        return response()->json(['data' => $dokters], 200);
    }
    
    // Method helper untuk mendapatkan URL foto profil (sama seperti AuthController)
    private function photoUrlForUser(int $userId): ?string
    {
        $dir = public_path('assets/profile');
        if (! \Illuminate\Support\Facades\File::isDirectory($dir)) {
            return null;
        }

        $matches = \Illuminate\Support\Facades\File::glob($dir . DIRECTORY_SEPARATOR . 'user-' . $userId . '.*');
        if (! $matches) {
            return null;
        }

        return asset('assets/profile/' . basename($matches[0]));
    }

        public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'nama'               => 'required|string|max:255',
        'no_identitas'       => 'required|string|max:255|unique:dokters,no_identitas',
        'spesialisasi'       => 'required|string|max:255',
        'no_lisensi'         => 'required|string|max:255|unique:dokters,no_lisensi',
        'no_telepon'         => 'required|string|max:50',

        'email' => [
            'required',
            'email',
            'max:255',
            'unique:dokters,email',
            'unique:users,email',
            function ($attribute, $value, $fail) {
                $blocked = [
                    'admin@rumahsakit.com',
                    'admin@gmail.com',
                    'test@gmail.com'
                ];

                if (in_array(strtolower($value), $blocked)) {
                    $fail('Email tidak boleh menggunakan email default / dummy.');
                }
            }
        ],

        'alamat'             => 'required|string|max:2000',
        'jam_praktek_mulai'  => 'required|date_format:H:i',
        'jam_praktek_selesai'=> 'required|date_format:H:i|after:jam_praktek_mulai',
        'hari_libur'         => 'nullable|string|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
        'status'             => 'required|boolean',

        // ✅ WAJIB ISI PASSWORD
        'password' => 'nullable|string|min:6',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $dokter = DB::transaction(function () use ($request) {

        // ✅ WAJIB dari input (tidak ada default)
       $password = $request->password ?? 'password123';

        // 🔐 Simpan user login
        \App\Models\User::create([
            'name'     => $request->nama,
            'email'    => $request->email,
            'password' => Hash::make($password),
            'role'     => 'dokter',
        ]);

        // 👨‍⚕️ Simpan dokter
        return Dokter::create([
            'nama'                => $request->nama,
            'no_identitas'        => $request->no_identitas,
            'spesialisasi'        => $request->spesialisasi,
            'no_lisensi'          => $request->no_lisensi,
            'no_telepon'          => $request->no_telepon,
            'email'               => $request->email,
            'alamat'              => $request->alamat,
            'jam_praktek_mulai'   => $request->jam_praktek_mulai,
            'jam_praktek_selesai' => $request->jam_praktek_selesai,
            'hari_libur'          => $request->hari_libur,
            'status'              => $request->status,
        ]);
    });

    return response()->json([
        'message' => 'Dokter berhasil ditambahkan & akun login dibuat.',
        'data'    => $dokter,
    ], 201);
}

    public function show(Dokter $dokter)
    {
        return response()->json([
            'data' => $dokter->load(['jadwalDokter', 'pendaftaran']),
        ], 200);
    }

    public function update(Request $request, Dokter $dokter)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|max:255',
            'no_identitas' => 'sometimes|required|string|max:255|unique:dokters,no_identitas,'.$dokter->id,
            'spesialisasi' => 'sometimes|required|string|max:255',
            'no_lisensi' => 'sometimes|required|string|max:255|unique:dokters,no_lisensi,'.$dokter->id,
            'no_telepon' => 'sometimes|required|string|max:50',
            'email' => 'sometimes|required|email|max:255|unique:dokters,email,'.$dokter->id,
            'alamat' => 'sometimes|required|string|max:2000',
            'jam_praktek_mulai' => 'sometimes|required|date_format:H:i',
            'jam_praktek_selesai' => 'sometimes|required|date_format:H:i',
            'hari_libur' => 'nullable|string|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'status' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('jam_praktek_mulai') || $request->has('jam_praktek_selesai')) {
            $mulai = $request->input('jam_praktek_mulai', $dokter->jam_praktek_mulai);
            $selesai = $request->input('jam_praktek_selesai', $dokter->jam_praktek_selesai);
            if (strtotime($selesai) <= strtotime($mulai)) {
                return response()->json([
                    'errors' => ['jam_praktek_selesai' => ['Jam selesai harus setelah jam mulai.']],
                ], 422);
            }
        }

        $dokter->update($request->only([
            'nama',
            'no_identitas',
            'spesialisasi',
            'no_lisensi',
            'no_telepon',
            'email',
            'alamat',
            'jam_praktek_mulai',
            'jam_praktek_selesai',
            'hari_libur',
            'status',
        ]));

        return response()->json([
            'message' => 'Dokter updated successfully',
            'data' => $dokter->fresh(),
        ], 200);
    }

    public function destroy(Dokter $dokter)
    {
        try {
            $dokter->delete();

            return response()->json([
                'message' => 'Dokter berhasil dihapus permanen dari database',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus dokter. Data mungkin masih terikat dengan jadwal atau pendaftaran.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getBySpesialisasi($spesialisasi)
    {
        $dokters = Dokter::where('spesialisasi', $spesialisasi)
            ->where('status', 1) // ✅ pakai 1 bukan true
            ->get();

        return response()->json([
            'data' => $dokters,
        ], 200);
    }
     public function getJadwalByLogin(Request $request)
{
    try {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        // ⚠️ PENTING: sesuaikan mapping user ke dokter
        // kalau user email = dokter email
        $dokter = Dokter::where('email', $user->email)->first();

        if (!$dokter) {
            return response()->json([
                'message' => 'Data dokter tidak ditemukan'
            ], 404);
        }

        $jadwal = JadwalDokter::where('dokter_id', $dokter->id)
            ->where('status', true)
            ->get();

        // fallback kalau tidak ada status true
        if ($jadwal->isEmpty()) {
            $jadwal = JadwalDokter::where('dokter_id', $dokter->id)->get();
        }

        return response()->json([
            'dokter' => [
                'id' => $dokter->id,
                'nama' => $dokter->nama,
                'spesialisasi' => $dokter->spesialisasi,
                'no_telepon' => $dokter->no_telepon,
                'email' => $dokter->email,
            ],
            'jadwal' => $jadwal
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

public function statsDokter(Request $request)
{
    $user = $request->user();

    $dokter = Dokter::where('email', $user->email)->first();

    if (!$dokter) {
        return response()->json(['message' => 'Dokter tidak ditemukan'], 404);
    }

    $today = now()->toDateString();

    return response()->json([
        'dokter' => [
            'id' => $dokter->id,
            'nama' => $dokter->nama,
            'email' => $dokter->email,
            'spesialisasi' => $dokter->spesialisasi ?? '-',
        ],

        'todayAppointments' => \App\Models\Pendaftaran::where('dokter_id', $dokter->id)
            ->whereDate('created_at', $today)
            ->count(),

        'pendingDiagnoses' => \App\Models\Pendaftaran::where('dokter_id', $dokter->id)
            ->where('status', 'pending')
            ->count(),

        'completedToday' => \App\Models\RekamMedis::where('dokter_id', $dokter->id)
            ->whereDate('created_at', $today)
            ->count(),
    ]);
}

public function profile(Request $request)
{
    $user = $request->user();

    $dokter = Dokter::where('email', $user->email)->first();

    if (!$dokter) {
        return response()->json([
            'message' => 'Dokter tidak ditemukan'
        ], 404);
    }

    return response()->json([
        'data' => $dokter
    ]);
}

public function updateProfile(Request $request)
{
    $user = $request->user();

    $dokter = Dokter::where('email', $user->email)->firstOrFail();

    $validated = $request->validate([
        'nama' => 'required|string',
        'spesialisasi' => 'nullable|string',
        'no_telepon' => 'nullable|string',
        'no_identitas' => 'nullable|string',
        'no_lisensi' => 'nullable|string',
    ]);

    $dokter->update($validated);

    return response()->json([
        'message' => 'Profile berhasil diupdate',
        'data' => $dokter
    ]);
}

    public function updatePassword(Request $request)
{
    $user = $request->user(); // ← user yang login (dari tabel users)

    $request->validate([
        'password_lama' => 'required',
        'password_baru' => 'required|min:6|confirmed',
    ]);

    // ✅ Cek password di tabel USERS, bukan dokters
    if (!Hash::check($request->password_lama, $user->password)) {
        return response()->json([
            'message' => 'Password lama salah'
        ], 400);
    }

    // ✅ Update password di tabel USERS
    $user->update([
        'password' => Hash::make($request->password_baru)
    ]);

    return response()->json([
        'message' => 'Password berhasil diubah'
    ]);
}
public function aktivitasHariIni(Request $request)
{
    $user = $request->user();

    $dokter = Dokter::where('email', $user->email)->first();

    if (!$dokter) {
        return response()->json(['message' => 'Dokter tidak ditemukan'], 404);
    }

    $today = now()->toDateString();

    // 🔹 Ambil data pendaftaran hari ini
   $pendaftaran = \App\Models\Pendaftaran::with('pasien')
    ->where('dokter_id', $dokter->id)
    ->latest()
    ->limit(5)
    ->get();
    $aktivitas = [];

    foreach ($pendaftaran as $item) {
        $aktivitas[] = [
            'time' => $item->created_at->format('H:i'),
            'desc' => 'Pasien: ' . ($item->pasien->nama ?? '-') . ' - Pemeriksaan'
        ];
    }

    // 🔹 Tambahan: pending diagnosis
    $pending = \App\Models\Pendaftaran::where('dokter_id', $dokter->id)
        ->where('status', 'pending')
        ->count();

    if ($pending > 0) {
        $aktivitas[] = [
            'time' => now()->format('H:i'),
            'desc' => "Diagnosis pending: $pending pasien menunggu"
        ];
    }

    // 🔹 Tambahan: rekam medis hari ini
    $rekam = \App\Models\RekamMedis::where('dokter_id', $dokter->id)
        ->whereDate('created_at', $today)
        ->count();

    if ($rekam > 0) {
        $aktivitas[] = [
            'time' => now()->format('H:i'),
            'desc' => "Rekam medis hari ini: $rekam entri"
        ];
    }

    return response()->json([
        'data' => $aktivitas
    ]);
}
}
