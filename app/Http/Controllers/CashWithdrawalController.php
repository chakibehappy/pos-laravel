<?php

namespace App\Http\Controllers;

use App\Models\CashWithdrawal;
use App\Models\CashStore;
use App\Models\Store;
use App\Models\StoreType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CashWithdrawalController extends Controller
{
    /**
     * Menampilkan daftar transaksi tarik tunai dengan Paginasi, Search, Filter Tanggal & Toko.
     */
    public function index(Request $request)
    {
        // Perbaikan: Gunakan filled() untuk memastikan sort tidak null/kosong saat pencarian dihapus
        $sortField = $request->filled('sort') ? $request->sort : 'created_at'; 
        $sortDirection = $request->input('direction', 'desc'); 

        // Mapping untuk kolom sorting agar SQL tidak bingung saat menerima key dari front-end
        $sortMapping = [
            'customer_name'    => 'customer_name',
            'withdrawal_count' => 'withdrawal_count',
            'admin_fee'        => 'admin_fee',
            'created_at'       => 'created_at',
            'store_id'         => 'store_id'
        ];

        $orderColumn = $sortMapping[$sortField] ?? 'created_at';

        // Query Utama
        $withdrawals = CashWithdrawal::with(['store'])
            // Filter Search (Nama Pelanggan atau Nama Toko)
            ->when($request->search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('customer_name', 'like', "%{$search}%")
                      ->orWhereHas('store', function ($sq) use ($search) {
                          $sq->where('name', 'like', "%{$search}%");
                      });
                });
            })
            // Filter Berdasarkan Toko (store_id)
            ->when($request->store_id, function ($query, $storeId) {
                $query->where('store_id', $storeId);
            })
            // Filter Berdasarkan Rentang Tanggal
            ->when($request->start_date, function ($query, $startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($request->end_date, function ($query, $endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            })
            // Logika Sorting dengan fallback ke orderColumn yang tervalidasi
            ->orderBy($orderColumn, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('CashWithdrawals/Index', [
            'withdrawals' => $withdrawals,
            'stores'      => Store::all(['id', 'name', 'store_type_id']),
            'storeTypes'  => StoreType::all(['id', 'name']),
            'filters'     => $request->only(['search', 'sort', 'direction', 'store_id', 'start_date', 'end_date']),
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

            // HITUNG NOMINAL BERSIH (Uang yang keluar dari laci)
            $netAmount = $request->withdrawal_count - $request->admin_fee;

            $cashStore = CashStore::where('store_id', $request->store_id)->lockForUpdate()->first();

            // Cek menggunakan netAmount
            if (!$cashStore || $cashStore->cash < $netAmount) {
                return back()->withErrors(['error' => 'Gagal! Saldo kas tidak mencukupi untuk uang keluar bersih Rp ' . number_format($netAmount)]);
            }

            CashWithdrawal::create([
                // ... field lain tetap sama ...
                'withdrawal_count' => $request->withdrawal_count,
                'admin_fee'        => $request->admin_fee,
                'created_by'       => Auth::id(),
                'status'           => 0,
            ]);

            // POTONG SEJUMLAH NET AMOUNT
            $cashStore->decrement('cash', $netAmount);

            DB::commit();
            return back()->with('message', 'Transaksi berhasil.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Update data transaksi & Penyesuaian Saldo Otomatis.
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
            
            // 1. KEMBALIKAN saldo lama (Net Lama)
            $oldNet = $withdrawal->withdrawal_count - $withdrawal->admin_fee;
            $oldCashStore = CashStore::where('store_id', $withdrawal->store_id)->lockForUpdate()->first();
            if ($oldCashStore) {
                $oldCashStore->increment('cash', $oldNet);
            }

            // 2. CEK saldo baru (Net Baru)
            $newNet = $request->withdrawal_count - $request->admin_fee;
            $newCashStore = CashStore::where('store_id', $request->store_id)->lockForUpdate()->first();

            if (!$newCashStore || $newCashStore->cash < $newNet) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Saldo tidak mencukupi untuk nominal baru.']);
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
            $newCashStore->decrement('cash', $newNet);

            DB::commit();
            return back()->with('message', 'Data transaksi berhasil diperbarui.');

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
            $netAmount = $withdrawal->withdrawal_count - $withdrawal->admin_fee;

            $cashStore = CashStore::where('store_id', $withdrawal->store_id)->lockForUpdate()->first();
            if ($cashStore) {
                $cashStore->increment('cash', $netAmount); // Kembalikan uang bersihnya
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