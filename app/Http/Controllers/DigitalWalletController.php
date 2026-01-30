<?php

namespace App\Http\Controllers;

use App\Models\DigitalWallet;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DigitalWalletController extends Controller
{
    public function index()
    {
        return Inertia::render('DigitalWallets/Index', [
            'wallets' => DigitalWallet::latest()->get(),
            'total_balance' => (float) DigitalWallet::sum('balance')
        ]);
    }

    /**
     * Store atau Update Data Nama Wallet
     */
    public function store(Request $request)
    {
        $request->validate([
            'id'    => 'nullable|exists:digital_wallet,id',
            'name'  => 'required|string|max:100',
            'balance' => 'required|numeric|min:0' // Saldo awal saat buat baru
        ]);

        DigitalWallet::updateOrCreate(
            ['id' => $request->id],
            ['name' => $request->name, 'balance' => $request->balance]
        );

        return back()->with('message', 'Wallet berhasil disimpan!');
    }

    /**
     * Update Saldo (Top Up / Penyesuaian)
     */
    public function updateBalance(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|in:add,set'
        ]);

        $wallet = DigitalWallet::findOrFail($id);

        if ($request->type === 'add') {
            $wallet->balance += $request->amount;
        } else {
            $wallet->balance = $request->amount;
        }

        $wallet->save();

        return back()->with('message', 'Saldo berhasil diperbarui!');
    }

    public function destroy($id)
    {
        DigitalWallet::findOrFail($id)->delete();
        return back()->with('message', 'Wallet berhasil dihapus!');
    }
}