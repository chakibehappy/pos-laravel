<?php

namespace App\Http\Controllers;

use App\Models\CashWithdrawal;
use App\Models\CashStore;
use App\Models\Store;
use App\Models\StoreType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CashWithdrawalController extends Controller
{
    /**
     * Menampilkan daftar transaksi tarik tunai.
     */
    public function index()
    {
        return Inertia::render('CashWithdrawals/Index', [
            'withdrawals' => CashWithdrawal::with(['store'])->latest()->get(),
            'stores' => Store::all(['id', 'name', 'store_type_id']),
            'storeTypes' => StoreType::all(['id', 'name']),
        ]);
    }

    /**
     * Menyimpan transaksi tarik tunai baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'customer_name' => 'required|string|max:255',
            'withdrawal_source_id' => 'required|integer',
            'withdrawal_count' => 'required|numeric|min:1',
            'admin_fee' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $cashStore = CashStore::where('store_id', $request->store_id)->first();

            if (!$cashStore || $cashStore->cash < $request->withdrawal_count) {
                return back()->withErrors(['error' => 'Gagal! Saldo kas tunai di toko ini tidak mencukupi.']);
            }

            CashWithdrawal::create([
                'store_id' => $request->store_id,
                'customer_name' => $request->customer_name,
                'withdrawal_source_id' => $request->withdrawal_source_id,
                'withdrawal_count' => $request->withdrawal_count,
                'admin_fee' => $request->admin_fee,
            ]);

            $cashStore->decrement('cash', $request->withdrawal_count);

            DB::commit();
            return back()->with('message', 'Transaksi tarik tunai berhasil dicatat.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Update data transaksi (Full Edit).
     * Mendukung perubahan nominal dan perpindahan toko dengan sinkronisasi saldo.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'customer_name' => 'required|string|max:255',
            'withdrawal_source_id' => 'required|integer',
            'withdrawal_count' => 'required|numeric|min:1',
            'admin_fee' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $withdrawal = CashWithdrawal::findOrFail($id);
            
            // 1. KEMBALIKAN saldo ke toko asal (sebelum diedit)
            $oldCashStore = CashStore::where('store_id', $withdrawal->store_id)->first();
            if ($oldCashStore) {
                $oldCashStore->increment('cash', $withdrawal->withdrawal_count);
            }

            // 2. AMBIL data kas toko baru (bisa jadi toko yang sama atau berbeda)
            $newCashStore = CashStore::where('store_id', $request->store_id)->first();

            // Cek apakah saldo di toko baru cukup untuk nominal baru
            if (!$newCashStore || $newCashStore->cash < $request->withdrawal_count) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Gagal Update! Saldo di toko terpilih tidak mencukupi.']);
            }

            // 3. POTONG saldo toko baru dengan nominal baru
            $newCashStore->decrement('cash', $request->withdrawal_count);

            // 4. UPDATE data transaksi
            $withdrawal->update([
                'store_id' => $request->store_id,
                'customer_name' => $request->customer_name,
                'withdrawal_source_id' => $request->withdrawal_source_id,
                'withdrawal_count' => $request->withdrawal_count,
                'admin_fee' => $request->admin_fee,
            ]);

            DB::commit();
            return back()->with('message', 'Data transaksi dan saldo kas berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan saat update: ' . $e->getMessage()]);
        }
    }

    /**
     * Menghapus transaksi dan MENGEMBALIKAN saldo ke kas toko.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $withdrawal = CashWithdrawal::findOrFail($id);
            $cashStore = CashStore::where('store_id', $withdrawal->store_id)->first();
            
            if ($cashStore) {
                $cashStore->increment('cash', $withdrawal->withdrawal_count);
            }

            $withdrawal->delete();

            DB::commit();
            return back()->with('message', 'Transaksi dibatalkan dan saldo kas toko telah dikembalikan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal membatalkan transaksi: ' . $e->getMessage()]);
        }
    }
}