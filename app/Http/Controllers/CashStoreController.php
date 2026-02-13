<?php

namespace App\Http\Controllers;

use App\Models\CashStore;
use App\Models\Store;
use App\Models\StoreType;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Helpers\ActivityLogger;

class CashStoreController extends Controller
{
    public function index(Request $request)
    {
        // 1. SYNC OTOMATIS: Pastikan setiap toko di tabel stores punya baris di cash_stores
        $allStoreIds = Store::pluck('id');
        $existingCashStoreIds = CashStore::pluck('store_id')->toArray();
        $missingStores = $allStoreIds->diff($existingCashStoreIds);

        if ($missingStores->count() > 0) {
            foreach ($missingStores as $storeId) {
                CashStore::create([
                    'store_id' => $storeId,
                    'cash' => 0
                ]);
            }
        }

        // 2. QUERY DATA: Ambil saldo kas dengan pencarian dan FILTER TIPE
        $cashBalances = CashStore::with('store')
            ->when($request->search, function ($query, $search) {
                $query->whereHas('store', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            // Tambahan Filter Tipe Usaha
            ->when($request->type, function ($query, $type) {
                $query->whereHas('store', function ($q) use ($type) {
                    $q->where('store_type_id', $type);
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('CashStores/Index', [
            'cashBalances' => $cashBalances,
            'stores' => Store::all(['id', 'name', 'store_type_id']),
            'storeTypes' => StoreType::all(['id', 'name']),
            'filters' => $request->only(['search', 'type']), // Tambahkan 'type' ke filters
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input dari form
        $request->validate([
            'id'          => 'required|exists:cash_store,id', 
            'store_id'    => 'required|exists:stores,id',
            'cash'        => 'required|numeric|min:0',
            'action_type' => 'required|in:add,subtract,reset'
        ]);

        $cashStore = CashStore::findOrFail($request->id);
        
        // Logika Kalkulasi Berdasarkan action_type
        $currentCash = $cashStore->cash;
        $inputAmount = $request->cash;
        $finalCash = $currentCash;

        $label = "Menambah Kas Toko ";

        if ($request->action_type === 'add') {
            $finalCash = $currentCash + $inputAmount;
        } elseif ($request->action_type === 'subtract') {
            $label = "Mengurangi Kas Toko ";
            $finalCash = $currentCash - $inputAmount;
        } elseif ($request->action_type === 'reset') {
            $finalCash = 0;        
            $label = "Mengeset Kas Toko ";
        }

        // Simpan hasil kalkulasi ke database
        $cashStore->update([
            'cash' => $finalCash,
            'created_by' => auth()->user()->posUser->id,
        ]);

        $statusLabel = [
            'add' => 'ditambahkan',
            'subtract' => 'dikurangi',
            'reset' => 'direset ke 0'
        ];
        $store_name = Store::where('id', $request->store_id)->first()->name;
        // TODO : connect created by
        ActivityLogger::log('update', 'cash_store', $cashStore->id, $label . $store_name, auth()->user()->posUser->id);
        return back()->with('message', "Saldo kas berhasil {$statusLabel[$request->action_type]}!");
    }

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