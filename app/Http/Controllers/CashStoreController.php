<?php

namespace App\Http\Controllers;

use App\Models\CashStore;
use App\Models\Store;
use App\Models\StoreType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CashStoreController extends Controller
{
    /**
     * READ: Menampilkan dashboard kas dengan Paginasi & Search.
     */
    public function index(Request $request)
    {
        // 1. Query dengan filter search dan relasi store
        $cashBalances = CashStore::with('store')
            ->when($request->search, function ($query, $search) {
                $query->whereHas('store', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10) // Paginasi 10 data per halaman
            ->withQueryString();

        return Inertia::render('CashStores/Index', [
            // Data utama dengan paginasi
            'cashBalances' => $cashBalances,
            
            // Data pendukung untuk modal/dropdown
            'stores' => Store::all(['id', 'name', 'store_type_id']),
            'storeTypes' => StoreType::all(['id', 'name']),
            
            // Mengirim balik parameter filter untuk input search di Vue
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * CREATE/UPDATE: Menyimpan data baru atau memperbarui data kas.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id'       => 'nullable|exists:cash_store,id',
            'store_id' => 'required|exists:stores,id',
            'cash'     => 'required|numeric|min:0'
        ]);

        CashStore::updateOrCreate(
            ['id' => $request->id],
            [
                'store_id' => $request->store_id,
                'cash'     => $request->cash
            ]
        );

        $message = $request->id ? 'Data kas berhasil diperbarui!' : 'Data kas berhasil dicatat!';
        return back()->with('message', $message);
    }

    /**
     * DELETE: Menghapus pencatatan kas berdasarkan ID.
     */
    public function destroy($id)
    {
        try {
            $cash = CashStore::findOrFail($id);
            $cash->delete();
            
            return back()->with('message', 'Data kas berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus data kas.']);
        }
    }
}