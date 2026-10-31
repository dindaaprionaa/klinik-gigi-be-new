<?php

namespace App\Http\Controllers\API\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use App\Models\Pendaftaran;
use App\Models\RekamMedis;
use App\Models\TarifTindakan;
use App\Models\Transaksi;
use App\Models\TransaksiItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransaksiController extends Controller
{
    public function index(Request $request)
    {
        $q = Transaksi::query()->with(['pasien', 'pendaftaran', 'items'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }

        if ($request->filled('dari')) {
            $q->whereDate('created_at', '>=', $request->input('dari'));
        }

        if ($request->filled('sampai')) {
            $q->whereDate('created_at', '<=', $request->input('sampai'));
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));

        return response()->json($q->paginate($perPage));
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'pendaftaran_id' => 'required|exists:pendaftarans,id',
            'rekam_medis_id' => 'nullable|exists:rekam_medis,id',
            'diskon' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.jenis' => 'required|in:obat,tindakan',
            'items.*.qty' => 'required|numeric|min:0.01',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        foreach ($request->items as $idx => $row) {
            if (($row['jenis'] ?? '') === 'obat' && empty($row['obat_id'])) {
                return response()->json(['errors' => ['items.'.$idx.'.obat_id' => ['Wajib untuk jenis obat.']]], 422);
            }
            if (($row['jenis'] ?? '') === 'tindakan' && empty($row['tarif_tindakan_id'])) {
                return response()->json(['errors' => ['items.'.$idx.'.tarif_tindakan_id' => ['Wajib untuk jenis tindakan.']]], 422);
            }
        }

        $pendaftaran = Pendaftaran::findOrFail($request->pendaftaran_id);

        if (! in_array($pendaftaran->status, ['checked_in', 'completed'], true)) {
            return response()->json([
                'message' => 'Billing hanya untuk pendaftaran yang sudah check-in atau selesai pemeriksaan.',
            ], 422);
        }

        if (Transaksi::where('pendaftaran_id', $pendaftaran->id)
            ->whereIn('status', [Transaksi::STATUS_DRAFT, Transaksi::STATUS_PAID])
            ->exists()) {
            return response()->json([
                'message' => 'Sudah ada transaksi draft atau lunas untuk pendaftaran ini.',
            ], 422);
        }

        $rekamMedis = null;
        if ($request->filled('rekam_medis_id')) {
            $rekamMedis = RekamMedis::findOrFail($request->rekam_medis_id);
            if ((int) $rekamMedis->pendaftaran_id !== (int) $pendaftaran->id) {
                return response()->json(['message' => 'Rekam medis tidak sesuai pendaftaran.'], 422);
            }
        }

        $lines = [];
        $subtotal = 0;

        foreach ($request->items as $row) {
            if ($row['jenis'] === 'obat') {
                $obat = Obat::where('id', $row['obat_id'])->where('aktif', true)->firstOrFail();
                $harga = (float) $obat->harga_jual;
                $nama = $obat->nama;
                $qty = (float) $row['qty'];
                $lineSub = round($harga * $qty, 2);
                $subtotal += $lineSub;
                $lines[] = [
                    'jenis' => 'obat',
                    'obat_id' => $obat->id,
                    'tarif_tindakan_id' => null,
                    'nama_snapshot' => $nama,
                    'qty' => $qty,
                    'harga_satuan' => $harga,
                    'subtotal' => $lineSub,
                ];
            } else {
                $tarif = TarifTindakan::where('id', $row['tarif_tindakan_id'])->where('aktif', true)->firstOrFail();
                $harga = (float) $tarif->harga;
                $qty = (float) $row['qty'];
                $lineSub = round($harga * $qty, 2);
                $subtotal += $lineSub;
                $lines[] = [
                    'jenis' => 'tindakan',
                    'obat_id' => null,
                    'tarif_tindakan_id' => $tarif->id,
                    'nama_snapshot' => $tarif->nama,
                    'qty' => $qty,
                    'harga_satuan' => $harga,
                    'subtotal' => $lineSub,
                ];
            }
        }

        $diskon = round((float) $request->input('diskon', 0), 2);
        $total = max(0, round($subtotal - $diskon, 2));

        $transaksi = DB::transaction(function () use ($request, $pendaftaran, $rekamMedis, $lines, $subtotal, $diskon, $total) {
            $t = Transaksi::create([
                'nomor_invoice' => $this->generateNomorInvoice(),
                'pendaftaran_id' => $pendaftaran->id,
                'rekam_medis_id' => $rekamMedis?->id,
                'pasien_id' => $pendaftaran->pasien_id,
                'user_id' => $request->user()->id,
                'status' => Transaksi::STATUS_DRAFT,
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'total' => $total,
                'metode_bayar' => null,
                'paid_at' => null,
            ]);

            foreach ($lines as $line) {
                TransaksiItem::create(array_merge($line, ['transaksi_id' => $t->id]));
            }

            return $t->load('items');
        });

        return response()->json([
            'message' => 'Draft transaksi dibuat.',
            'data' => $transaksi,
        ], 201);
    }

    public function show(Transaksi $transaksi)
    {
        $transaksi->load(['items', 'pasien', 'pendaftaran', 'rekamMedis', 'user']);

        return response()->json(['data' => $transaksi]);
    }

    /**
     * Payload JSON untuk invoice (cetak dilakukan di klien / FE terpisah).
     */
    public function invoice(Transaksi $transaksi)
    {
        $transaksi->load(['items', 'pasien', 'pendaftaran.dokter', 'user']);

        return response()->json([
            'invoice' => [
                'nomor' => $transaksi->nomor_invoice,
                'tanggal' => $transaksi->paid_at?->toIso8601String() ?? $transaksi->created_at->toIso8601String(),
                'status' => $transaksi->status,
                'pasien' => [
                    'nama' => $transaksi->pasien->nama,
                    'no_identitas' => $transaksi->pasien->no_identitas,
                ],
                'dokter' => $transaksi->pendaftaran?->dokter?->nama,
                'kasir' => $transaksi->user?->name,
                'subtotal' => (float) $transaksi->subtotal,
                'diskon' => (float) $transaksi->diskon,
                'total' => (float) $transaksi->total,
                'metode_bayar' => $transaksi->metode_bayar,
                'baris' => $transaksi->items->map(fn ($i) => [
                    'nama' => $i->nama_snapshot,
                    'jenis' => $i->jenis,
                    'qty' => (float) $i->qty,
                    'harga_satuan' => (float) $i->harga_satuan,
                    'subtotal' => (float) $i->subtotal,
                ]),
            ],
        ]);
    }

    public function bayar(Request $request, Transaksi $transaksi)
    {
        if ($transaksi->status !== Transaksi::STATUS_DRAFT) {
            return response()->json(['message' => 'Hanya transaksi draft yang dapat dibayar.'], 422);
        }

        $v = Validator::make($request->all(), [
            'metode_bayar' => 'required|string|max:40',
            'diskon' => 'nullable|numeric|min:0',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $diskon = round((float) $request->input('diskon', $transaksi->diskon), 2);
        $subtotal = (float) $transaksi->subtotal;
        $total = max(0, round($subtotal - $diskon, 2));

        try {
            DB::transaction(function () use ($transaksi, $request, $diskon, $total) {
                $items = TransaksiItem::where('transaksi_id', $transaksi->id)->lockForUpdate()->get();

                foreach ($items as $item) {
                    if ($item->jenis !== 'obat' || ! $item->obat_id) {
                        continue;
                    }

                    $obat = Obat::where('id', $item->obat_id)->lockForUpdate()->firstOrFail();
                    $need = (float) $item->qty;
                    if ((float) $obat->stok < $need) {
                        throw new \RuntimeException('Stok obat tidak mencukupi: '.$obat->nama.' (butuh '.$need.', tersedia '.$obat->stok.')');
                    }

                    $obat->decrement('stok', $need);
                }

                $transaksi->update([
                    'status' => Transaksi::STATUS_PAID,
                    'diskon' => $diskon,
                    'total' => $total,
                    'metode_bayar' => $request->metode_bayar,
                    'paid_at' => now(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Pembayaran dikonfirmasi. Stok obat diperbarui.',
            'data' => $transaksi->fresh()->load('items'),
        ]);
    }

    public function batal(Transaksi $transaksi)
    {
        if ($transaksi->status !== Transaksi::STATUS_DRAFT) {
            return response()->json(['message' => 'Hanya draft yang dapat dibatalkan.'], 422);
        }

        $transaksi->update(['status' => Transaksi::STATUS_CANCELLED]);

        return response()->json(['message' => 'Transaksi dibatalkan.', 'data' => $transaksi]);
    }

    private function generateNomorInvoice(): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';
        $last = Transaksi::where('nomor_invoice', 'like', $prefix.'%')->orderByDesc('id')->first();
        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last->nomor_invoice, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
