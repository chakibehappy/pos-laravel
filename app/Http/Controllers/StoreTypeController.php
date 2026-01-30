<?php

namespace App\Http\Controllers;

use App\Models\StoreType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreTypeController extends Controller
{

    public function index()
    {
        return Inertia::render('StoreTypes/Index', [
            'types' => StoreType::latest()->get()
        ]);
    }

   
    public function store(Request $request)
    {
        $request->validate([
            'id'   => 'nullable|numeric',

            'name' => 'required|string|max:255|unique:store_types,name,' . $request->id,
        ]);

        StoreType::updateOrCreate(
            ['id' => $request->id],
            ['name' => $request->name]
        );

        return redirect()->back()->with('success', 'Data berhasil diproses!');
    }


    public function destroy($id)
    {
        $type = StoreType::findOrFail($id);
        $type->delete();

        return redirect()->back()->with('success', 'Tipe Usaha berhasil dihapus!');
    }
}