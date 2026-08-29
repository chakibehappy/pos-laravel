<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportItemController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->getBaseQuery($request);

        // 1. Hitung Summary langsung di DB (Cepat & Hemat Memory)
        $summaryQuery = DB::table(DB::raw("({$query->toSql()}) as sub"))
            ->mergeBindings($query);

        $summary = $summaryQuery->selectRaw('
            COALESCE(SUM(total_qty), 0) as grand_total_qty,
            COALESCE(SUM(total_sales), 0) as grand_total_sales
        ')->first();

        // 2. Dynamic Sorting di Database
        $sortField = $request->get('sort', 'total_sales');
        $sortDirection = $request->get('direction', 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'item_name'           => 'item_name',
            'store_name'          => 's.name',
            'total_qty'           => 'total_qty',
            'total_sales'         => 'total_sales',
            'last_transaction_at' => 'last_transaction_at',
        ];

        $orderBy = $allowedSorts[$sortField] ?? 'total_sales';
        $query->orderBy($orderBy, $sortDirection);

        // 3. Paginasi Asli Laravel (Hanya tarik 10 data per request ke RAM)
        $paginatedResults = $query->paginate(10)->withQueryString();

        return Inertia::render('ReportItems/Index', [
            'reports' => $paginatedResults,
            'filters' => $request->only(['search', 'sort', 'direction', 'store_id', 'type', 'start_date', 'end_date']),
            'stores'  => Store::all(['id', 'name']),
            'summary' => [
                'grand_total_qty'   => (float) ($summary->grand_total_qty ?? 0),
                'grand_total_sales' => (float) ($summary->grand_total_sales ?? 0),
            ]
        ]);
    }

    private function getBaseQuery(Request $request)
    {
        $query = DB::table('transaction_details as td')
            ->join('transactions as t', 'td.transaction_id', '=', 't.id')
            ->leftJoin('products as p', 'td.product_id', '=', 'p.id')
            ->leftJoin('stores as s', 't.store_id', '=', 's.id')
            ->select([
                'td.product_id',
                'td.topup_transaction_id',
                'td.cash_withdrawal_id',
                't.store_id',
                's.name as store_name',
                DB::raw("COALESCE(p.name, 
                    CASE 
                        WHEN td.topup_transaction_id IS NOT NULL THEN 'Topup Layanan'
                        WHEN td.cash_withdrawal_id IS NOT NULL THEN 'Tarik Tunai'
                        ELSE 'Lain-lain'
                    END
                ) as item_name"),
                DB::raw("
                    CASE 
                        WHEN td.product_id IS NOT NULL THEN 'produk'
                        WHEN td.topup_transaction_id IS NOT NULL THEN 'topup'
                        WHEN td.cash_withdrawal_id IS NOT NULL THEN 'tarik_tunai'
                        ELSE 'lainnya'
                    END as item_type
                "),
                DB::raw('SUM(td.quantity) as total_qty'),
                DB::raw('SUM(td.subtotal) as total_sales'),
                DB::raw('MAX(t.transaction_at) as last_transaction_at')
            ])
            ->where('t.status', 0)
            ->whereNull('t.deleted_at')
            ->whereNull('td.deleted_at');

        if ($request->filled('store_id')) {
            $query->where('t.store_id', $request->store_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('t.transaction_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('t.transaction_at', '<=', $request->end_date);
        }

        if ($request->filled('type')) {
            if ($request->type === 'produk') {
                $query->whereNotNull('td.product_id');
            } elseif ($request->type === 'topup') {
                $query->whereNotNull('td.topup_transaction_id');
            } elseif ($request->type === 'tarik_tunai') {
                $query->whereNotNull('td.cash_withdrawal_id');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('p.name', 'LIKE', "%{$search}%")
                  ->orWhere('s.name', 'LIKE', "%{$search}%");
            });
        }

        return $query->groupBy(
            'td.product_id',
            'td.topup_transaction_id',
            'td.cash_withdrawal_id',
            't.store_id',
            's.name',
            'p.name'
        );
    }
}