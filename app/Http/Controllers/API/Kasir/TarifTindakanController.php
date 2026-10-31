<?php

namespace App\Http\Controllers\API\Kasir;

use App\Http\Controllers\Controller;
use App\Models\TarifTindakan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TarifTindakanController extends Controller
{
    public function index(Request $request)
    {
        $q = TarifTindakan::query()->orderBy('nama');

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
            'kode' => 'required|string|max:50|unique:tarif_tindakans,kode',
            'nama' => 'required|string|max:255',
            'harga' => 'required|numeric|min:0',
            'aktif' => 'nullable|boolean',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $row = TarifTindakan::create([
            'kode' => $request->kode,
            'nama' => $request->nama,
            'harga' => $request->harga,
            'aktif' => $request->boolean('aktif', true),
        ]);

        return response()->json(['message' => 'Tarif tindakan tersimpan', 'data' => $row], 201);
    }

    public function show(TarifTindakan $tarifTindakan)
    {
        return response()->json(['data' => $tarifTindakan]);
    }

    public function update(Request $request, TarifTindakan $tarifTindakan)
    {
        $v = Validator::make($request->all(), [
            'kode' => 'sometimes|required|string|max:50|unique:tarif_tindakans,kode,'.$tarifTindakan->id,
            'nama' => 'sometimes|required|string|max:255',
            'harga' => 'sometimes|required|numeric|min:0',
            'aktif' => 'sometimes|boolean',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $tarifTindakan->update($request->only(['kode', 'nama', 'harga', 'aktif']));

        return response()->json(['message' => 'Tarif diperbarui', 'data' => $tarifTindakan->fresh()]);
    }

    public function destroy(TarifTindakan $tarifTindakan)
    {
        $tarifTindakan->delete();

        return response()->json(['message' => 'Tarif dihapus']);
    }
}
