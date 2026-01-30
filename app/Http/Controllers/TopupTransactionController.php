<?php

namespace App\Http\Controllers;

use App\Models\TopupTransaction; 
use App\Models\TopupTransType;   
use App\Models\Store;
use App\Models\DigitalWalletStore; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TopupTransactionController extends Controller
{
    /**
     * READ: Menampilkan daftar transaksi dengan relasi lengkap
     */
    public function index()
    {
        return Inertia::render('TopupTransaction/Index', [
            // Mengambil data transaksi beserta relasi
            'transactions' => TopupTransaction::with(['store', 'transType', 'digitalWalletStore'])
                ->latest()
                ->get(),
            
            // FILTER: Hanya memunculkan toko dengan store_type_id = 1 di Combo Box
            'stores' => Store::where('store_type_id', 1)->get(),
            
            'transTypes' => TopupTransType::all(),
            'walletStores' => DigitalWalletStore::all(),
        ]);
    }

    /**
     * CREATE: Menyimpan transaksi baru ke database
     */
    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'cust_account_number' => 'required|string|max:50',
            'nominal_request' => 'required|numeric|min:0',
            'nominal_pay' => 'required|numeric|min:0',
            'digital_wallet_store_id' => 'required|exists:digital_wallet_stores,id',
            'topup_trans_type_id' => 'required|exists:topup_trans_type,id',
        ]);

        // Simpan data ke tabel topup_transactions
        TopupTransaction::create($request->all());

        return back()->with('message', 'Transaksi berhasil dicatat.');
    }

    /**
     * UPDATE: Memperbarui data transaksi yang sudah ada
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'cust_account_number' => 'required|string|max:50',
            'nominal_request' => 'required|numeric|min:0',
            'nominal_pay' => 'required|numeric|min:0',
            'digital_wallet_store_id' => 'required|exists:digital_wallet_stores,id',
            'topup_trans_type_id' => 'required|exists:topup_trans_type,id',
        ]);

        $transaction = TopupTransaction::findOrFail($id);
        $transaction->update($request->all());

        return back()->with('message', 'Transaksi berhasil diperbarui.');
    }

    /**
     * DELETE: Menghapus data transaksi
     */
    public function destroy($id)
    {
        $transaction = TopupTransaction::findOrFail($id);
        $transaction->delete();

        return back()->with('message', 'Riwayat transaksi berhasil dihapus.');
    }
}