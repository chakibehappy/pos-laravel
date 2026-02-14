<?php

namespace App\Http\Controllers;

use App\Models\DigitalWallet;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DigitalWalletController extends Controller
{
    /**
     * Menampilkan daftar platform wallet dengan Sorting, Pagination & Search.
     * Menggunakan variabel 'resource' agar kompatibel dengan DataTable.vue.
     */
    public function index(Request $request)
    {
        // Ambil parameter sort, default ke 'id' dan 'desc'
        $sortField = $request->input('sort', 'id');
        $sortDirection = $request->input('direction', 'desc');

        $resource = DigitalWallet::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            // Logika Sorting Dinamis
            ->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('DigitalWallets/Index', [
            'resource' => $resource, // Nama variabel harus 'resource' sesuai sistem DataTable
            'filters'  => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    /**
     * Simpan atau Update Master Platform Wallet.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id'   => 'nullable|exists:digital_wallet,id',
            'name' => 'required|string|max:255|unique:digital_wallet,name,' . $request->id,
        ], [
            'name.unique' => 'Nama platform sudah terdaftar!',
            'name.required' => 'Nama platform wajib diisi.'
        ]);

        try {
            DigitalWallet::updateOrCreate(
                ['id' => $request->id],
                [
                    'name' => $request->name,
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
            $wallet = DigitalWallet::findOrFail($id);
            
            // Opsional: Cek jika platform masih digunakan di tabel relasi (jika ada)
            // if ($wallet->storeWallets()->exists()) {
            //     return back()->with('error', 'Gagal! Platform masih digunakan oleh toko.');
            // }

            $wallet->delete();
            return back()->with('message', 'Platform Wallet berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->withErrors(['id' => 'Gagal menghapus platform.']);
        }
    }
}