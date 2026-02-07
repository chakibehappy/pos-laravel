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

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('stores.name', 'LIKE', "%{$request->search}%")
                    ->orWhere('pos_users.name', 'LIKE', "%{$request->search}%")
                    ->orWhere('transactions.id', 'LIKE', "%{$request->search}%");
            });
        }

        return Inertia::render('Transactions/Index', [
            'transactions' => $query->latest('transactions.created_at')->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
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

    /**
     * Store menggunakan POST /transactions
     */
    public function store(Request $request)
    {
        return $this->processTransaction($request);
    }

    /**
     * Update menggunakan POST /transactions/{id}
     */
    public function update(Request $request, $id)
    {
        return $this->processTransaction($request, $id);
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $transaction = Transaction::with('details')->findOrFail($id);

                // 1. Rollback Stok, Saldo Wallet, dan Kas (Tarik Tunai)
                $this->rollbackAssets($transaction);

                // 2. Rollback saldo kas utama (uang masuk dari subtotal transaksi)
                DB::table('cash_store')
                    ->where('store_id', $transaction->store_id)
                    ->decrement('cash', $transaction->subtotal);

                // 3. Hapus data
                $transaction->details()->delete();
                $transaction->delete();
            });

            return redirect()->back()->with('message', 'Transaksi berhasil dihapus dan aset telah dikembalikan.');
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
            // Hanya produk dan topup yang menambah subtotal (kas masuk)
            if ($item['type'] !== 'tarik_tunai') {
                $calcSubtotal += $item['subtotal'];
            }
        }
        $calcTotal = $calcSubtotal + ($request->tax ?? 0);

        try {
            DB::transaction(function () use ($request, $id, $automatedCreatedBy, $calcSubtotal, $calcTotal) {
                $storeId = $request->store_id;

                // LOGIKA EDIT: Rollback data lama sebelum ditimpa data baru
                if ($id) {
                    $old = Transaction::with('details')->findOrFail($id);
                    $this->rollbackAssets($old);
                    // Kembalikan saldo kas toko dari subtotal lama
                    DB::table('cash_store')->where('store_id', $old->store_id)->decrement('cash', $old->subtotal);
                    // Hapus detail lama
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
                    ]
                );

                // Tambahkan kas masuk ke toko
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
                            'topup_trans_type_id'     => $item['product_id'],
                            'created_by'              => $automatedCreatedBy,
                            'created_at'              => $request->transaction_at,
                            'updated_at'              => now(),
                        ]);
                        DigitalWalletStore::where('id', $item['meta']['digital_wallet_store_id'])->decrement('balance', $item['meta']['nominal_topup']);
                    }

                    if ($item['type'] === 'tarik_tunai') {
                        $cashWithId = DB::table('cash_withdrawals')->insertGetId([
                            'store_id'             => $storeId,
                            'customer_name'        => $item['meta']['customer_name'],
                            'withdrawal_source_id' => $item['meta']['withdrawal_source_id'],
                            'withdrawal_count'     => $item['meta']['amount'],
                            'admin_fee'            => $item['meta']['fee'],
                            'created_by'           => $automatedCreatedBy,
                            'created_at'           => $request->transaction_at,
                            'updated_at'           => now(),
                        ]);
                        // Tarik tunai mengurangi kas toko
                        DB::table('cash_store')->where('store_id', $storeId)->decrement('cash', $item['meta']['amount']);
                    }

                    $transaction->details()->create([
                        'product_id'           => $productId,
                        'topup_transaction_id' => $topupTransId,
                        'cash_withdrawal_id'   => $cashWithId,
                        'buying_prices'        => $buyingPrice,
                        'selling_prices'       => $item['price'],
                        'quantity'             => ($item['type'] === 'produk') ? $item['quantity'] : 1,
                        'subtotal'             => $itemSubtotal,
                        'created_by'           => $automatedCreatedBy
                    ]);
                }
            });

            return redirect()->route('transactions.index')->with('message', 'Transaksi Berhasil Disimpan!');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    private function rollbackAssets($transaction)
    {
        foreach ($transaction->details as $detail) {
            // 1. Rollback Stok Produk
            if ($detail->product_id) {
                StoreProduct::where('store_id', $transaction->store_id)
                    ->where('product_id', $detail->product_id)
                    ->increment('stock', $detail->quantity);
            }

            // 2. Rollback Saldo Wallet (Topup)
            if ($detail->topup_transaction_id) {
                $topup = DB::table('topup_transactions')->where('id', $detail->topup_transaction_id)->first();
                if ($topup) {
                    DigitalWalletStore::where('id', $topup->digital_wallet_store_id)
                        ->increment('balance', $topup->nominal_request);
                    DB::table('topup_transactions')->where('id', $topup->id)->delete();
                }
            }

            // 3. Rollback Kas Toko (Tarik Tunai)
            if ($detail->cash_withdrawal_id) {
                $withdraw = DB::table('cash_withdrawals')->where('id', $detail->cash_withdrawal_id)->first();
                if ($withdraw) {
                    // Jika dibatalkan, uang yang tadinya keluar dikembalikan ke kas toko
                    DB::table('cash_store')->where('store_id', $transaction->store_id)
                        ->increment('cash', $withdraw->withdrawal_count);
                    DB::table('cash_withdrawals')->where('id', $withdraw->id)->delete();
                }
            }
        }
    }
}