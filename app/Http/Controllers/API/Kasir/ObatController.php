<?php

namespace App\Http\Controllers\API\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ObatController extends Controller
{
    public function index(Request $request)
    {
        $q = Obat::query()->orderBy('nama');

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
            $q->where(function ($w) use ($like) {
                $w->where('nama', 'like', $like)->orWhere('kode', 'like', $like);
            });
        }

        if ($request->boolean('hanya_aktif', false)) {
            $q->where('aktif', true);
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));

        return response()->json($q->paginate($perPage));
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'kode' => 'required|string|max:50|unique:obats,kode',
            'nama' => 'required|string|max:255',
            'satuan' => 'nullable|string|max:32',
            'harga_jual' => 'required|numeric|min:0',
            'stok' => 'nullable|numeric|min:0',
            'stok_minimum' => 'nullable|numeric|min:0',
            'aktif' => 'nullable|boolean',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $data = $v->validated();
        $data['satuan'] = $data['satuan'] ?? 'pcs';
        $data['stok'] = $data['stok'] ?? 0;
        $data['stok_minimum'] = $data['stok_minimum'] ?? 0;
        $data['aktif'] = array_key_exists('aktif', $data) ? (bool) $data['aktif'] : true;

        $obat = Obat::create($data);

        return response()->json(['message' => 'Obat tersimpan', 'data' => $obat], 201);
    }

    public function show(Obat $obat)
    {
        return response()->json(['data' => $obat]);
    }

    public function update(Request $request, Obat $obat)
    {
        $v = Validator::make($request->all(), [
            'kode' => 'sometimes|required|string|max:50|unique:obats,kode,'.$obat->id,
            'nama' => 'sometimes|required|string|max:255',
            'satuan' => 'nullable|string|max:32',
            'harga_jual' => 'sometimes|required|numeric|min:0',
            'stok' => 'sometimes|required|numeric|min:0',
            'stok_minimum' => 'nullable|numeric|min:0',
            'aktif' => 'sometimes|boolean',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $obat->update($request->only([
            'kode', 'nama', 'satuan', 'harga_jual', 'stok', 'stok_minimum', 'aktif',
        ]));

        return response()->json(['message' => 'Obat diperbarui', 'data' => $obat->fresh()]);
    }

    public function destroy(Obat $obat)
    {
        $obat->delete();

        return response()->json(['message' => 'Obat dihapus']);
    }
}
