<?php

namespace App\Http\Controllers;

use App\Models\CashStore;
use App\Models\Store;
use App\Models\StoreType;
use App\Models\PaymentMethod; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;
use Carbon\Carbon;

class CashStoreController extends Controller
{
    public function index(Request $request)
    {
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

        $query = CashStore::with('store');

        if ($request->filled('search')) {
            $query->whereHas('store', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('type')) {
            $query->whereHas('store', function ($q) use ($request) {
                $q->where('store_type_id', $request->type);
            });
        }

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

        $cashBalancesResult = $query->paginate(10)->withQueryString();
        $paymentMethods = PaymentMethod::where('status', 0)->get(['id', 'name']);

        // MENAMPILKAN TRANSAKSI YANG TERJADI HANYA SETELAH KAS DI-RESET / DISET KAS AWAL
        $transactionBalances = [];
        
        foreach ($cashBalancesResult as $cashStore) {
            $balances = DB::table('transactions')
                ->where('status', 0)
                ->where('store_id', $cashStore->store_id)
                ->where('created_at', '>=', $cashStore->updated_at)
                ->select('payment_id', DB::raw('SUM(subtotal) as total_amount'))
                ->groupBy('payment_id')
                ->get()
                ->pluck('total_amount', 'payment_id');

            $transactionBalances[$cashStore->store_id] = $balances;
        }

        return Inertia::render('CashStores/Index', [
            'cashBalances' => $cashBalancesResult,
            'stores' => Store::all(['id', 'name', 'store_type_id']),
            'storeTypes' => StoreType::all(['id', 'name']),
            'paymentMethods' => $paymentMethods, 
            'transactionBalances' => (object)$transactionBalances,
            'filters' => $request->only(['search', 'type', 'sort', 'direction']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id'               => 'required|exists:cash_store,id', 
            'store_id'         => 'required|exists:stores,id',
            'action_type'      => 'required|in:add,subtract,reset,set_initial,reset_local',
            'cash_amounts'     => 'nullable|array', 
            'initial_cash'     => 'nullable|numeric|min:0',
            'target_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        $cashStore = CashStore::findOrFail($request->id);
        $oldData = $cashStore->getRawOriginal();

        $currentCash = (float) $cashStore->cash;
        $finalCash = $currentCash;
        $operatorId = auth()->user()->posUser->id;
        
        if ($request->action_type === 'set_initial') {
            $inputAmount = (float) $request->initial_cash;
        } else {
            $inputAmount = $request->filled('cash_amounts') ? (float) array_sum($request->cash_amounts) : 0;
        }

        $label = "Menambah Kas Toko ";

        if ($request->action_type === 'add') {
            $finalCash = $currentCash + $inputAmount;
            $cashStore->timestamps = false;

        } elseif ($request->action_type === 'subtract') {
            $label = "Mengurangi Kas Toko ";
            $finalCash = $currentCash - $inputAmount;
            $cashStore->timestamps = false;

        } elseif ($request->action_type === 'reset_local') {
            $label = "Mereset Lokal Metode Pembayaran ";
            
            if ($request->filled('target_method_id')) {
                $method = PaymentMethod::find($request->target_method_id);
                if ($method) {
                    $isTunai = strtolower($method->name) === 'tunai';
                    
                    $currentMethodBalance = 0;
                    if ($isTunai) {
                        $nonTunaiTotal = DB::table('transactions')
                            ->where('status', 0)
                            ->where('store_id', $request->store_id)
                            ->where('created_at', '>=', $cashStore->updated_at)
                            ->where('payment_id', '!=', $method->id)
                            ->sum('subtotal');
                        $currentMethodBalance = max(0, $currentCash - $nonTunaiTotal);
                    } else {
                        $currentMethodBalance = DB::table('transactions')
                            ->where('status', 0)
                            ->where('store_id', $request->store_id)
                            ->where('created_at', '>=', $cashStore->updated_at)
                            ->where('payment_id', $method->id)
                            ->sum('subtotal');
                    }

                    $inputAmount = (float)$currentMethodBalance;
                    $finalCash = max(0, $currentCash - $inputAmount);
                }
            }

            $cashStore->timestamps = false;

        } elseif ($request->action_type === 'reset') {
            $finalCash = 0;        
            $label = "Mengeset Kas Toko ";
            $inputAmount = $currentCash; 
            $cashStore->timestamps = true;

        } elseif ($request->action_type === 'set_initial') {
            $finalCash = $inputAmount; 
            $label = "Mengeset Kas Awal Toko ";
            $cashStore->timestamps = true;
        }

        // Simpan perubahan ke kas global akumulasi
        $cashStore->update([
            'cash' => max(0, $finalCash), 
            'created_by' => $operatorId,
        ]);

        $methodName = '';
        if ($request->filled('target_method_id')) {
            $method = PaymentMethod::find($request->target_method_id);
            if ($method) {
                $methodName = " via " . $method->name;
            }
        }

        $statusLabel = [
            'add' => 'ditambahkan',
            'subtract' => 'dikurangi',
            'reset' => 'direset ke 0',
            'reset_local' => 'direset ke 0 secara lokal',
            'set_initial' => 'diatur sebagai kas awal'
        ];
        
        $store = Store::find($request->store_id);
        $store_name = $store ? $store->name : 'Unknown Store';
        
        ActivityLogger::log(
            'update', 
            'cash_store', 
            $cashStore->id, 
            $label . $store_name . $methodName . " sebesar Rp " . number_format($inputAmount, 0, ',', '.'), 
            $operatorId,
            ['old' => $oldData, 'new' => $cashStore->getAttributes()], 
            $request->store_id
        );
        
        return back()->with('message', "Saldo kas berhasil {$statusLabel[$request->action_type]}!");
    }

    public function destroy($id)
    {
        try {
            $cash = CashStore::findOrFail($id);
            $operatorId = auth()->user()->posUser->id;
            $oldData = $cash->getRawOriginal();

            ActivityLogger::log(
                'delete', 
                'cash_store', 
                $id, 
                "Menghapus record kas toko ID: " . $cash->store_id, 
                $operatorId,
                ['old' => $oldData, 'new' => null],
                $cash->store_id
            );

            $cash->delete();
            return back()->with('message', 'Data kas berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus data kas.']);
        }
    }
}