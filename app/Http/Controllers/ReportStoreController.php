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

        $paymentMethods = DB::table('payment_methods')
            ->where('status', '!=', 2)
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

        return Inertia::render('ReportStores/Index',[
            'stores' => $stores,
            'storeTypes' => $storeTypes,
            'productCategories' => $productCategories,
            'dynamicWallets' => $dynamicWallets,
            'paymentMethods' => $paymentMethods,
            'filters' => $request->only(['store_id', 'store_type_id', 'payment_method_id', 'start_date', 'end_date']),
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

        // 2. Eager Aggregation: Operasional
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

        // 3a. KHUSUS PRODUK RETAIL (Hanya mengambil baris yang murni produk fisik)
        $productTxData = DB::table('transaction_details as td')
            ->join('transactions as t', 'td.transaction_id', '=', 't.id')
            ->join('products as p', 'td.product_id', '=', 'p.id')
            ->select([
                't.store_id',
                'p.product_category_id',
                DB::raw('SUM(td.subtotal) as total_jual'),
                DB::raw('SUM(td.quantity) as total_qty'),
                DB::raw('SUM(CASE 
                    WHEN td.buying_prices IS NULL OR td.buying_prices = 0 THEN COALESCE(p.buying_price, 0) * td.quantity 
                    ELSE td.buying_prices * td.quantity 
                END) as total_beli')
            ])
            ->whereIn('t.store_id', $storeIds)
            ->where('t.status', 0)
            ->whereNull('t.deleted_at')
            ->whereNull('td.deleted_at')
            ->whereNull('td.topup_transaction_id')
            ->whereNull('td.cash_withdrawal_id') // Kunci isolasi utama agar tidak bocor
            ->when($request->start_date, fn($q) => $q->whereDate('t.transaction_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('t.transaction_at', '<=', $request->end_date))
            ->when($request->payment_method_id, fn($q, $id) => $q->where('t.payment_id', $id))
            ->groupBy('t.store_id', 'p.product_category_id') 
            ->get()
            ->groupBy('store_id');

        // 3b. KHUSUS TARIK TUNAI (Terisolasi penuh dari query retail biasa)
        $withdrawalTxData = DB::table('transaction_details as td')
            ->join('transactions as t', 'td.transaction_id', '=', 't.id')
            ->join('cash_withdrawals as cw', 'td.cash_withdrawal_id', '=', 'cw.id')
            ->select([
                't.store_id',
                DB::raw('COUNT(td.id) as total_qty'),
                DB::raw('SUM(cw.withdrawal_count) as total_tarik_jual'),
                DB::raw('SUM(td.subtotal) as total_tarik_beli')
            ])
            ->whereIn('t.store_id', $storeIds)
            ->where('t.status', 0)
            ->whereNull('t.deleted_at')
            ->whereNull('td.deleted_at')
            ->whereNotNull('td.cash_withdrawal_id')
            ->when($request->start_date, fn($q) => $q->whereDate('t.transaction_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('t.transaction_at', '<=', $request->end_date))
            ->when($request->payment_method_id, fn($q, $id) => $q->where('t.payment_id', $id))
            ->groupBy('t.store_id')
            ->get()
            ->keyBy('store_id');
 
        // 4. Eager Aggregation: Digital Wallet
        $walletData = DB::table('transaction_details as td')
            ->join('transactions as t', 'td.transaction_id', '=', 't.id')
            ->join('topup_transactions as tt', 'td.topup_transaction_id', '=', 'tt.id')
            ->join('digital_wallet_store as dws', 'tt.digital_wallet_store_id', '=', 'dws.id')
            ->select([
                't.store_id',
                'dws.digital_wallet_id',
                DB::raw('SUM(tt.nominal_pay) as total_jual'),
                DB::raw('SUM(tt.nominal_request + tt.provider_fee) as total_beli')
            ])
            ->whereIn('t.store_id', $storeIds)
            ->where('t.status', 0)
            ->whereNull('t.deleted_at')
            ->whereNull('td.deleted_at')
            ->when($request->start_date, fn($q) => $q->whereDate('t.transaction_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('t.transaction_at', '<=', $request->end_date))
            ->when($request->payment_method_id, fn($q, $id) => $q->where('t.payment_id', $id))
            ->groupBy('t.store_id', 'dws.digital_wallet_id')
            ->get()
            ->groupBy('store_id');

        // 5. Mapping Data ke Model Toko
        $stores->transform(function ($item) use ($productTxData, $withdrawalTxData, $walletData, $operasionalData, $productCategories, $dynamicWallets) {
            $storeProd = $productTxData->get($item->id) ?? collect();
            $storeWith = $withdrawalTxData->get($item->id);
            $storeWall = $walletData->get($item->id) ?? collect();

            $totalJualKategori = 0;
            $totalBeliKategori = 0;

            // Mapping kategori produk secara dinamis
            foreach ($productCategories as $cat) {
                $key = strtolower(str_replace(' ', '_', $cat->name));
                $catRow = $storeProd->firstWhere('product_category_id', $cat->id);
                
                $jualValue = (float) ($catRow->total_jual ?? 0);
                $beliValue = (float) ($catRow->total_beli ?? 0);

                $item->{'cat_' . $key . '_jual'} = $jualValue;
                $item->{'cat_' . $key . '_beli'} = $beliValue;

                $totalJualKategori += $jualValue;
                $totalBeliKategori += $beliValue;
            }

            // Mapping digital wallet secara dinamis
            foreach ($dynamicWallets as $wallet) {
                $key = strtolower(str_replace(' ', '_', $wallet->name));
                $wallRow = $storeWall->firstWhere('digital_wallet_id', $wallet->id);
                
                $item->{'wallet_' . $key . '_jual'} = (float) ($wallRow->total_jual ?? 0);
                $item->{'wallet_' . $key . '_beli'} = (float) ($wallRow->total_beli ?? 0);
            }

            // Ambil data Tarik Tunai dari variabel terpisah
            $item->tarik_tunai_jual = (float) ($storeWith->total_tarik_jual ?? 0);
            $item->tarik_tunai_beli = (float) ($storeWith->total_tarik_beli ?? 0);

            // Total kuantitas item gabungan
            $item->qty = (float) $storeProd->sum('total_qty') + (float) ($storeWith->total_qty ?? 0);
            $item->operasional = (float) ($operasionalData[$item->id] ?? 0);

            // SEKARANG DATA OMZET SINKRON DAN BERSIH DARI KEBOCORAN
            $item->total = $totalJualKategori + (float) $storeWall->sum('total_jual'); 

            // Akumulasi modal pembelian
            $totalBeliWallet = (float) $storeWall->sum('total_beli');
            $item->pembelian = $totalBeliKategori + $totalBeliWallet; 
            $item->labatariktunai = $item->tarik_tunai_jual - $item->tarik_tunai_beli;
            $item->labasementara = $item->labatariktunai + $item->total;
            $item->laba_kotor = $item->labasementara - $item->pembelian;
            $item->laba_cabang = $item->laba_kotor - $item->operasional;

            return $item;
        });

        return $stores;
    }
}