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
                // Alias agar Vue tetap menggunakan key 'price' untuk tampilan
                $detail->price = $detail->selling_prices; 
                
                if ($detail->product) {
                    // Menyinkronkan harga produk di dropdown jika diperlukan
                    $detail->product->price = $detail->product->selling_price;
                }
            }
            return $transaction;
        });

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'stores' => Store::all(['id', 'name']),
            'pos_users' => PosUser::all(['id', 'name']),
            // Kirim data product dengan alias price untuk frontend
            'products' => Product::all(['id', 'name', 'selling_price as price']), 
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
            'id'             => 'nullable',
            'store_id'       => 'required|exists:stores,id',
            'pos_user_id'    => 'required|exists:pos_users,id',
            'payment_id'     => 'required|exists:payment_methods,id',
            'transaction_at' => 'required',
            'subtotal'       => 'required|numeric',
            'tax'            => 'required|numeric',
            'total'          => 'required|numeric',
            'details'        => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity'   => 'required|numeric|min:1',
            'details.*.price'      => 'required|numeric', 
            'details.*.subtotal'   => 'required|numeric',
        ]);

        DB::transaction(function () use ($request) {
            $transaction = Transaction::updateOrCreate(
                ['id' => $request->id],
                $request->only(['store_id', 'pos_user_id', 'payment_id', 'transaction_at', 'subtotal', 'tax', 'total'])
            );

            // Hapus detail lama untuk menghindari duplikasi saat Update
            $transaction->details()->delete();

            // Kumpulkan detail dan capture harga modal (buying_price)
            $details = collect($request->details)->map(function($item) {
                // Ambil data produk terbaru dari database untuk mendapatkan buying_price
                $product = Product::find($item['product_id']);
                
                return [
                    'product_id'     => $item['product_id'],
                    'quantity'       => $item['quantity'],
                    'buying_prices'  => $product ? $product->buying_price : 0, // Capture modal
                    'selling_prices' => $item['price'], // Capture harga jual dari form
                    'subtotal'       => $item['subtotal'],
                ];
            })->toArray();

            $transaction->details()->createMany($details);
        });

        return back()->with('message', 'Transaksi berhasil disimpan!');
    }

    public function destroy($id)
    {
        $transaction = Transaction::findOrFail($id);
        
        DB::transaction(function () use ($transaction) {
            $transaction->details()->delete(); 
            $transaction->delete();
        });

        return back()->with('message', 'Transaksi berhasil dihapus!');
    }
}