<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalSourceType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WithdrawalSourceTypeController extends Controller
{
    /**
     * Menampilkan daftar semua kategori dengan Paginasi & Search.
     */
    public function index(Request $request)
    {
        $data = WithdrawalSourceType::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('WithdrawalSourceType/Index', [
            'data' => $data,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        WithdrawalSourceType::create(['name' => $request->name]);

        return back()->with('message', 'Sumber penarikan berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $item = WithdrawalSourceType::findOrFail($id);
        $item->update(['name' => $request->name]);

        return back()->with('message', 'Sumber penarikan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        try {
            $item = WithdrawalSourceType::findOrFail($id);
            $item->delete();

            return back()->with('message', 'Sumber penarikan berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus! Data mungkin masih digunakan oleh transaksi lain.']);
        }
    }
}