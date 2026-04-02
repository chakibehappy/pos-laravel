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
        $query = CashStore::with('store');

        // Filter Pencarian
        if ($request->filled('search')) {
            $query->whereHas('store', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            });
        }

        // Filter Tipe Usaha
        if ($request->filled('type')) {
            $query->whereHas('store', function ($q) use ($request) {
                $q->where('store_type_id', $request->type);
            });
        }

        // --- LOGIKA SORTING DINAMIS ---
        $sortField = $request->input('sort');
        $direction = $request->input('direction', 'asc');

        if ($sortField) {
            $tableName = (new CashStore())->getTable();

            switch ($sortField) {
                case 'store_name':
                    $query->join('stores', "$tableName.store_id", '=', 'stores.id')
                          ->orderBy('stores.name', $direction)
                          ->select("$tableName.*");
                    break;
                case 'cash':
                    $query->orderBy('cash', $direction);
                    break;
                case 'updated_at':
                    $query->orderBy('updated_at', $direction);
                    break;
                default:
                    $query->latest();
                    break;
            }
        } else {
            $query->latest();
        }

        $cashBalances = $query->paginate(10)->withQueryString();

        return Inertia::render('CashStores/Index', [
            'cashBalances' => $cashBalances,
            'stores' => Store::all(['id', 'name', 'store_type_id']),
            'storeTypes' => StoreType::all(['id', 'name']),
            'filters' => $request->only(['search', 'type', 'sort', 'direction']),
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
        
        // --- TAMBAHKAN INI: Tangkap data lama sebelum diupdate ---
        $oldData = $cashStore->getRawOriginal();

        // Logika Kalkulasi Berdasarkan action_type
        $currentCash = (float) $cashStore->cash;
        $inputAmount = (float) $request->cash;
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

        $operatorId = auth()->user()->posUser->id;

        // Simpan hasil kalkulasi ke database
        $cashStore->update([
            'cash' => max(0, $finalCash), // Pastikan tidak minus
            'created_by' => $operatorId,
        ]);

        $statusLabel = [
            'add' => 'ditambahkan',
            'subtract' => 'dikurangi',
            'reset' => 'direset ke 0'
        ];
        
        $store = Store::find($request->store_id);
        $store_name = $store ? $store->name : 'Unknown Store';
        
        // LOG ACTIVITY dengan Payload Lengkap
        ActivityLogger::log(
            'update', 
            'cash_store', 
            $cashStore->id, 
            $label . $store_name . " sebesar Rp " . number_format($inputAmount, 0, ',', '.'), 
            $operatorId,
            ['old' => $oldData, 'new' => $cashStore->getAttributes()], 
            $request->store_id// <-- Payload dikirim di sini
        );
        
        return back()->with('message', "Saldo kas berhasil {$statusLabel[$request->action_type]}!");
    }

    public function destroy($id)
    {
        try {
            $cash = CashStore::findOrFail($id);
            $operatorId = auth()->user()->posUser->id;
            
            // Tangkap data lama sebelum dihapus
            $oldData = $cash->getRawOriginal();

            // LOG ACTIVITY sebelum delete
            ActivityLogger::log(
                'delete', 
                'cash_store', 
                $id, 
                "Menghapus record kas toko ID: " . $cash->store_id, 
                $operatorId,
                ['old' => $oldData, 'new' => null],
                $cash->store_id// New null karena data dihapus
            );

            $cash->delete();
            
            return back()->with('message', 'Data kas berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus data kas.']);
        }
    }
}