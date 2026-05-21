<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreType;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportBuyingController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil data utama Purchases dengan relasi terkait
        $query = Purchase::join('stores', 'purchases.store_id', '=', 'stores.id')
            ->leftJoin('pos_users', 'purchases.created_by', '=', 'pos_users.id')
            ->select(
                'purchases.id as id',
                'purchases.created_at as tanggal',
                'purchases.total_harga as total',
                'stores.name as pemasok',
                'stores.store_type_id',
                'pos_users.name as user_name'
            );

        // 2. Filter Pencarian
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('purchases.id', 'like', "%{$request->search}%")
                  ->orWhere('stores.name', 'like', "%{$request->search}%");
            });
        }

        // 3. Filter Jenis Usaha
        if ($request->filled('store_type_id')) {
            $query->where('stores.store_type_id', $request->store_type_id);
        }

        // 4. Filter Cabang Toko
        if ($request->filled('store_id')) {
            $query->where('purchases.store_id', $request->store_id);
        }

        // 5. Filter Rentang Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('purchases.created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('purchases.created_at', '<=', $request->end_date);
        }

        // 6. Eksekusi query dengan pagination
        $purchases = $query->orderBy('purchases.id', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($item) {
                // Ambil SEMUA detail item produk yang ada di pembelian ini
                $details = DB::table('purchase_details')
                    ->where('purchase_id', $item->id)
                    ->get();

                // Hitung total kuantitas dari semua item produk
                $totalQty = $details->sum('qty');

                return [
                    'id'           => $item->id,
                    'tanggal'      => date('d-m-Y', strtotime($item->tanggal)),
                    'nomor_faktur' => '#' . $item->id,
                    'pemasok'      => $item->pemasok,
                    'user_name'    => $item->user_name,
                    'kuantitas'    => $totalQty,
                    'total'        => $item->total,
                    // Kirimkan array list produk ke Vue untuk di-looping
                    'items_list'   => $details->map(function ($detail) {
                        return [
                            'product_name' => $detail->product_name,
                            'qty'          => $detail->qty,
                            'buying_price' => $detail->buying_price,
                            'total_item'   => $detail->total,
                        ];
                    })
                ];
            });

        return Inertia::render('ReportBuying/Index', [
            'purchases'  => $purchases,
            'stores'     => Store::where('status', '!=', 2)->get(['id', 'name', 'store_type_id']),
            'storeTypes' => StoreType::all(['id', 'name']),
            'filters'    => $request->only(['search', 'store_type_id', 'store_id', 'start_date', 'end_date']),
        ]);
    }

    public function export(Request $request)
    {
        return response()->json(['message' => 'Fungsi export belum dikonfigurasi.']);
    }
}