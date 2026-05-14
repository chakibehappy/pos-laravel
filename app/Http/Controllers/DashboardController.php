<?php

namespace App\Http\Controllers;

use App\Models\StoreType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard utama dengan dukungan filter dinamis.
     */
    public function index(Request $request)
    {
        // 1. Ambil data store types untuk dropdown di UI
        $storeTypes = StoreType::where('status', 0)
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        // 2. Tangkap parameter filter dari Request
        $selectedUnit = $request->input('businessUnit');
        $idKonter = 1; // ID untuk unit bisnis 'Konter' di database
        
        $filters = [
            'businessUnit' => $selectedUnit,
            'dateFilter'   => $request->input('dateFilter', 'minggu'),
            'singleDate'   => $request->input('singleDate'),
            'startDate'    => $request->input('startDate'),
            'endDate'      => $request->input('endDate'),
        ];

        /**
         * 3. LOGIKA PENDAPATAN HARI INI (Transactions)
         */
        $today = Carbon::today();

        $totalRevenue = DB::table('transactions')
            ->join('stores', 'transactions.store_id', '=', 'stores.id')
            ->whereDate('transactions.transaction_at', $today)
            ->where('transactions.status', 0)
            ->when($selectedUnit, function ($query, $selectedUnit) {
                return $query->where('stores.store_type_id', $selectedUnit);
            })
            ->sum('transactions.total');

        $revenueBreakdown = [];
        if (empty($selectedUnit)) {
            $revenueBreakdown = DB::table('transactions')
                ->join('stores', 'transactions.store_id', '=', 'stores.id')
                ->join('store_types', 'stores.store_type_id', '=', 'store_types.id')
                ->whereDate('transactions.transaction_at', $today)
                ->where('transactions.status', 0)
                ->select('store_types.name', DB::raw('SUM(transactions.total) as total'))
                ->groupBy('store_types.id', 'store_types.name')
                ->get();
        }

        /**
         * 4. LOGIKA DATA CHART (Analisis Penjualan)
         */
        // A. Tentukan Rentang Waktu
        $endDateChart = Carbon::now()->endOfDay();

        if ($filters['dateFilter'] === 'bulan') {
            // Tampilkan dari tanggal 1 bulan ini sampai HARI INI saja (Grafik dinamis)
            $startDateChart = Carbon::now()->startOfMonth();
            $endDateChart = Carbon::now()->endOfDay();
        } elseif ($filters['dateFilter'] === 'bulan_lalu') {
            // START: Tanggal 1 bulan lalu | END: Tanggal terakhir bulan lalu
            $startDateChart = Carbon::now()->subMonth()->startOfMonth();
            $endDateChart = Carbon::now()->subMonth()->endOfMonth();
        } elseif ($filters['dateFilter'] === 'tanggal' && $filters['singleDate']) {
            // HANYA menampilkan 1 diagram/batang pada tanggal yang dipilih
            $startDateChart = Carbon::parse($filters['singleDate'])->startOfDay();
            $endDateChart = Carbon::parse($filters['singleDate'])->endOfDay();
            
        } elseif ($filters['dateFilter'] === 'periode' && $filters['startDate'] && $filters['endDate']) {
            $startDateChart = Carbon::parse($filters['startDate'])->startOfDay();
            $endDateChart = Carbon::parse($filters['endDate'])->endOfDay();
        } else {
            // Default: Rolling 7 hari ke belakang (Hari ini selalu di paling kanan)
            $startDateChart = Carbon::now()->subDays(6)->startOfDay();
            $endDateChart = Carbon::now()->endOfDay();
        }

        // B. Ambil Data Real dari Database
        $actualData = DB::table('transactions')
            ->join('stores', 'transactions.store_id', '=', 'stores.id')
            ->where('transactions.status', 0)
            ->whereBetween('transaction_at', [$startDateChart, $endDateChart])
            ->when($selectedUnit, function ($query, $selectedUnit) {
                return $query->where('stores.store_type_id', $selectedUnit);
            })
            ->select(
                DB::raw('DATE(transaction_at) as date'),
                DB::raw('SUM(total) as daily_total')
            )
            ->groupBy('date')
            ->pluck('daily_total', 'date');

        // C. Isi Slot Kosong secara Sekuensial
        $chartLabels = [];
        $chartValues = [];
        $currentDate = $startDateChart->copy();

        while ($currentDate <= $endDateChart) {
            $dateString = $currentDate->format('Y-m-d');
            
            // Label dinamis: Jika hari ini tulis "Hari Ini", jika tidak tampilkan Tanggal & Bulan
            if ($currentDate->isToday()) {
                $chartLabels[] = 'Hari Ini';
            } else {
                $chartLabels[] = $currentDate->translatedFormat('d M');
            }
            
            $chartValues[] = (int) ($actualData[$dateString] ?? 0);
            
            $currentDate->addDay();
        }

        /**
         * 5. LOGIKA PERHITUNGAN KASIR
         */
        $cashierCount = DB::table('pos_users')
            ->join('pos_user_store', 'pos_users.id', '=', 'pos_user_store.pos_user_id')
            ->join('stores', 'pos_user_store.store_id', '=', 'stores.id')
            ->where('pos_users.status', 0)
            ->where('pos_users.role', 'cashier')
            ->when($selectedUnit, function ($query, $selectedUnit) {
                return $query->where('stores.store_type_id', $selectedUnit);
            })
            ->distinct('pos_users.id')
            ->count();

        /**
         * 6. LOGIKA PERHITUNGAN KOLEKTOR
         */
        $collectorCount = 0;
        if (empty($selectedUnit) || $selectedUnit == $idKonter) {
            $collectorCount = DB::table('pos_users')
                ->where('status', 0)
                ->where('role', 'collector')
                ->count();
        }

        /**
         * 7. LOGIKA PERHITUNGAN ADMIN
         */
        $adminCount = 0;
        if (empty($selectedUnit)) {
            $adminCount = DB::table('pos_users')
                ->where('status', 0)
                ->where('role', 'admin')
                ->count();
        }

        /**
         * 8. LOGIKA PERHITUNGAN TOTAL STOK PRODUK
         */
        $totalStock = DB::table('store_products')
            ->join('stores', 'store_products.store_id', '=', 'stores.id')
            ->where('store_products.status', 0)
            ->when($selectedUnit, function ($query, $selectedUnit) {
                return $query->where('stores.store_type_id', $selectedUnit);
            })
            ->sum('store_products.stock');

        /**
         * 9. LOGIKA BREAKDOWN STOK PER JENIS USAHA
         */
        $stockBreakdown = [];
        if (empty($selectedUnit)) {
            $stockBreakdown = DB::table('store_products')
                ->join('stores', 'store_products.store_id', '=', 'stores.id')
                ->join('store_types', 'stores.store_type_id', '=', 'store_types.id')
                ->where('store_products.status', 0)
                ->select('store_types.name', DB::raw('SUM(store_products.stock) as total'))
                ->groupBy('store_types.id', 'store_types.name')
                ->get();
        }

        /**
         * 10. Final Data Assembly
         */
        return Inertia::render('Dashboard', [
            'storeTypes'        => $storeTypes,
            'filters'           => $filters,
            'totalRevenue'      => (int) $totalRevenue,
            'revenueBreakdown'  => $revenueBreakdown,
            'salesChart'        => [
                'labels'   => $chartLabels,
                'datasets' => $chartValues
            ],
            'totalProductStock' => (int) $totalStock,
            'stockBreakdown'    => $stockBreakdown,
            'staffStats'        => [
                'cashier'   => $cashierCount,
                'collector' => $collectorCount,
                'admin'     => $adminCount,
                'total'     => $cashierCount + $collectorCount + $adminCount
            ]
        ]);
    }
}