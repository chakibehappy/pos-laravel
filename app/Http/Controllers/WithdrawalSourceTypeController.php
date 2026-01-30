<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalSourceType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WithdrawalSourceTypeController extends Controller
{
    /**
     * Menampilkan daftar semua kategori sumber penarikan.
     */
    public function index()
    {
        return Inertia::render('WithdrawalSourceType/Index', [
            'data' => WithdrawalSourceType::latest()->get()
        ]);
    }

    /**
     * Menyimpan kategori sumber penarikan baru ke tabel withdrawal_source_type.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        WithdrawalSourceType::create([
            'name' => $request->name,
        ]);

        return back()->with('message', 'Sumber penarikan berhasil ditambahkan.');
    }

    /**
     * Memperbarui data nama pada tabel withdrawal_source_type.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $item = WithdrawalSourceType::findOrFail($id);
        $item->update([
            'name' => $request->name,
        ]);

        return back()->with('message', 'Sumber penarikan berhasil diperbarui.');
    }

    /**
     * Menghapus data dari tabel withdrawal_source_type.
     */
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