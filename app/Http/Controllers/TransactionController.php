<?php

namespace App\Http\Controllers;

use App\Models\Transaction;       // INI YANG KURANG
use App\Models\TransactionDetail;
use App\Models\Store;
use App\Models\PosUser;
use App\Models\Product; // Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Untuk Database Transaction
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index()
    {
        return Inertia::render('Transactions/Index', [
            'transactions' => Transaction::with(['details.product']) // Eager load detail dan produknya
                ->join('stores', 'transactions.store_id', '=', 'stores.id')
                ->join('pos_users', 'transactions.pos_user_id', '=', 'pos_users.id')
                ->select(
                    'transactions.*', 
                    'stores.name as store_name', 
                    'pos_users.name as cashier_name'
                )
                ->latest('transactions.created_at')
                ->paginate(10),
            
            'stores' => Store::all(['id', 'name']),
            'pos_users' => PosUser::all(['id', 'name']),
            'products' => Product::all(['id', 'name', 'price']), // KIRIM DATA PRODUK KE VUE
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id'             => 'nullable|numeric',
            'store_id'       => 'required|exists:stores,id',
            'pos_user_id'    => 'required|exists:pos_users,id',
            'transaction_at' => 'required|date',
            'subtotal'       => 'required|numeric',
            'tax'            => 'required|numeric',
            'total'          => 'required|numeric',
            'details'        => 'required|array|min:1', // Pastikan ada barang yang dibeli
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity'   => 'required|numeric|min:1',
            'details.*.price'      => 'required|numeric',
            'details.*.subtotal'   => 'required|numeric',
        ]);

        // Gunakan DB Transaction agar jika detail gagal simpan, header juga batal (aman)
        DB::transaction(function () use ($request) {
            // 1. Simpan Header
            $transaction = Transaction::updateOrCreate(
                ['id' => $request->id],
                $request->only(['store_id', 'pos_user_id', 'transaction_at', 'subtotal', 'tax', 'total'])
            );

            // 2. Jika ini UPDATE, hapus detail lama dulu sebelum ganti yang baru
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
        
        // Hapus detailnya dulu (Penting karena relasi!)
        $transaction->details()->delete(); 
        $transaction->delete();

        return back()->with('message', 'Transaction deleted!');
    }
}