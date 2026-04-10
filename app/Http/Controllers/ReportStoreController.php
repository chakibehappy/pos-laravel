<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\ExpenseType;
use App\Exports\StoreReportExport; 
use Maatwebsite\Excel\Facades\Excel; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportStoreController extends Controller
{
    public function index(Request $request)
    {
        $stores = Store::where('status', '!=', 2)
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'store_type_id']);

        $storeTypes = DB::table('store_types')
            ->where('status', '!=', 2)
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

        // 1. HITUNG PENGELUARAN GLOBAL (Case-Insensitive)
        // Menjumlahkan semua expense yang tipe namanya mengandung kata "global"
        $globalExpense = DB::table('expense_transactions')
            ->join('expense_types', 'expense_transactions.expense_type_id', '=', 'expense_types.id')
            ->whereRaw("LOWER(expense_types.name) LIKE ?", ['%global%'])
            ->where('expense_transactions.status', 0)
            ->whereNull('expense_transactions.deleted_at')
            ->when($request->start_date, fn($q) => $q->whereDate('expense_transactions.transaction_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('expense_transactions.transaction_at', '<=', $request->end_date))
            ->sum('expense_transactions.amount');

        // Menggunakan method privat untuk mengambil data report per cabang
        $reportData = $this->getReportData($request, $productCategories, $dynamicWallets);

        return Inertia::render('ReportStores/Index', [
            'stores' => $stores,
            'storeTypes' => $storeTypes,
            'productCategories' => $productCategories,
            'dynamicWallets' => $dynamicWallets,
            'filters' => $request->only(['store_id', 'store_type_id', 'start_date', 'end_date']),
            'reportData' => $reportData,
            'globalExpense' => (float)$globalExpense, // Dikirim ke Vue
        ]);
    }

    /**
     * Fitur Eksport Excel
     */
    public function export(Request $request)
    {
        $productCategories = DB::table('product_categories')
            ->where('status', '!=', 2)->whereNull('deleted_at')->get(['id', 'name']);

        $dynamicWallets = DB::table('digital_wallet')
            ->where('status', '!=', 2)->whereNull('deleted_at')->get(['id', 'name']);

        $reportData = $this->getReportData($request, $productCategories, $dynamicWallets);

        // --- PENAMBAHAN BAGIAN PENGELUARAN GLOBAL ---
        $globalExpense = DB::table('expense_transactions')
            ->join('expense_types', 'expense_transactions.expense_type_id', '=', 'expense_types.id')
            ->whereRaw("LOWER(expense_types.name) LIKE ?", ['%global%'])
            ->where('expense_transactions.status', 0)
            ->whereNull('expense_transactions.deleted_at')
            ->when($request->start_date, fn($q) => $q->whereDate('expense_transactions.transaction_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('expense_transactions.transaction_at', '<=', $request->end_date))
            ->sum('expense_transactions.amount');

        // Gabungkan nilai global_expense ke dalam array params
        $params = array_merge($request->all(), [
            'global_expense' => (float)$globalExpense
        ]);
        // --------------------------------------------

        $fileName = 'Rekap_Laporan_Toko_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new StoreReportExport($reportData, $productCategories, $dynamicWallets, $params), 
            $fileName
        );
    }

    /**
     * Method Privat: Logika pengolahan data agar konsisten antara Index & Export
     */
    private function getReportData(Request $request, $productCategories, $dynamicWallets)
    {
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
                    SELECT SUM(td.buying_prices)
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

        // 2. Subquery Kategori Produk
        foreach ($productCategories as $cat) {
            $catId = $cat->id;
            $key = strtolower(str_replace(' ', '_', $cat->name)); 
            $reportQuery->addSelect(DB::raw("(SELECT SUM(td.subtotal) FROM transactions t JOIN transaction_details td ON t.id = td.transaction_id JOIN products p ON td.product_id = p.id WHERE t.store_id = stores.id AND p.product_category_id = {$catId} AND t.status = 0 AND t.deleted_at IS NULL AND td.deleted_at IS NULL " . $this->applyDateFilter($request) . ") as {$key}_jual"));
            $reportQuery->addSelect(DB::raw("(SELECT SUM(td.buying_prices * td.quantity) FROM transactions t JOIN transaction_details td ON t.id = td.transaction_id JOIN products p ON td.product_id = p.id WHERE t.store_id = stores.id AND p.product_category_id = {$catId} AND t.status = 0 AND t.deleted_at IS NULL AND td.deleted_at IS NULL " . $this->applyDateFilter($request) . ") as {$key}_beli"));
        }

        // 2.5 Subquery Digital Wallet
        foreach ($dynamicWallets as $wallet) {
            $walletId = $wallet->id;
            $key = strtolower(str_replace(' ', '_', $wallet->name));
            $reportQuery->addSelect(DB::raw("(SELECT SUM(td.subtotal) FROM transactions t JOIN transaction_details td ON t.id = td.transaction_id JOIN topup_transactions tt ON td.topup_transaction_id = tt.id JOIN digital_wallet_store dws ON tt.digital_wallet_store_id = dws.id WHERE t.store_id = stores.id AND dws.digital_wallet_id = {$walletId} AND t.status = 0 AND t.deleted_at IS NULL AND td.deleted_at IS NULL " . $this->applyDateFilter($request) . ") as {$key}_jual"));
            $reportQuery->addSelect(DB::raw("(SELECT SUM(td.buying_prices) FROM transactions t JOIN transaction_details td ON t.id = td.transaction_id JOIN topup_transactions tt ON td.topup_transaction_id = tt.id JOIN digital_wallet_store dws ON tt.digital_wallet_store_id = dws.id WHERE t.store_id = stores.id AND dws.digital_wallet_id = {$walletId} AND t.status = 0 AND t.deleted_at IS NULL AND td.deleted_at IS NULL " . $this->applyDateFilter($request) . ") as {$key}_beli"));
        }

        // 3. Eksekusi Query
        $reportData = $reportQuery->where('stores.status', '!=', 2)
            ->whereNull('stores.deleted_at')
            ->when($request->store_type_id, fn($q, $id) => $q->where('stores.store_type_id', $id))
            ->when($request->store_id, fn($q, $id) => $q->where('stores.id', $id))
            ->get();

        // 4. Transformasi & Perhitungan Akhir
        $reportData->transform(function ($item) use ($productCategories, $dynamicWallets, $request) {
            $item->qty = (float) ($item->qty ?? 0);
            $item->total = (float) ($item->total ?? 0);
            $item->tarik_tunai_jual = (float) ($item->tarik_tunai_jual ?? 0);
            $item->tarik_tunai_beli = (float) ($item->tarik_tunai_beli ?? 0);

            $operasional = DB::table('expense_transactions')
                ->join('expense_types', 'expense_transactions.expense_type_id', '=', 'expense_types.id')
                ->where('expense_transactions.store_id', $item->id)
                ->where('expense_types.name', 'PENGELUARAN TOKO') 
                ->where('expense_transactions.status', 0)
                ->whereNull('expense_transactions.deleted_at')
                ->when($request->start_date, fn($q) => $q->whereDate('expense_transactions.transaction_at', '>=', $request->start_date))
                ->when($request->end_date, fn($q) => $q->whereDate('expense_transactions.transaction_at', '<=', $request->end_date))
                ->sum('expense_transactions.amount');
            
            $totalModal = 0;
            foreach ($productCategories as $cat) {
                $keyBeli = strtolower($cat->name) . '_beli';
                $keyJual = strtolower($cat->name) . '_jual';
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
            $item->operasional = (float) $operasional; 
            $item->laba_kotor = $item->total - $totalModal;
            $item->laba_cabang = $item->laba_kotor - $item->operasional;

            return $item;
        });

        return $reportData;
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