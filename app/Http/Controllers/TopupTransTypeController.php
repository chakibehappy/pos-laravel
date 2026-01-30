<?php

namespace App\Http\Controllers;

use App\Models\TopupTransType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TopupTransTypeController extends Controller
{
    public function index()
    {
        return Inertia::render('TopupTransType/Index', [
            'data' => TopupTransType::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
        ]);

        TopupTransType::create($request->all());

        return back()->with('message', 'Tipe transaksi berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
        ]);

        $item = TopupTransType::findOrFail($id);
        $item->update($request->all());

        return back()->with('message', 'Data berhasil diperbarui.');
    }

    public function destroy($id)
    {
        TopupTransType::findOrFail($id)->delete();
        return back()->with('message', 'Data berhasil dihapus.');
    }
}