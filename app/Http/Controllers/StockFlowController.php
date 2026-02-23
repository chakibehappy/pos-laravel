<?php

namespace App\Http\Controllers;

use App\Models\StockFlow;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class StockFlowController extends Controller
{
    public function index(Request $request)
    {
        // Menangkap parameter dari DataTable.vue & Filter
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        
        // Query Dasar dengan Eager Loading agar tidak N+1
        $query = StockFlow::with(['product.product', 'creator']);

        // Filter: Pencarian (Berdasarkan Nama Produk atau Tipe Transaksi)
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->whereHas('product.product', function($pq) use ($request) {
                    $pq->where('name', 'like', "%{$request->search}%");
                })
                ->orWhere('transaction_type', 'like', "%{$request->search}%")
                ->orWhere('reference_id', 'like', "%{$request->search}%");
            });
        }

        // Filter: Rentang Tanggal (Sangat Penting untuk Report)
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }

        // Filter: Tipe Transaksi Khusus
        if ($request->type) {
            $query->where('transaction_type', $request->type);
        }

        return Inertia::render('StockFlow/Index', [
            'resource' => $query->orderBy($sortField, $sortDirection)
                               ->paginate(15)
                               ->withQueryString(),
            'filters'  => $request->only(['search', 'sort', 'direction', 'start_date', 'end_date', 'type']),
            // Mengambil semua tipe transaksi yang tersedia secara unik untuk dropdown filter
            'types'    => StockFlow::distinct()->pluck('transaction_type')
        ]);
    }
}