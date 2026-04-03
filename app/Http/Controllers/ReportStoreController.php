<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportStoreController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil data store untuk dropdown filter (Hanya yang aktif & tidak terhapus)
        $stores = Store::where('status', '!=', 2)
            ->whereNull('deleted_at')
            ->get(['id', 'name']);

        // 2. Ambil daftar Kategori Produk secara dinamis (Warna Merah di Vue)
        $productCategories = DB::table('product_categories')
            ->where('status', '!=', 2)
            ->whereNull('deleted_at')
            ->get(['id', 'name']);

        // 3. Ambil daftar Wallet secara dinamis (Warna Biru di Vue)
        $dynamicWallets = DB::table('digital_wallet')
            ->where('status', '!=', 2)
            ->whereNull('deleted_at')
            ->get(['id', 'name']);

        // 4. Ambil data report utama
        $reportData = Store::query()
            ->select([
                'stores.id',
                'stores.name as nama_cabang',
                
                // SUBQUERY UNTUK MAPPING QTY:
                // Jika Topup/Tarik Tunai = 1, Jika Fisik = Nilai Kolom quantity
                DB::raw("(
                    SELECT SUM(
                        CASE 
                            WHEN td.topup_transaction_id IS NOT NULL OR td.cash_withdrawal_id IS NOT NULL THEN 1
                            ELSE td.quantity 
                        END
                    )
                    FROM transactions t
                    JOIN transaction_details td ON t.id = td.transaction_id
                    WHERE t.store_id = stores.id
                    AND t.status = 0               /* Hanya transaksi aktif */
                    AND t.deleted_at IS NULL       /* Soft Delete Transaksi */
                    AND td.deleted_at IS NULL      /* Soft Delete Detail */
                    " . $this->applyDateFilter($request) . "
                ) as qty"),
                
                // SUBQUERY UNTUK TOTAL:
                // Diambil dari SUM subtotal di tabel transaction_details
                DB::raw("(
                    SELECT SUM(td.subtotal)
                    FROM transactions t
                    JOIN transaction_details td ON t.id = td.transaction_id
                    WHERE t.store_id = stores.id
                    AND t.status = 0               /* Hanya transaksi aktif */
                    AND t.deleted_at IS NULL       /* Soft Delete Transaksi */
                    AND td.deleted_at IS NULL      /* Soft Delete Detail */
                    " . $this->applyDateFilter($request) . "
                ) as total"),

                // Placeholder untuk laba (Akan diisi pada tahap berikutnya)
                DB::raw("0 as laba_kotor"),
                DB::raw("0 as laba_bersih")
            ])
            ->where('stores.status', '!=', 2)
            ->whereNull('stores.deleted_at')
            ->when($request->store_id, function ($query, $storeId) {
                $query->where('stores.id', $storeId);
            })
            ->get();

        // 5. Normalisasi data agar key kategori/wallet tersedia (mencegah error undefined di Vue)
        $reportData->transform(function ($item) use ($productCategories, $dynamicWallets) {
            // Pastikan angka adalah numeric agar formatNumber di Vue tidak error
            $item->qty = (float) ($item->qty ?? 0);
            $item->total = (float) ($item->total ?? 0);

            foreach ($productCategories as $cat) {
                $key = strtolower($cat->name);
                $item->$key = $item->$key ?? 0; 
            }
            foreach ($dynamicWallets as $wallet) {
                $key = strtolower($wallet->name);
                $item->$key = $item->$key ?? 0;
            }
            return $item;
        });

        return Inertia::render('ReportStores/Index', [
            'stores' => $stores,
            'productCategories' => $productCategories,
            'dynamicWallets' => $dynamicWallets,
            'filters' => $request->only(['store_id', 'start_date', 'end_date']),
            'reportData' => $reportData,
        ]);
    }

    /**
     * Helper untuk injeksi filter tanggal ke dalam Raw Subquery
     */
    private function applyDateFilter($request)
    {
        $sql = "";
        // Gunakan parameter binding jika memungkinkan untuk keamanan, 
        // namun untuk raw string pastikan input sudah tervalidasi.
        if ($request->start_date) {
            $sql .= " AND t.transaction_at >= '{$request->start_date} 00:00:00'";
        }
        if ($request->end_date) {
            $sql .= " AND t.transaction_at <= '{$request->end_date} 23:59:59'";
        }
        return $sql;
    }
}