<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreType;
use App\Models\Purchase;
use App\Models\StoreProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

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

        // --- PERUBAHAN: Hitung total dari seluruh hasil query yang terfilter menggunakan clone ---
        $grandTotalAll = (clone $query)->sum('purchases.total_harga');

        // 6. Eksekusi query dengan pagination
        $purchases = $query->orderBy('purchases.id', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($item) {
                $details = DB::table('purchase_details')
                    ->where('purchase_id', $item->id)
                    ->get();

                $totalQty = $details->sum('qty');

                return [
                    'id'           => $item->id,
                    'tanggal'      => date('d-m-Y', strtotime($item->tanggal)),
                    'nomor_faktur' => '#' . $item->id,
                    'pemasok'      => $item->pemasok,
                    'user_name'    => $item->user_name,
                    'kuantitas'    => $totalQty,
                    'total'        => $item->total,
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
            'purchases'     => $purchases,
            'grandTotalAll' => $grandTotalAll, // Kirim ke Vue
            'stores'        => Store::where('status', '!=', 2)->get(['id', 'name', 'store_type_id']),
            'storeTypes'    => StoreType::all(['id', 'name']),
            'filters'       => $request->only(['search', 'store_type_id', 'store_id', 'start_date', 'end_date']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'batch'              => 'required|array|min:1',
            'batch.*.store_id'   => 'required|exists:stores,id',
            'batch.*.product_id' => 'required|exists:products,id',
            'batch.*.stock'      => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $adminEmail = auth()->user()->email;
            $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
            $createdBy = $posUser ? $posUser->id : null;

            $storeId = $request->batch[0]['store_id'];
            $store = Store::findOrFail($storeId);

            $purchase = Purchase::create([
                'store_id'    => $storeId,
                'total_harga' => 0,
                'created_by'  => $createdBy,
                'status'      => 0
            ]);

            $grandTotal = 0;
            $logDetails = [];

            foreach ($request->batch as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qty = (int) $item['stock'];
                $subTotal = $product->buying_price * $qty;
                $grandTotal += $subTotal;

                DB::table('purchase_details')->insert([
                    'purchase_id'  => $purchase->id,
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'buying_price' => $product->buying_price,
                    'qty'          => $qty,
                    'total'        => $subTotal,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                $storeProduct = StoreProduct::where('store_id', $storeId)
                    ->where('product_id', $product->id)
                    ->first();

                $oldStock = $storeProduct ? $storeProduct->stock : 0;
                $newStock = $oldStock + $qty;

                StoreProduct::updateOrCreate(
                    ['store_id' => $storeId, 'product_id' => $product->id],
                    [
                        'stock'      => $newStock,
                        'created_by' => $createdBy,
                        'status'     => 0,
                        'deleted_at' => null
                    ]
                );

                $logDetails[] = "{$product->name} (Qty: {$qty}, Stok lama: {$oldStock} -> Stok baru: {$newStock})";
            }

            $purchase->update(['total_harga' => $grandTotal]);

            $detailsString = implode(', ', $logDetails);
            ActivityLogger::log(
                'create', 'purchases', $purchase->id,
                "Mencatat nota pembelian baru #{$purchase->id} untuk cabang {$store->name}. Detail item: {$detailsString}",
                $createdBy, ['new' => ['purchase_id' => $purchase->id, 'total_harga' => $grandTotal]],
                $storeId
            );

            return back()->with('message', 'Transaksi pengadaan stok berhasil disimpan!');
        });
    }

    /**
     * Menghapus data transaksi pembelian dan mengembalikan stok produk toko (Rollback)
     */
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $purchase = Purchase::findOrFail($id);
            $store = Store::findOrFail($purchase->store_id);

            $adminEmail = auth()->user()->email;
            $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
            $createdBy = $posUser ? $posUser->id : null;

            // 1. Ambil semua item pembelian dari tabel purchase_details
            $details = DB::table('purchase_details')->where('purchase_id', $purchase->id)->get();
            $logDetails = [];

            foreach ($details as $detail) {
                // 2. Cari stok produk di toko terkait
                $storeProduct = StoreProduct::where('store_id', $purchase->store_id)
                    ->where('product_id', $detail->product_id)
                    ->first();

                if ($storeProduct) {
                    $oldStock = $storeProduct->stock;
                    // Kurangi kembali stok yang dulu ditambahkan saat proses pembelian
                    $newStock = $oldStock - $detail->qty;

                    $storeProduct->update([
                        'stock' => $newStock < 0 ? 0 : $newStock
                    ]);

                    $logDetails[] = "{$detail->product_name} (Qty dikurangi: {$detail->qty}, Stok: {$oldStock} -> {$newStock})";
                }
            }

            // 3. Catat log pembatalan/penghapusan ke Activity Log
            $detailsString = implode(', ', $logDetails);
            ActivityLogger::log(
                'delete', 'purchases', $purchase->id,
                "Menghapus nota pembelian #{$purchase->id} dari cabang {$store->name}. Penyesuaian stok: {$detailsString}",
                $createdBy, ['old' => ['purchase_id' => $purchase->id, 'total_harga' => $purchase->total_harga]],
                $purchase->store_id
            );

            // 4. Hapus detail transaksi dan data utama transaksi pembelian
            DB::table('purchase_details')->where('purchase_id', $purchase->id)->delete();
            $purchase->delete();

            return back()->with('message', 'Data pembelian berhasil dihapus dan stok toko telah disesuaikan.');
        });
    }

    public function export(Request $request)
    {
        return response()->json(['message' => 'Fungsi export belum dikonfigurasi.']);
    }
}