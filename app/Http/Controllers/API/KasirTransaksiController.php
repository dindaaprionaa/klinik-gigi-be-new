<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\Pendaftaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KasirTransaksiController extends Controller
{
    /**
     * GET /kasir/transaksi
     * List semua transaksi dengan filter opsional
     */
    public function index(Request $request)
    {
        $query = Transaksi::with([
            'pendaftaran.pasien',
            'pendaftaran.dokter',
            'pendaftaran.rekamMedis',
            'pasien',
            'items',
            'user',
            'rekamMedis',
        ])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('metode_bayar')) {
            $query->where('metode_bayar', $request->metode_bayar);
        }
        if ($request->filled('tanggal')) {
            $query->whereDate('created_at', $request->tanggal);
        }
        if ($request->filled('pasien_id')) {
            $query->where('pasien_id', $request->pasien_id);
        }

        $data = $query->paginate($request->get('per_page', 20));

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * POST /kasir/transaksi
     * Pasien membuat transaksi baru (status: draft)
     * Form-data: pendaftaran_id, metode_bayar, bukti_transfer (image)
     */
    /**
 * POST /pasien/transaksi
 * Pasien kirim pembayaran → update transaksi existing (status: billed → draft)
 */
public function store(Request $request)
{
    $request->validate([
        'pendaftaran_id' => 'required|exists:pendaftarans,id',
        'metode_bayar'   => 'required|in:tunai,transfer',
        'bukti_transfer' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'catatan'        => 'nullable|string|max:500',
    ]);

    // Jika transfer, wajib ada bukti
    if ($request->metode_bayar === 'transfer' && !$request->hasFile('bukti_transfer')) {
        return response()->json([
            'success' => false,
            'message' => 'Bukti transfer wajib diupload untuk metode transfer.',
        ], 422);
    }

    $pendaftaran = Pendaftaran::with(['pasien', 'rekamMedis'])->findOrFail($request->pendaftaran_id);

    // Pastikan pasien yang login adalah pemilik pendaftaran ini
    $pasienLogin = \App\Models\Pasien::where('email', Auth::user()->email)->first();
    if (!$pasienLogin || (int) $pendaftaran->pasien_id !== (int) $pasienLogin->id) {
        return response()->json([
            'success' => false,
            'message' => 'Anda tidak berhak mengakses pendaftaran ini.',
        ], 403);
    }

    // Cari transaksi yang sudah disiapkan admin (status: billed)
    $transaksi = Transaksi::where('pendaftaran_id', $pendaftaran->id)
        ->where('status', Transaksi::STATUS_BILLED)
        ->first();

    // Belum ada tagihan dari admin
    if (!$transaksi) {
        // Cek apakah masih pending_billing (admin belum input tagihan)
        $pending = Transaksi::where('pendaftaran_id', $pendaftaran->id)
            ->where('status', Transaksi::STATUS_PENDING_BILLING)
            ->exists();

        return response()->json([
            'success' => false,
            'message' => $pending
                ? 'Tagihan sedang disiapkan oleh admin. Silakan tunggu.'
                : 'Tidak ada tagihan aktif untuk pendaftaran ini.',
        ], 422);
    }

    // Cegah bayar ulang jika sudah pernah kirim (status: draft atau paid)
    $sudahBayar = Transaksi::where('pendaftaran_id', $pendaftaran->id)
        ->whereIn('status', [Transaksi::STATUS_DRAFT, Transaksi::STATUS_PAID])
        ->exists();

    if ($sudahBayar) {
        return response()->json([
            'success' => false,
            'message' => 'Pembayaran untuk kunjungan ini sudah dikirim.',
        ], 422);
    }

    // Upload bukti transfer
    $buktiPath = null;
    if ($request->hasFile('bukti_transfer')) {
        $buktiPath = $request->file('bukti_transfer')->store('bukti_transfer', 'public');
    }

    // Update transaksi existing: billed → draft
    $transaksi->update([
        'metode_bayar'   => $request->metode_bayar,
        'bukti_transfer' => $buktiPath,
        'catatan_pasien' => $request->catatan,
        'status'         => Transaksi::STATUS_DRAFT, // menunggu konfirmasi admin
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Pembayaran berhasil dikirim, menunggu konfirmasi admin.',
        'data'    => $transaksi->fresh()->load(['pendaftaran.pasien', 'items']),
    ], 200); // 200 bukan 201 karena update, bukan create
}
    /**
     * GET /kasir/transaksi/{transaksi}
     */
    public function show(Transaksi $transaksi)
    {
        $transaksi->load([
            'pendaftaran.pasien',
            'pendaftaran.dokter',
            'pasien',
            'rekamMedis',
            'items',
            'user',
        ]);

        // bukti_transfer_url sudah di-append otomatis oleh model
        return response()->json([
            'success' => true,
            'data'    => $transaksi,
        ]);
    }

    /**
     * GET /kasir/transaksi/{transaksi}/invoice
     */
    public function invoice(Transaksi $transaksi)
    {
        $transaksi->load([
            'pendaftaran.pasien',
            'pendaftaran.dokter',
            'pasien',
            'rekamMedis',
            'items',
            'user',
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'transaksi'     => $transaksi,
                'klinik_nama'   => config('app.klinik_nama', 'Klinik Sehat'),
                'klinik_alamat' => config('app.klinik_alamat', '-'),
                'tanggal_cetak' => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * POST /kasir/transaksi/{transaksi}/bayar
     * Kasir konfirmasi pembayaran → status: paid
     * Body (opsional): items[], diskon, metode_bayar, catatan_kasir
     */
    public function bayar(Request $request, Transaksi $transaksi)
    {
        $request->validate([
            'metode_bayar'   => 'sometimes|in:tunai,transfer',
            'diskon'         => 'nullable|numeric|min:0',
            'catatan_kasir'  => 'nullable|string|max:500',
            'items'          => 'nullable|array',
            'items.*.nama_snapshot' => 'required_with:items|string|max:255',
            'items.*.harga_satuan'  => 'required_with:items|numeric|min:0',
            'items.*.qty'           => 'required_with:items|numeric|min:0',
        ]);

        if ($transaksi->status === Transaksi::STATUS_PAID) {
            return response()->json(['success' => false, 'message' => 'Transaksi sudah PAID.'], 422);
        }
        if ($transaksi->status === Transaksi::STATUS_CANCELLED) {
            return response()->json(['success' => false, 'message' => 'Transaksi sudah CANCELLED.'], 422);
        }

        // Hitung ulang total dari items jika dikirim
        if ($request->filled('items')) {
            $transaksi->items()->delete();
            $subtotal = 0;
            foreach ($request->items as $item) {
            $jumlah = $item['harga_satuan'] * $item['qty'];
            $transaksi->items()->create([
                'nama_snapshot' => $item['nama_snapshot'],  // ✅ sesuai model
                'harga_satuan'  => $item['harga_satuan'],   // ✅ sesuai model
                'qty'           => $item['qty'],
                'subtotal'      => $jumlah,                 // ✅ sesuai model
                'jenis'         => $item['jenis'] ?? 'lain',
            ]);
            $subtotal += $jumlah;
        }
            $diskon = $request->input('diskon', $transaksi->diskon ?? 0);
            $transaksi->subtotal = $subtotal;
            $transaksi->diskon   = $diskon;
            $transaksi->total    = max(0, $subtotal - $diskon);
        }

        $transaksi->status     = Transaksi::STATUS_PAID;
        $transaksi->metode_bayar = $request->metode_bayar ?? $transaksi->metode_bayar;
        $transaksi->user_id    = Auth::id();
        $transaksi->paid_at    = now();
        $transaksi->save();

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran dikonfirmasi. Status: PAID.',
            'data'    => $transaksi->fresh()->load(['pendaftaran.pasien', 'items']),
        ]);
    }

    public function inputTagihan(Request $request, Transaksi $transaksi)
{
    $request->validate([
        'items'          => 'required|array|min:1',
        'items.*.nama_snapshot' => 'required|string|max:255',
        'items.*.harga_satuan'  => 'required|numeric|min:0',
        'items.*.qty'           => 'required|integer|min:1',
        'items.*.jenis'         => 'nullable|string',
        'diskon'         => 'nullable|numeric|min:0',
        'catatan_kasir'  => 'nullable|string|max:500',
    ]);

    if ($transaksi->status !== Transaksi::STATUS_PENDING_BILLING) {
        return response()->json(['success' => false, 'message' => 'Transaksi tidak dalam status pending billing.'], 422);
    }

    $transaksi->items()->delete();
    $subtotal = 0;
    foreach ($request->items as $item) {
        $jumlah = $item['harga_satuan'] * $item['qty'];
        $transaksi->items()->create([
            'nama_snapshot' => $item['nama_snapshot'],
            'harga_satuan'  => $item['harga_satuan'],
            'qty'           => $item['qty'],
            'subtotal'      => $jumlah,
            'jenis'         => $item['jenis'] ?? 'lain',
        ]);
        $subtotal += $jumlah;
    }

    $diskon = $request->input('diskon', 0);
    $transaksi->update([
        'subtotal'      => $subtotal,
        'diskon'        => $diskon,
        'total'         => max(0, $subtotal - $diskon),
        'catatan_kasir' => $request->catatan_kasir,
        'status'        => Transaksi::STATUS_BILLED,  // ← pasien sekarang bisa lihat tagihan
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Tagihan berhasil diinput. Pasien dapat melihat total tagihan.',
        'data'    => $transaksi->fresh()->load(['pendaftaran.pasien', 'items']),
    ]);
}

    /**
     * POST /kasir/transaksi/{transaksi}/batal
     * Batalkan transaksi → status: cancelled
     */
    public function batal(Request $request, Transaksi $transaksi)
    {
        $request->validate(['alasan' => 'nullable|string|max:500']);

        if (in_array($transaksi->status, [Transaksi::STATUS_PAID, Transaksi::STATUS_CANCELLED])) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak bisa dibatalkan (sudah PAID atau CANCELLED).',
            ], 422);
        }

        $transaksi->update([
            'status'  => Transaksi::STATUS_CANCELLED,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dibatalkan.',
            'data'    => $transaksi->fresh(),
        ]);
    }

    /**
 * GET /pasien/transaksi
 * Hanya tampilkan transaksi milik pasien yang login
 */
    public function indexPasien(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $pasien = \App\Models\Pasien::where('email', $user->email)->first();

    if (!$pasien) {
        return response()->json(['message' => 'Data pasien tidak ditemukan'], 404);
    }

    $transaksi = \App\Models\Transaksi::with(['pendaftaran.dokter', 'items'])
        ->where('pasien_id', $pasien->id)
        ->latest()
        ->get();

    return response()->json([
        'data' => $transaksi
    ]);
}

/**
 * GET /pasien/transaksi/{transaksi}
 * Pastikan transaksi milik pasien yang login
 */
public function showPasien(Transaksi $transaksi)
{
    $pasien = \App\Models\Pasien::where('email', Auth::user()->email)->firstOrFail();

    if ((int) $transaksi->pasien_id !== (int) $pasien->id) {
        return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
    }

    return response()->json([
        'success' => true,
        'data'    => $transaksi->load(['pendaftaran.dokter', 'items']),
    ]);
}

public function ubahStatus(Request $request, Transaksi $transaksi)
{
    $request->validate([
        'status' => 'required|in:pending_billing'
    ]);

    // ❌ Tidak boleh jika sudah PAID
    if ($transaksi->status === Transaksi::STATUS_PAID) {
        return response()->json([
            'success' => false,
            'message' => 'Transaksi sudah PAID, tidak bisa diubah.'
        ], 422);
    }

    // ✅ Hanya izinkan CANCELLED → PENDING_BILLING
    if ($transaksi->status === Transaksi::STATUS_CANCELLED) {
        $transaksi->update([
            'status' => Transaksi::STATUS_PENDING_BILLING,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil diaktifkan kembali.',
            'data' => $transaksi->fresh()
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Perubahan status tidak diizinkan.'
    ], 422);
}
}
