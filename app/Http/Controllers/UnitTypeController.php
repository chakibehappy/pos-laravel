<?php

namespace App\Http\Controllers;

use App\Models\UnitType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnitTypeController extends Controller
{
    /**
     * Menampilkan daftar satuan.
     * Disesuaikan untuk mendukung Pagination & Search.
     */
    public function index(Request $request)
    {
        return Inertia::render('UnitTypes/Index', [
            'units' => UnitType::query()
                ->when($request->search, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->latest()
                ->paginate(10) // WAJIB paginate agar DataTable tidak error
                ->withQueryString(),
            'filters' => $request->only(['search'])
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

        return back()->with('message', 'Satuan berhasil disimpan!');
    }

    /**
     * Menghapus satuan.
     */
    public function destroy($id)
    {
        $unit = UnitType::findOrFail($id);
        
        // Cek jika satuan masih dipakai produk bisa ditambahkan di sini
        
        $unit->delete();

        return back()->with('message', 'Satuan berhasil dihapus!');
    }
}