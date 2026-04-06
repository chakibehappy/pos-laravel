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

        $dynamicWallets = DB::table('digital_wallet')
            ->where('status', '!=', 2)
            ->whereNull('deleted_at')
            ->get(['id', 'name']);

        // 1. Inisialisasi Query Utama
        $reportQuery = Store::query()
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
                    AND t.status = 0
                    AND t.deleted_at IS NULL
                    AND td.deleted_at IS NULL
                    " . $this->applyDateFilter($request) . "
                ) as qty"),
                DB::raw("(
                    SELECT SUM(td.subtotal)
                    FROM transactions t
                    JOIN transaction_details td ON t.id = td.transaction_id
                    WHERE t.store_id = stores.id
                    AND t.status = 0
                    AND t.deleted_at IS NULL
                    AND td.deleted_at IS NULL
                    " . $this->applyDateFilter($request) . "
                ) as total"),

                DB::raw("(
                    SELECT SUM(td.subtotal)
                    FROM transactions t
                    JOIN transaction_details td ON t.id = td.transaction_id
                    WHERE t.store_id = stores.id 
                    AND td.cash_withdrawal_id IS NOT NULL
                    AND t.status = 0 
                    AND t.deleted_at IS NULL 
                    AND td.deleted_at IS NULL
                    " . $this->applyDateFilter($request) . "
                ) as tarik_tunai_jual"),

                DB::raw("(
                    SELECT SUM(td.buying_prices) -- LANGSUNG AMBIL DARI KOLOM TANPA * QTY
                    FROM transactions t
                    JOIN transaction_details td ON t.id = td.transaction_id
                    WHERE t.store_id = stores.id 
                    AND td.cash_withdrawal_id IS NOT NULL
                    AND t.status = 0 
                    AND t.deleted_at IS NULL 
                    AND td.deleted_at IS NULL
                    " . $this->applyDateFilter($request) . "
                ) as tarik_tunai_beli")
            ]);

        // 2. Subquery Kategori Produk (Tetap pakai * quantity karena ini stok barang fisik)
        foreach ($productCategories as $cat) {
            $catId = $cat->id;
            $key = strtolower($cat->name);

            $reportQuery->addSelect(DB::raw("(
                SELECT SUM(td.subtotal)
                FROM transactions t
                JOIN transaction_details td ON t.id = td.transaction_id
                JOIN products p ON td.product_id = p.id
                WHERE t.store_id = stores.id 
                AND p.product_category_id = {$catId}
                AND t.status = 0 
                AND t.deleted_at IS NULL 
                AND td.deleted_at IS NULL
                " . $this->applyDateFilter($request) . "
            ) as {$key}_jual"));

            $reportQuery->addSelect(DB::raw("(
                SELECT SUM(td.buying_prices * td.quantity)
                FROM transactions t
                JOIN transaction_details td ON t.id = td.transaction_id
                JOIN products p ON td.product_id = p.id
                WHERE t.store_id = stores.id 
                AND p.product_category_id = {$catId}
                AND t.status = 0 
                AND t.deleted_at IS NULL 
                AND td.deleted_at IS NULL
                " . $this->applyDateFilter($request) . "
            ) as {$key}_beli"));
        }

        // 2.5 Subquery Digital Wallet
        foreach ($dynamicWallets as $wallet) {
            $walletId = $wallet->id;
            $key = strtolower(str_replace(' ', '_', $wallet->name));

            $reportQuery->addSelect(DB::raw("(
                SELECT SUM(td.subtotal)
                FROM transactions t
                JOIN transaction_details td ON t.id = td.transaction_id
                JOIN topup_transactions tt ON td.topup_transaction_id = tt.id
                JOIN digital_wallet_store dws ON tt.digital_wallet_store_id = dws.id
                WHERE t.store_id = stores.id 
                AND dws.digital_wallet_id = {$walletId}
                AND t.status = 0 
                AND t.deleted_at IS NULL 
                AND td.deleted_at IS NULL
                " . $this->applyDateFilter($request) . "
            ) as {$key}_jual"));

            $reportQuery->addSelect(DB::raw("(
                SELECT SUM(td.buying_prices) -- LANGSUNG AMBIL DARI KOLOM TANPA * QTY
                FROM transactions t
                JOIN transaction_details td ON t.id = td.transaction_id
                JOIN topup_transactions tt ON td.topup_transaction_id = tt.id
                JOIN digital_wallet_store dws ON tt.digital_wallet_store_id = dws.id
                WHERE t.store_id = stores.id 
                AND dws.digital_wallet_id = {$walletId}
                AND t.status = 0 
                AND t.deleted_at IS NULL 
                AND td.deleted_at IS NULL
                " . $this->applyDateFilter($request) . "
            ) as {$key}_beli"));
        }

        // 3. Eksekusi Query
        $reportData = $reportQuery->where('stores.status', '!=', 2)
            ->whereNull('stores.deleted_at')
            ->when($request->store_id, function ($query, $storeId) {
                $query->where('stores.id', $storeId);
            })
            ->get();

        // 4. Transformasi & Pengisian Kolom Pembelian (Total Modal)
        $reportData->transform(function ($item) use ($productCategories, $dynamicWallets) {
            $item->qty = (float) ($item->qty ?? 0);
            $item->total = (float) ($item->total ?? 0);
            $item->tarik_tunai_jual = (float) ($item->tarik_tunai_jual ?? 0);
            $item->tarik_tunai_beli = (float) ($item->tarik_tunai_beli ?? 0);
            
            $totalModal = 0;

            foreach ($productCategories as $cat) {
                $keyJual = strtolower($cat->name) . '_jual';
                $keyBeli = strtolower($cat->name) . '_beli';
                $item->$keyJual = (float) ($item->$keyJual ?? 0);
                $item->$keyBeli = (float) ($item->$keyBeli ?? 0);
                $totalModal += $item->$keyBeli;
            }

            foreach ($dynamicWallets as $wallet) {
                $cleanKey = strtolower(str_replace(' ', '_', $wallet->name));
                $keyJual = $cleanKey . '_jual';
                $keyBeli = $cleanKey . '_beli';
                
                $item->$keyJual = (float) ($item->$keyJual ?? 0);
                $item->$keyBeli = (float) ($item->$keyBeli ?? 0);
                $totalModal += $item->$keyBeli;
            }

            $totalModal += $item->tarik_tunai_beli;

            $item->pembelian = $totalModal; 
            $item->laba_kotor = $item->total - $totalModal;
            $item->laba_bersih = $item->laba_kotor; 

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