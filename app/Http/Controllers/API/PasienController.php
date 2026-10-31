<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\User; // <-- Diberikan akses ke model User
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash; // <-- Wajib import Hash

class PasienController extends Controller
{
    /**
     * Menampilkan daftar pasien dengan paginasi dan pencarian.
     */
    public function index(Request $request)
    {
        $query = Pasien::query()->orderByDesc('updated_at');

        // Pencarian Global
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('nama', 'like', $like)
                  ->orWhere('no_identitas', 'like', $like)
                  ->orWhere('no_pendaftaran', 'like', $like)
                  ->orWhere('email', 'like', $like)
                  ->orWhere('no_telepon', 'like', $like);
            });
        }

        // Filter Jenis Kelamin
        if ($request->filled('jenis_kelamin')) {
            $query->where('jenis_kelamin', $request->input('jenis_kelamin'));
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 15)));
        return response()->json($query->paginate($perPage), 200);
    }

    /**
     * Menyimpan data pasien baru (Manual oleh Admin).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'            => 'required|string|max:255',
            'no_identitas'    => 'required|string|size:16|unique:pasiens,no_identitas', // Sesuai permintaan 16 digit
            'jenis_kelamin'   => 'required|in:Laki-laki,Perempuan',
            'tanggal_lahir'   => 'required|date|before_or_equal:today',
            'no_telepon'      => 'required|string|max:50',
            'alamat'          => 'nullable|string|max:2000',
            'email'           => 'nullable|email|max:255|unique:pasiens,email',
            'no_pendaftaran'  => 'nullable|string|unique:pasiens,no_pendaftaran',
            'golongan_darah'  => 'nullable|in:A,B,AB,O',
            'password'        => 'nullable|string|min:6', // Validation Password
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (empty($data['no_pendaftaran'])) {
            $count = Pasien::whereDate('created_at', now())->count() + 1;
            $data['no_pendaftaran'] = 'PAS-' . now()->format('Ymd') . str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        $data['alamat'] = $data['alamat'] ?? '-';

        // Tentukan password (jika kosong, default ke 'password123')
        $rawPassword = $request->input('password') ?: 'password123';
        $data['password'] = Hash::make($rawPassword);

        $pasien = null;

        DB::transaction(function () use (&$pasien, $data, $rawPassword) {
            // 1. Buat Data Pasien
            $pasien = Pasien::create($data);

            // 2. Buat Akun User jika Sistem Auth Login membaca tabel `users`
            if (class_exists(User::class) && !empty($data['email'])) {
                User::updateOrCreate(
                    ['email' => $data['email']],
                    [
                        'name'     => $data['nama'],
                        'password' => Hash::make($rawPassword),
                        'role'     => 'pasien', // Sesuaikan dengan kolom role aplikasi Anda
                    ]
                );
            }
        });

        return response()->json([
            'message' => 'Data pasien berhasil ditambahkan',
            'data' => $pasien,
        ], 201);
    }

    /**
     * Detail pasien beserta riwayat medisnya.
     */
    public function show(Pasien $pasien)
    {
        return response()->json([
            'data' => $pasien->load(['pendaftaran', 'rekamMedis']),
        ], 200);
    }

    /**
     * Update data pasien oleh Admin.
     */
    public function update(Request $request, Pasien $pasien)
    {
        $validator = Validator::make($request->all(), [
            'nama'            => 'sometimes|required|string|max:255',
            'no_identitas'    => 'sometimes|required|string|size:16|unique:pasiens,no_identitas,' . $pasien->id,
            'jenis_kelamin'   => 'sometimes|required|in:Laki-laki,Perempuan',
            'tanggal_lahir'   => 'sometimes|required|date|before_or_equal:today',
            'no_telepon'      => 'sometimes|required|string|max:50',
            'alamat'          => 'sometimes|required|string|max:2000',
            'email'           => 'nullable|email|unique:pasiens,email,' . $pasien->id,
            'golongan_darah'  => 'nullable|in:A,B,AB,O',
            'password'        => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Update password jika diisi oleh Admin
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        $pasien->update($data);

        // Update juga ke tabel users jika email terhubung
        if (class_exists(User::class) && $pasien->email) {
            $userPayload = ['name' => $pasien->nama];
            if ($request->filled('password')) {
                $userPayload['password'] = Hash::make($request->password);
            }
            User::where('email', $pasien->email)->update($userPayload);
        }

        return response()->json([
            'message' => 'Data pasien berhasil diperbarui',
            'data' => $pasien,
        ], 200);
    }

    /**
     * Menghapus data pasien beserta seluruh data relasinya secara bersih (Deep Cascade Delete).
     */
    public function destroy(Pasien $pasien)
    {
        try {
            DB::transaction(function () use ($pasien) {
                // Hapus akun login di tabel users jika ada
                if (class_exists(User::class) && $pasien->email) {
                    User::where('email', $pasien->email)->delete();
                }

                $pendaftaranIds = $pasien->pendaftaran()->pluck('id');
                $rekamMedisIds  = $pasien->rekamMedis()->pluck('id');

                if ($pendaftaranIds->isNotEmpty()) {
                    $pendaftaranChildTables = [
                        'transaksis', 'transaksi', 'pembayarans', 'pembayaran', 'tagihans', 'tagihan'
                    ];

                    foreach ($pendaftaranChildTables as $table) {
                        if (Schema::hasTable($table)) {
                            DB::table($table)->whereIn('pendaftaran_id', $pendaftaranIds)->delete();
                        }
                    }
                }

                if ($rekamMedisIds->isNotEmpty()) {
                    $rekamMedisChildTables = [
                        'resep_obats', 'resep_obat', 'reseps', 'tindakan_pasiens', 'tindakan_pasien', 'tindakans'
                    ];

                    foreach ($rekamMedisChildTables as $table) {
                        if (Schema::hasTable($table)) {
                            DB::table($table)->whereIn('rekam_medis_id', $rekamMedisIds)->delete();
                        }
                    }
                }

                $pasien->rekamMedis()->delete();
                $pasien->pendaftaran()->delete();

                $pasien->delete();
            });

            return response()->json([
                'message' => 'Data pasien berhasil dihapus'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal menghapus data pasien: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API untuk Pasien melihat Profil sendiri (berdasarkan Email Login).
     */
    public function profile(Request $request)
    {
        $pasien = Pasien::where('email', $request->user()->email)->first();

        if (!$pasien) {
            return response()->json(['message' => 'Profil pasien tidak ditemukan'], 404);
        }

        return response()->json([
            'data' => array_merge($pasien->toArray(), [
                'photo_url' => $this->photoUrlForUser($request->user()->id),
            ]),
        ], 200);
    }

    /**
     * Update Profil oleh Pasien sendiri.
     */
    public function updateProfile(Request $request)
    {
        $pasien = Pasien::where('email', $request->user()->email)->first();

        if (!$pasien) {
            return response()->json(['message' => 'Profil tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama'             => 'sometimes|required|string|max:255',
            'alamat'           => 'sometimes|required|string',
            'no_telepon'       => 'sometimes|required|string',
            'status_pernikahan'=> 'nullable|string',
            'pekerjaan'        => 'nullable|string',
            'berat_badan'      => 'nullable|numeric',
            'tinggi_badan'     => 'nullable|numeric',
            'alergi'           => 'nullable|string',
            'jenis_kelamin'    => 'nullable|in:Laki-laki,Perempuan',
            'tanggal_lahir'    => 'nullable|date',
            'golongan_darah'   => 'nullable|in:A,B,AB,O',
            'no_identitas'     => 'nullable|string|size:16|unique:pasiens,no_identitas,' . $pasien->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pasien->update($validator->validated());

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'data' => $pasien->fresh(),
        ], 200);
    }

    // --- HELPER METHODS UNTUK FOTO ---

    public function updatePhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $path = $this->storePhotoForUser($request, $request->user()->id);

        return response()->json([
            'message' => 'Foto berhasil diperbarui',
            'photo_url' => asset('assets/profile/' . basename($path)),
        ], 200);
    }

    private function photoUrlForUser(int $userId): ?string
    {
        $dir = public_path('assets/profile');
        $matches = File::glob($dir . DIRECTORY_SEPARATOR . 'user-' . $userId . '.*');
        return $matches ? asset('assets/profile/' . basename($matches[0])) : null;
    }

    private function storePhotoForUser(Request $request, int $userId): string
    {
        $dir = public_path('assets/profile');
        if (!File::isDirectory($dir)) File::makeDirectory($dir, 0755, true);

        foreach (File::glob($dir . "/user-$userId.*") as $old) File::delete($old);

        $file = $request->file('photo');
        $filename = "user-$userId." . $file->getClientOriginalExtension();
        $file->move($dir, $filename);

        return $dir . DIRECTORY_SEPARATOR . $filename;
    }
}