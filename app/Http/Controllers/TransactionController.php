<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Store;
use App\Models\PosUser;
use App\Models\Product;
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
            'products' => Product::where('stock', '>', 0)->get(['id', 'name', 'selling_price as price', 'stock']), 
            'paymentMethods' => PaymentMethod::all(['id', 'name']),
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->merge(['id' => $id]);
        return $this->store($request);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id'                   => 'nullable',
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
                // 1. JIKA EDIT: Kembalikan stok lama sebelum data detail lama dihapus
                if ($request->id) {
                    $oldDetails = TransactionDetail::where('transaction_id', $request->id)->get();
                    foreach ($oldDetails as $oldItem) {
                        Product::where('id', $oldItem->product_id)->increment('stock', $oldItem->quantity);
                    }
                }

                // 2. Simpan atau Perbarui Header Transaksi
                $transaction = Transaction::updateOrCreate(
                    ['id' => $request->id],
                    $request->only(['store_id', 'pos_user_id', 'payment_id', 'transaction_at', 'subtotal', 'tax', 'total'])
                );

                // 3. Bersihkan detail lama (untuk case Update)
                $transaction->details()->delete();

                // 4. Proses detail baru & Potong Stok
                foreach ($request->details as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);

                    // Cek apakah stok cukup
                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("Stok produk '{$product->name}' tidak mencukupi (Tersisa: {$product->stock})");
                    }

                    // Buat detail transaksi
                    $transaction->details()->create([
                        'product_id'     => $item['product_id'],
                        'quantity'       => $item['quantity'],
                        'buying_prices'  => $product->buying_price, 
                        'selling_prices' => $item['price'],
                        'subtotal'       => $item['subtotal'],
                    ]);

                    // Potong stok produk
                    $product->decrement('stock', $item['quantity']);
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
        
        DB::transaction(function () use ($transaction) {
            // Kembalikan stok saat transaksi dihapus
            foreach ($transaction->details as $detail) {
                Product::where('id', $detail->product_id)->increment('stock', $detail->quantity);
            }

            $transaction->details()->delete(); 
            $transaction->delete();
        });

        return back()->with('message', 'Transaksi berhasil dihapus dan stok dikembalikan!');
    }
}