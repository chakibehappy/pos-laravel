<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Store;
use App\Models\PosUser;
use App\Models\StoreProduct;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\DigitalWalletStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['details.product', 'details.cashWithdrawal', 'details.topupTransaction'])
            ->join('stores', 'transactions.store_id', '=', 'stores.id')
            ->join('pos_users', 'transactions.pos_user_id', '=', 'pos_users.id')
            ->leftJoin('payment_methods', 'transactions.payment_id', '=', 'payment_methods.id')
            ->select(
                'transactions.*',
                'stores.name as store_name',
                'pos_users.name as cashier_name',
                'payment_methods.name as payment_name'
            );

        // Default status (Aktif)
        $query->where('transactions.status', 0);

        // --- LOGIKA FILTER ---

        // Filter berdasarkan Toko
        if ($request->filled('store_id')) {
            $query->where('transactions.store_id', $request->store_id);
        }

        // Filter berdasarkan Rentang Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('transactions.transaction_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('transactions.transaction_at', '<=', $request->end_date);
        }

        // Filter Pencarian Universal (Search)
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('stores.name', 'LIKE', "%{$request->search}%")
                    ->orWhere('pos_users.name', 'LIKE', "%{$request->search}%")
                    ->orWhere('payment_methods.name', 'LIKE', "%{$request->search}%")
                    ->orWhere('transactions.id', 'LIKE', "%{$request->search}%");
            });
        }

        // --- LOGIKA SORTING ---
        $sortField = $request->filled('sort') ? $request->sort : 'transaction_at'; 
        $sortDirection = $request->get('direction', 'desc'); 
        
        $sortMapping = [
            'transaction_at' => 'transactions.transaction_at',
            'store_id'       => 'stores.name',
            'pos_user_id'    => 'pos_users.name',
            'payment_id'     => 'payment_methods.name',
            'total'          => 'transactions.total'
        ];

        $orderColumn = $sortMapping[$sortField] ?? 'transactions.transaction_at';
        $query->orderBy($orderColumn, $sortDirection);

        return Inertia::render('Transactions/Index', [
            'transactions' => $query->paginate(10)->withQueryString(),
            // Kirim balik state filter agar input di Vue tetap terisi (UI Konsisten)
            'filters' => $request->only(['search', 'sort', 'direction', 'store_id', 'start_date', 'end_date']),
            'stores' => Store::all(['id', 'name']),
            'pos_users' => PosUser::all(['id', 'name']),
            'products' => Product::all(['id', 'name', 'selling_price as price']),
            'paymentMethods' => PaymentMethod::all(['id', 'name']),
            'store_products' => StoreProduct::all(['store_id', 'product_id', 'stock']),
            'topup_trans_types' => DB::table('topup_trans_type')->select('id', 'name')->get(),
            'digital_wallet_stores' => DigitalWalletStore::with('wallet:id,name')->get(),
            'withdrawal_source_type' => DB::table('withdrawal_source_type')->select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        return $this->processTransaction($request);
    }

    public function update(Request $request, $id)
    {
        return $this->processTransaction($request, $id);
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                // Eager Load detail secara mendalam agar OLD data sangat lengkap
                $transaction = Transaction::with([
                    'details.product', 
                    'details.topupTransaction', 
                    'details.cashWithdrawal'
                ])->findOrFail($id);
                
                // 1. Simpan data LENGKAP sebagai OLD (dalam bentuk array)
                $oldData = $transaction->toArray();

                // 2. Rollback Logic (rest product, reset saldo, reset cash from withdawals)
                $this->rollbackAssets($transaction);

                // 3. Identifikasi Admin Pos User
                $adminEmail = auth()->user()->email;
                $matchPosUser = PosUser::where('username', $adminEmail)->first();
                $adminPosUserId = $matchPosUser ? $matchPosUser->id : null;

                // 4. Update Status ke ARCHIVED (2)
                $transaction->update([
                    'status' => 2,
                    'deleted_at' => now(),
                    'admin_approved_by' => $adminPosUserId
                ]);

                // 5. Catat Activity Log dengan Payload Lengkap
                $storeName = Store::find($transaction->store_id)->name ?? 'Unknown Store';
                ActivityLogger::log(
                    'delete', 
                    'transactions', 
                    $id, 
                    "Membatalkan & mengarsipkan transaksi Toko $storeName", 
                    $adminPosUserId,
                    [
                        'old' => $oldData, 
                        'new' => $transaction->fresh()->toArray() // Ambil status terbaru setelah update
                    ],
                    $transaction->store_id
                );
            });

            return redirect()->back()->with('message', 'Transaksi berhasil diarsipkan.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => 'Gagal menghapus: ' . $e->getMessage()]);
        }
    }

    private function processTransaction(Request $request, $id = null)
    {
        $request->validate([
            'store_id'       => 'required|exists:stores,id',
            'pos_user_id'    => 'required|exists:pos_users,id',
            'payment_id'     => 'required|exists:payment_methods,id',
            'transaction_at' => 'required',
            'subtotal'       => 'required|numeric',
            'total'          => 'required|numeric',
            'details'        => 'required|array|min:1',
            'details.*.type' => 'required|in:produk,topup,tarik_tunai',
        ]);

        $adminEmail = auth()->user()->email;
        $matchPosUser = PosUser::where('username', $adminEmail)->first();
        $automatedCreatedBy = $matchPosUser ? $matchPosUser->id : $request->pos_user_id;

        $calcSubtotal = 0;
        foreach ($request->details as $item) {
            if ($item['type'] !== 'tarik_tunai') {
                $calcSubtotal += $item['subtotal'];
            }
        }
        $calcTotal = $calcSubtotal + ($request->tax ?? 0);

        try {
            $oldData = null;

            $transaction = DB::transaction(function () use ($request, $id, $automatedCreatedBy, $calcSubtotal, $calcTotal, &$oldData) {
                $storeId = $request->store_id;

                if ($id) {
                    // Load data lama BESERTA RELASI untuk payload log 'old'
                    $old = Transaction::with(['details.product', 'details.topupTransaction', 'details.cashWithdrawal'])->findOrFail($id);
                    $oldData = $old->toArray(); 
                    
                    $this->rollbackAssets($old);
                    DB::table('cash_store')->where('store_id', $old->store_id)->decrement('cash', $old->subtotal);
                    $old->details()->delete();
                }

                $transaction = Transaction::updateOrCreate(
                    ['id' => $id],
                    [
                        'store_id'       => $request->store_id,
                        'pos_user_id'    => $request->pos_user_id,
                        'payment_id'     => $request->payment_id,
                        'transaction_at' => $request->transaction_at,
                        'subtotal'       => $calcSubtotal,
                        'tax'            => $request->tax ?? 0,
                        'total'          => $calcTotal,
                        'status'         => 0,
                        'deleted_at'     => null
                    ]
                );

                DB::table('cash_store')->where('store_id', $storeId)->increment('cash', $calcSubtotal);

                foreach ($request->details as $item) {
                    $topupTransId = null;
                    $cashWithId = null;
                    $productId = ($item['type'] === 'produk') ? $item['product_id'] : null;
                    $buyingPrice = 0;
                    $itemSubtotal = ($item['type'] === 'tarik_tunai') ? 0 : $item['subtotal'];

                    if ($item['type'] === 'produk') {
                        $productMaster = Product::find($item['product_id']);
                        if ($productMaster) $buyingPrice = $productMaster->buying_price;

                        $sp = StoreProduct::where('store_id', $storeId)->where('product_id', $item['product_id'])->first();
                        if (!$sp || $sp->stock < $item['quantity']) throw new \Exception("Stok {$item['name']} tidak cukup!");
                        $sp->decrement('stock', $item['quantity']);
                    }

                    if ($item['type'] === 'topup') {
                        $topupTransId = DB::table('topup_transactions')->insertGetId([
                            'store_id'                => $storeId,
                            'digital_wallet_store_id' => $item['meta']['digital_wallet_store_id'],
                            'cust_account_number'     => $item['meta']['target'],
                            'nominal_request'         => $item['meta']['nominal_topup'],
                            'nominal_pay'             => $item['price'],
                            'topup_trans_type_id'     => $item['meta']['topup_trans_type_id'] ?? $item['product_id'],
                            'created_by'              => $automatedCreatedBy,
                            'created_at'              => $request->transaction_at,
                            'updated_at'              => now(),
                        ]);
                        DigitalWalletStore::where('id', $item['meta']['digital_wallet_store_id'])->decrement('balance', $item['meta']['nominal_topup']);
                    }

                    if ($item['type'] === 'tarik_tunai') {
                        // 1. Hitung uang fisik yang sebenarnya keluar dari laci
                        $nominalKotor = $item['meta']['amount']; // misal 200000
                        $feeAdmin     = $item['meta']['fee'];    // misal 3000
                        $uangKeluar   = $nominalKotor - $feeAdmin; // Hasil: 197000

                        $cashWithId = DB::table('cash_withdrawals')->insertGetId([
                            'store_id'             => $storeId,
                            'customer_name'        => $item['meta']['customer_name'],
                            'withdrawal_source_id' => $item['meta']['withdrawal_source_id'],
                            'withdrawal_count'     => $nominalKotor, // SIMPAN 197.000 (Uang Fisik)
                            'admin_fee'            => $feeAdmin,
                            'created_by'           => $automatedCreatedBy,
                            'created_at'           => $request->transaction_at,
                            'updated_at'           => now(),
                        ]);

                        // 2. Kurangi kas toko sejumlah uang fisik yang keluar saja
                        DB::table('cash_store')->where('store_id', $storeId)->decrement('cash', $uangKeluar);
                    }

                    $transaction->details()->create([
                        'product_id'           => $productId,
                        'topup_transaction_id' => $topupTransId,
                        'cash_withdrawal_id'   => $cashWithId,
                        'buying_prices'        => $buyingPrice,
                        // 3. Selling Price tetap catat 200.000 agar Admin tahu nilai transaksinya
                        'selling_prices'       => ($item['type'] === 'tarik_tunai') ? $item['meta']['amount'] : $item['price'],
                        'quantity'             => ($item['type'] === 'produk') ? $item['quantity'] : 1,
                        'subtotal'             => $itemSubtotal, // Tetap 0
                        'created_by'           => $automatedCreatedBy
                    ]);
                }
                return $transaction;
            });

            // Refresh & Eager Load rincian agar 'new' payload lengkap
            $transaction->load(['details.product', 'details.topupTransaction', 'details.cashWithdrawal']);

            ActivityLogger::log(
                $id ? "update" : "create", 
                'transactions', 
                $transaction->id, 
                ($id ? "Memperbarui" : "Mencatat") . " transaksi di Toko: " . Store::find($request->store_id)->name, 
                auth()->user()->posUser->id ?? $automatedCreatedBy,
                [
                    'old' => $oldData, 
                    'new' => $transaction->toArray() // Sekarang mencakup key 'details'
                ],
                $transaction->store_id
            );

            return redirect()->route('transactions.index')->with('message', 'Transaksi Berhasil Disimpan!');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    private function rollbackAssets($transaction)
    {
        // Gunakan load jika details belum dimuat
        if (!$transaction->relationLoaded('details')) {
            $transaction->load('details');
        }

        foreach ($transaction->details as $detail) {
            if ($detail->product_id) {
                StoreProduct::where('store_id', $transaction->store_id)
                    ->where('product_id', $detail->product_id)
                    ->increment('stock', $detail->quantity);

                // Rollback saldo kas utama toko
                DB::table('cash_store')->where('store_id', $transaction->store_id)
                    ->decrement('cash', $detail->subtotal);
            }

            if ($detail->topup_transaction_id) {
                $topup = DB::table('topup_transactions')->where('id', $detail->topup_transaction_id)->first();
                if ($topup) {
                    DigitalWalletStore::where('id', $topup->digital_wallet_store_id)
                        ->increment('balance', $topup->nominal_request);
                    DB::table('topup_transactions')->where('id', $topup->id)->delete();
                }
            }

            if ($detail->cash_withdrawal_id) {
                $withdraw = DB::table('cash_withdrawals')->where('id', $detail->cash_withdrawal_id)->first();
                if ($withdraw) {
                    // Ambil dari withdrawal_count (yang nilainya 197k)
                    // NOW
                    DB::table('cash_store')->where('store_id', $transaction->store_id)
                        ->increment('cash', $withdraw->withdrawal_count - $withdraw->admin_fee);
                    
                    DB::table('cash_withdrawals')->where('id', $withdraw->id)->delete();
                }
            }
        }
    }
}