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

        // HITUNG PENGELUARAN GLOBAL
        $globalExpense = DB::table('expense_transactions')
            ->join('expense_types', 'expense_transactions.expense_type_id', '=', 'expense_types.id')
            ->whereRaw("LOWER(expense_types.name) LIKE ?", ['%global%'])
            ->where('expense_transactions.status', 0)
            ->whereNull('expense_transactions.deleted_at')
            ->when($request->start_date, fn($q) => $q->whereDate('expense_transactions.transaction_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('expense_transactions.transaction_at', '<=', $request->end_date))
            ->sum('expense_transactions.amount');

        $reportData = $this->getReportData($request, $productCategories, $dynamicWallets);

        return Inertia::render('ReportStores/Index', [
            'stores' => $stores,
            'storeTypes' => $storeTypes,
            'productCategories' => $productCategories,
            'dynamicWallets' => $dynamicWallets,
            'filters' => $request->only(['store_id', 'store_type_id', 'start_date', 'end_date']),
            'reportData' => $reportData,
            'globalExpense' => (float)$globalExpense,
        ]);
    }

    public function export(Request $request)
    {
        $productCategories = DB::table('product_categories')
            ->where('status', '!=', 2)->whereNull('deleted_at')->get(['id', 'name']);

        $dynamicWallets = DB::table('digital_wallet')
            ->where('status', '!=', 2)->whereNull('deleted_at')->get(['id', 'name']);

        $reportData = $this->getReportData($request, $productCategories, $dynamicWallets);

        $globalExpense = DB::table('expense_transactions')
            ->join('expense_types', 'expense_transactions.expense_type_id', '=', 'expense_types.id')
            ->whereRaw("LOWER(expense_types.name) LIKE ?", ['%global%'])
            ->where('expense_transactions.status', 0)
            ->whereNull('expense_transactions.deleted_at')
            ->when($request->start_date, fn($q) => $q->whereDate('expense_transactions.transaction_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('expense_transactions.transaction_at', '<=', $request->end_date))
            ->sum('expense_transactions.amount');

        $params = array_merge($request->all(), [
            'global_expense' => (float)$globalExpense
        ]);

        $fileName = 'Rekap_Laporan_Toko_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new StoreReportExport($reportData, $productCategories, $dynamicWallets, $params), 
            $fileName
        );
    }

    private function getReportData(Request $request, $productCategories, $dynamicWallets)
    {
        // 1. Ambil Dasar Toko
        $stores = Store::where('status', '!=', 2)
            ->whereNull('deleted_at')
            ->when($request->store_type_id, fn($q, $id) => $q->where('store_type_id', $id))
            ->when($request->store_id, fn($q, $id) => $q->where('id', $id))
            ->get(['id', 'name as nama_cabang']);

        $storeIds = $stores->pluck('id');

        // 2. Eager Aggregation: Operasional (Satu query untuk semua toko)
        $operasionalData = DB::table('expense_transactions')
            ->join('expense_types', 'expense_transactions.expense_type_id', '=', 'expense_types.id')
            ->select('store_id', DB::raw('SUM(amount) as total_operasional'))
            ->whereIn('store_id', $storeIds)
            ->where('expense_types.name', 'PENGELUARAN TOKO')
            ->where('expense_transactions.status', 0)
            ->whereNull('expense_transactions.deleted_at')
            ->when($request->start_date, fn($q) => $q->whereDate('transaction_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('transaction_at', '<=', $request->end_date))
            ->groupBy('store_id')
            ->pluck('total_operasional', 'store_id');

        // 3. Eager Aggregation: Transaksi Produk, Tarik Tunai, & Qty (Satu query besar)
       // 3. Eager Aggregation: Transaksi Produk, Tarik Tunai, & Qty
        $transactionData = DB::table('transaction_details as td')
        ->join('transactions as t', 'td.transaction_id', '=', 't.id')
        ->leftJoin('products as p', 'td.product_id', '=', 'p.id')
        ->leftJoin('cash_withdrawals as cw', 'td.cash_withdrawal_id', '=', 'cw.id') 
        ->select([
            't.store_id',
            'p.product_category_id',
            'td.cash_withdrawal_id',
            DB::raw('SUM(td.subtotal) as total_jual'),
            DB::raw('SUM(td.buying_prices * td.quantity) as total_beli'),
            DB::raw('SUM(CASE WHEN td.topup_transaction_id IS NOT NULL OR td.cash_withdrawal_id IS NOT NULL THEN 1 ELSE td.quantity END) as total_qty'),
            
            // JUAL: Tetap dari withdrawal_count (Misal: 26.000)
            DB::raw('SUM(CASE WHEN td.cash_withdrawal_id IS NOT NULL THEN cw.withdrawal_count ELSE 0 END) as total_tarik_jual'),
            
            // BELI: SEKARANG mengambil dari td.subtotal (Angka 20.000 tadi)
            DB::raw('SUM(CASE WHEN td.cash_withdrawal_id IS NOT NULL THEN td.subtotal ELSE 0 END) as total_tarik_beli')
        ])
        ->whereIn('t.store_id', $storeIds)
        ->where('t.status', 0)
        ->whereNull('t.deleted_at')
        ->whereNull('td.deleted_at')
        ->when($request->start_date, fn($q) => $q->whereDate('t.transaction_at', '>=', $request->start_date))
        ->when($request->end_date, fn($q) => $q->whereDate('t.transaction_at', '<=', $request->end_date))
        // Tambahkan td.cash_withdrawal_id di groupBy agar data tidak melebur jadi satu
        ->groupBy('t.store_id', 'p.product_category_id', 'td.cash_withdrawal_id') 
        ->get()
        ->groupBy('store_id');
        // 4. Eager Aggregation: Digital Wallet (Satu query)
        $walletData = DB::table('transaction_details as td')
            ->join('transactions as t', 'td.transaction_id', '=', 't.id')
            ->join('topup_transactions as tt', 'td.topup_transaction_id', '=', 'tt.id')
            ->join('digital_wallet_store as dws', 'tt.digital_wallet_store_id', '=', 'dws.id')
            ->select([
                't.store_id',
                'dws.digital_wallet_id',
                DB::raw('SUM(td.subtotal) as total_jual'),
                DB::raw('SUM(td.buying_prices) as total_beli')
            ])
            ->whereIn('t.store_id', $storeIds)
            ->where('t.status', 0)
            ->whereNull('t.deleted_at')
            ->whereNull('td.deleted_at')
            ->when($request->start_date, fn($q) => $q->whereDate('t.transaction_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('t.transaction_at', '<=', $request->end_date))
            ->groupBy('t.store_id', 'dws.digital_wallet_id')
            ->get()
            ->groupBy('store_id');

        // 5. Mapping Data ke Model Toko
        $stores->transform(function ($item) use ($transactionData, $walletData, $operasionalData, $productCategories, $dynamicWallets) {
            $storeTx = $transactionData->get($item->id) ?? collect();
            $storeWall = $walletData->get($item->id) ?? collect();

            $item->qty = (float) $storeTx->sum('total_qty');

            // 1. Ambil nilai Tarik Tunai Jual yang benar (withdrawal_count)
            $item->tarik_tunai_jual = (float) $storeTx->sum('total_tarik_jual'); // Ini 26.000
            $item->tarik_tunai_beli = (float) $storeTx->sum('total_tarik_beli');

            // 2. Hitung Jual Produk lain (Non-Tarik Tunai) agar tidak double count
            $totalJualProdukLain = (float) $storeTx->whereNull('cash_withdrawal_id')->sum('total_jual');

            // 3. SEKARANG TOTAL OMZET SINKRON:
            // Produk Lain + Nilai Tarik Tunai yang benar + Wallet
            $item->total = $totalJualProdukLain + $item->tarik_tunai_jual + (float) $storeWall->sum('total_jual');

            $item->operasional = (float) ($operasionalData[$item->id] ?? 0);
            $totalModal = $item->tarik_tunai_beli;

            $totalModal = $item->tarik_tunai_beli;

            // Mapping kategori produk secara dinamis
            foreach ($productCategories as $cat) {
                $key = strtolower(str_replace(' ', '_', $cat->name));
                $catRow = $storeTx->firstWhere('product_category_id', $cat->id);
                
                $item->{$key . '_jual'} = (float) ($catRow->total_jual ?? 0);
                $item->{$key . '_beli'} = (float) ($catRow->total_beli ?? 0);
                $totalModal += $item->{$key . '_beli'};
            }

            // Mapping digital wallet secara dinamis
            foreach ($dynamicWallets as $wallet) {
                $key = strtolower(str_replace(' ', '_', $wallet->name));
                $wallRow = $storeWall->firstWhere('digital_wallet_id', $wallet->id);
                
                $item->{$key . '_jual'} = (float) ($wallRow->total_jual ?? 0);
                $item->{$key . '_beli'} = (float) ($wallRow->total_beli ?? 0);
                $totalModal += $item->{$key . '_beli'};
            }

            $item->pembelian = $totalModal; 
            $item->laba_kotor = $item->total - $totalModal;
            $item->laba_cabang = $item->laba_kotor - $item->operasional;

            return $item;
        });

        return $stores;
    }
}