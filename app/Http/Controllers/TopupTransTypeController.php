<?php

namespace App\Http\Controllers;

use App\Models\TopupTransType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TopupTransTypeController extends Controller
{
    /**
     * Menampilkan data dengan Paginasi dan Search.
     */
    public function index(Request $request)
    {
        $data = TopupTransType::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('TopupTransType/Index', [
            'data' => $data,
            'filters' => $request->only(['search']),
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
        try {
            TopupTransType::findOrFail($id)->delete();
            return back()->with('message', 'Data berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus! Data mungkin terikat dengan transaksi lain.']);
        }
    }
}