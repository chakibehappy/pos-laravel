<?php

namespace App\Http\Controllers;

use App\Models\UnitType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnitTypeController extends Controller
{
    /**
     * Menampilkan daftar satuan.
     */
    public function index()
    {
        return Inertia::render('UnitTypes/Index', [
            'units' => UnitType::latest()->get()
        ]);
    }

    /**
     * Menyimpan satuan baru atau memperbarui satuan lama.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id'   => 'nullable|numeric',
            'name' => 'required|string|max:100|unique:unit_types,name,' . $request->id,
        ], [
            'name.unique' => 'Nama satuan sudah ada!',
            'name.required' => 'Nama satuan wajib diisi.'
        ]);

        UnitType::updateOrCreate(
            ['id' => $request->id],
            ['name' => $request->name]
        );

        return back()->with('message', 'satuan berhasil disimpan!');
    }

    /**
     * Menghapus satuan.
     */
    public function destroy($id)
    {
        $unit = UnitType::findOrFail($id);
        
        // Cek jika satuan masih dipakai produk (opsional tapi disarankan)
        // if ($unit->products()->count() > 0) {
        //     return back()->with('error', 'Gagal! satuan masih digunakan oleh produk.');
        // }

        $unit->delete();

        return back()->with('message', 'satuan berhasil dihapus!');
    }
}