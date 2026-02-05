<?php

namespace App\Http\Controllers;

use App\Models\StoreType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreTypeController extends Controller
{
    public function index(Request $request)
    {
        // Mendukung fitur search di DataTable.vue
        $query = StoreType::query()->latest();

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('StoreTypes/Index', [
            'types' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        // Jika ID ada, berarti Mode Edit (Hanya satu data)
        if ($request->id) {
            $request->validate([
                'name' => 'required|string|max:255|unique:store_types,name,' . $request->id,
            ]);

            StoreType::where('id', $request->id)->update([
                'name' => $request->name
            ]);
        } 
        // Jika ID kosong, berarti Mode Create (Mendukung Batch/Antrian)
        else {
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.name' => 'required|string|max:255|unique:store_types,name',
            ], [
                'items.*.name.unique' => 'Salah satu nama jenis usaha sudah terdaftar.'
            ]);

            foreach ($request->items as $item) {
                StoreType::create([
                    'name' => $item['name']
                ]);
            }
        }

        return redirect()->back()->with('success', 'Data berhasil diproses!');
    }

    public function destroy($id)
    {
        $type = StoreType::findOrFail($id);
        $type->delete();

        return redirect()->back()->with('success', 'Tipe Usaha berhasil dihapus!');
    }
}