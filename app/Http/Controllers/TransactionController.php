<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Store;
use App\Models\PosUser;
use App\Models\Product;
use App\Models\PaymentMethod; // Import model baru
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index()
    {
        return Inertia::render('Transactions/Index', [
            'transactions' => Transaction::with(['details.product'])
                ->join('stores', 'transactions.store_id', '=', 'stores.id')
                ->join('pos_users', 'transactions.pos_user_id', '=', 'pos_users.id')
                // Tambahkan leftJoin agar transaksi yang belum punya metode bayar tetap tampil
                ->leftJoin('payment_methods', 'transactions.payment_id', '=', 'payment_methods.id')
                ->select(
                    'transactions.*', 
                    'stores.name as store_name', 
                    'pos_users.name as cashier_name',
                    'payment_methods.name as payment_name' // Ambil nama metode bayar
                )
                ->latest('transactions.created_at')
                ->paginate(10),
            
            'stores' => Store::all(['id', 'name']),
            'pos_users' => PosUser::all(['id', 'name']),
            'products' => Product::all(['id', 'name', 'price']),
            'paymentMethods' => PaymentMethod::all(['id', 'name']), // Kirim daftar metode bayar ke Vue
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id'             => 'nullable|numeric',
            'store_id'       => 'required|exists:stores,id',
            'pos_user_id'    => 'required|exists:pos_users,id',
            'payment_id'     => 'required|exists:payment_methods,id', // Validasi metode bayar wajib diisi
            'transaction_at' => 'required|date',
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
            // 1. Simpan Header (Sertakan payment_id)
            $transaction = Transaction::updateOrCreate(
                ['id' => $request->id],
                $request->only(['store_id', 'pos_user_id', 'payment_id', 'transaction_at', 'subtotal', 'tax', 'total'])
            );

            // 2. Jika ini UPDATE, hapus detail lama
            if ($request->id) {
                $transaction->details()->delete();
            }

            // 3. Simpan Detail baru
            $transaction->details()->createMany($request->details);
        });

        return back()->with('message', 'Transaction and details saved!');
    }

    public function destroy($id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->details()->delete(); 
        $transaction->delete();

        return back()->with('message', 'Transaction deleted!');
    }
}