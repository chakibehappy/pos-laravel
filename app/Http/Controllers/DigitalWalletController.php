<?php

namespace App\Http\Controllers;

use App\Models\DigitalWallet;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DigitalWalletController extends Controller
{
    /**
     * Menampilkan daftar platform wallet.
     * Menggunakan variabel 'resource' agar kompatibel dengan DataTable.vue.
     */
    public function index(Request $request)
    {
        $resource = DigitalWallet::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('DigitalWallets/Index', [
            'resource' => $resource, // Nama variabel harus 'resource' sesuai sistem DataTable
            'filters'  => $request->only(['search']),
        ]);
    }

    /**
     * Simpan atau Update Master Platform Wallet.
     * Tidak lagi menyertakan kolom balance sesuai database terbaru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id'   => 'nullable|exists:digital_wallet,id',
            'name' => 'required|string|max:255|unique:digital_wallet,name,' . $request->id,
        ]);

        try {
            DigitalWallet::updateOrCreate(
                ['id' => $request->id],
                [
                    'name' => $request->name,
                    // created_by bisa ditambahkan jika Anda menggunakan Auth::id()
                ]
            );

            return back()->with('message', 'Platform Wallet berhasil disimpan!');
        } catch (\Exception $e) {
            return back()->withErrors(['name' => 'Gagal menyimpan data platform.']);
        }
    }

    /**
     * Hapus Master Platform Wallet.
     */
    public function destroy($id)
    {
        try {
            DigitalWallet::findOrFail($id)->delete();
            return back()->with('message', 'Platform Wallet berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->withErrors(['id' => 'Gagal menghapus platform.']);
        }
    }
}