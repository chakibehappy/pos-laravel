<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Store;
use App\Models\PosUser;
use App\Models\Product;
use App\Models\StoreProduct; 
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::with(['details.product'])
            ->join('stores', 'transactions.store_id', '=', 'stores.id')
            ->join('pos_users', 'transactions.pos_user_id', '=', 'pos_users.id')
            ->leftJoin('payment_methods', 'transactions.payment_id', '=', 'payment_methods.id')
            ->select(
                'transactions.*', 
                'stores.name as store_name', 
                'pos_users.name as cashier_name',
                'payment_methods.name as payment_name'
            )
            ->latest('transactions.created_at')
            ->paginate(10);

        $transactions->getCollection()->transform(function ($transaction) {
            foreach ($transaction->details as $detail) {
                $detail->price = $detail->selling_prices; 
                if ($detail->product) {
                    $detail->product->price = $detail->product->selling_price;
                }
            }
            return $transaction;
        });

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'stores' => Store::all(['id', 'name']),
            'pos_users' => PosUser::all(['id', 'name']),
            'products' => Product::all(['id', 'name', 'selling_price as price', 'buying_price']), 
            'paymentMethods' => PaymentMethod::all(['id', 'name']),
            'store_products' => StoreProduct::all(['store_id', 'product_id', 'stock']),
        ]);
    }

    /**
     * Menangani proses Simpan (Baru)
     */
    public function store(Request $request)
    {
        return $this->processTransaction($request);
    }

    /**
     * Menangani proses Update (Edit)
     * Ini akan dipanggil saat form.id ada
     */
    public function update(Request $request, $id)
    {
        // Pastikan ID masuk ke request agar logika processTransaction jalan
        $request->merge(['id' => $id]);
        return $this->processTransaction($request);
    }

    /**
     * Logika Inti Transaksi (Reusable untuk Store & Update)
     */
    protected function processTransaction(Request $request)
    {
        $request->validate([
            'store_id'             => 'required|exists:stores,id',
            'pos_user_id'          => 'required|exists:pos_users,id',
            'payment_id'           => 'required|exists:payment_methods,id',
            'transaction_at'       => 'required',
            'subtotal'             => 'required|numeric',
            'tax'                  => 'required|numeric',
            'total'                => 'required|numeric',
            'details'              => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity'   => 'required|numeric|min:1',
            'details.*.price'      => 'required|numeric', 
            'details.*.subtotal'   => 'required|numeric',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $storeId = $request->store_id;

                // 1. JIKA EDIT: Kembalikan stok lama sebelum diupdate
                if ($request->id) {
                    $oldTransaction = Transaction::findOrFail($request->id);
                    $oldDetails = TransactionDetail::where('transaction_id', $request->id)->get();
                    foreach ($oldDetails as $oldItem) {
                        StoreProduct::where('store_id', $oldTransaction->store_id)
                            ->where('product_id', $oldItem->product_id)
                            ->increment('stock', $oldItem->quantity);
                    }
                }

                // 2. Simpan Header (Update atau Create)
                $transaction = Transaction::updateOrCreate(
                    ['id' => $request->id],
                    $request->only(['store_id', 'pos_user_id', 'payment_id', 'transaction_at', 'subtotal', 'tax', 'total'])
                );

                // 3. Bersihkan detail lama
                $transaction->details()->delete();

                // 4. Simpan detail baru & Kurangi stok
                foreach ($request->details as $item) {
                    $storeProduct = StoreProduct::where('store_id', $storeId)
                        ->where('product_id', $item['product_id'])
                        ->lockForUpdate()
                        ->first();

                    if (!$storeProduct || $storeProduct->stock < $item['quantity']) {
                        $pName = Product::find($item['product_id'])->name;
                        throw new \Exception("Stok barang '{$pName}' tidak cukup di toko terpilih!");
                    }

                    $originalProduct = Product::find($item['product_id']);

                    $transaction->details()->create([
                        'product_id'     => $item['product_id'],
                        'quantity'       => $item['quantity'],
                        'buying_prices'  => $originalProduct->buying_price, 
                        'selling_prices' => $item['price'],
                        'subtotal'       => $item['subtotal'],
                    ]);

                    $storeProduct->decrement('stock', $item['quantity']);
                }
            });

            return back()->with('message', 'Transaksi berhasil disimpan!');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $transaction = Transaction::findOrFail($id);
        try {
            DB::transaction(function () use ($transaction) {
                foreach ($transaction->details as $detail) {
                    StoreProduct::where('store_id', $transaction->store_id)
                        ->where('product_id', $detail->product_id)
                        ->increment('stock', $detail->quantity);
                }
                $transaction->delete();
            });
            return back()->with('message', 'Transaksi dihapus!');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Gagal menghapus transaksi.']);
        }
    }
}