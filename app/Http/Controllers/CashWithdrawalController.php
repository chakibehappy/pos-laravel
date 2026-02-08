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
     * Menampilkan daftar transaksi tarik tunai dengan Paginasi & Search.
     */
    public function index(Request $request)
    {
        $withdrawals = CashWithdrawal::with(['store'])
            ->when($request->search, function ($query, $search) {
                $query->where('customer_name', 'like', "%{$search}%")
                      ->orWhereHas('store', function ($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('CashWithdrawals/Index', [
            'withdrawals' => $withdrawals,
            'stores'      => Store::all(['id', 'name', 'store_type_id']),
            'storeTypes'  => StoreType::all(['id', 'name']),
            'filters'     => $request->only(['search']),
        ]);
    }

    /**
     * Menyimpan transaksi tarik tunai baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'store_id'             => 'required|exists:stores,id',
            'customer_name'        => 'required|string|max:255',
            'withdrawal_source_id' => 'required|integer', 
            'withdrawal_count'     => 'required|numeric|min:1',
            'admin_fee'            => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // 1. Cek ketersediaan kas di toko tersebut
            $cashStore = CashStore::where('store_id', $request->store_id)->lockForUpdate()->first();

            if (!$cashStore || $cashStore->cash < $request->withdrawal_count) {
                return back()->withErrors(['error' => 'Gagal! Saldo kas tunai di toko ini tidak mencukupi.']);
            }

            // 2. Buat record transaksi
            CashWithdrawal::create([
                'store_id'             => $request->store_id,
                'customer_name'        => $request->customer_name,
                'withdrawal_source_id' => $request->withdrawal_source_id,
                'withdrawal_count'     => $request->withdrawal_count,
                'admin_fee'            => $request->admin_fee,
            ]);

            // 3. Potong saldo kas fisik di toko
            $cashStore->decrement('cash', $request->withdrawal_count);

            DB::commit();
            return back()->with('message', 'Transaksi tarik tunai berhasil dicatat.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Update data transaksi (Semua Item & Penyesuaian Saldo).
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'store_id'             => 'required|exists:stores,id',
            'customer_name'        => 'required|string|max:255',
            'withdrawal_source_id' => 'required|integer',
            'withdrawal_count'     => 'required|numeric|min:1',
            'admin_fee'            => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $withdrawal = CashWithdrawal::findOrFail($id);
            
            // 1. KEMBALIKAN saldo lama ke toko asal terlebih dahulu (Reset State)
            $oldCashStore = CashStore::where('store_id', $withdrawal->store_id)->first();
            if ($oldCashStore) {
                $oldCashStore->increment('cash', $withdrawal->withdrawal_count);
            }

            // 2. CEK ketersediaan kas di toko yang baru (atau toko yang sama setelah di-reset)
            $newCashStore = CashStore::where('store_id', $request->store_id)->lockForUpdate()->first();

            if (!$newCashStore || $newCashStore->cash < $request->withdrawal_count) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Gagal! Saldo kas di toko tujuan tidak mencukupi untuk perubahan ini.']);
            }

            // 3. UPDATE record transaksi
            $withdrawal->update([
                'store_id'             => $request->store_id,
                'customer_name'        => $request->customer_name,
                'withdrawal_source_id' => $request->withdrawal_source_id,
                'withdrawal_count'     => $request->withdrawal_count,
                'admin_fee'            => $request->admin_fee,
            ]);

            // 4. POTONG saldo kas di toko yang baru sesuai nominal baru
            $newCashStore->decrement('cash', $request->withdrawal_count);

            DB::commit();
            return back()->with('message', 'Data transaksi berhasil diperbarui dan saldo kas telah disesuaikan.');

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
            
            $cashStore = CashStore::where('store_id', $withdrawal->store_id)->lockForUpdate()->first();
            
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