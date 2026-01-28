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
            'products' => Product::all(['id', 'name', 'selling_price as price']), 
            'paymentMethods' => PaymentMethod::all(['id', 'name']),
        ]);
    }

    // Fungsi Update Baru
    public function update(Request $request, $id)
    {
        // Masukkan ID dari URL ke dalam request agar dibaca oleh updateOrCreate di fungsi store
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
            // updateOrCreate akan mencari berdasarkan ID, jika ada diupdate, jika tidak ada dibuat baru
            $transaction = Transaction::updateOrCreate(
                ['id' => $request->id],
                $request->only(['store_id', 'pos_user_id', 'payment_id', 'transaction_at', 'subtotal', 'tax', 'total'])
            );

            // Bersihkan detail lama (penting saat proses Update)
            $transaction->details()->delete();

            // Simpan detail baru
            $details = collect($request->details)->map(function($item) {
                return [
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'], 
                    'subtotal'   => $item['subtotal'],
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