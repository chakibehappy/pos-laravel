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
        $stores = Store::where('status', '!=', 2)
            ->whereNull('deleted_at')
            ->get(['id', 'name']);
        $productCategories = DB::table('product_categories')
            ->where('status', '!=', 2)
            ->whereNull('deleted_at')
            ->get(['id', 'name']);

        // 3. Ambil daftar Wallet secara dinamis (Warna Biru di Vue)
        $dynamicWallets = DB::table('digital_wallet')
            ->where('status', '!=', 2)
            ->whereNull('deleted_at')
            ->get(['id', 'name']);
        $reportData = Store::query()
            ->select([
                'stores.id',
                'stores.name as nama_cabang',
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
                DB::raw("0 as laba_kotor"),
                DB::raw("0 as laba_bersih")
            ])
            ->where('stores.status', '!=', 2)
            ->whereNull('stores.deleted_at')
            ->when($request->store_id, function ($query, $storeId) {
                $query->where('stores.id', $storeId);
            })
            ->get();
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
    private function applyDateFilter($request)
    {
        $sql = "";
        if ($request->start_date) {
            $sql .= " AND t.transaction_at >= '{$request->start_date} 00:00:00'";
        }
        if ($request->end_date) {
            $sql .= " AND t.transaction_at <= '{$request->end_date} 23:59:59'";
        }
        return $sql;
    }
}