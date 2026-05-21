<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Product;
use App\Models\StoreType;
use App\Models\ProductCategory;
use App\Models\StoreProduct;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Exports\StoreProductExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;
use Illuminate\Support\Str;

class StoreProductController extends Controller
{
    public function index(Request $request)
    {
        $query = StoreProduct::with(['store', 'product.unitType'])
            ->join('stores', 'store_products.store_id', '=', 'stores.id')
            ->join('products', 'store_products.product_id', '=', 'products.id')
            ->leftJoin('pos_users', 'store_products.created_by', '=', 'pos_users.id')
            ->where('store_products.status', '!=', 2)
            ->select(
                'store_products.*', 
                'stores.name as store_name', 
                'products.name as product_name',
                'products.buying_price as product_buying_price',
                'products.selling_price as product_selling_price',
                'products.sku as product_sku',
                'pos_users.name as creator_name'
            );

        // 1. Logika Pencarian
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('products.name', 'like', "%{$request->search}%")
                  ->orWhere('products.sku', 'like', "%{$request->search}%")
                  ->orWhere('stores.name', 'like', "%{$request->search}%");
            });
        }

        // 2. Logika Filter
        if ($request->filled('store_id')) {
            $query->where('store_products.store_id', $request->store_id);
        }

        if ($request->filled('store_type_id')) {
            $query->whereHas('store', function($q) use ($request) {
                $q->where('store_type_id', $request->store_type_id);
            });
        }

        if ($request->filled('product_category_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('product_category_id', $request->product_category_id);
            });
        }

        // 3. LOGIKA SORTING DINAMIS
        $sort = $request->input('sort');
        $direction = $request->input('direction', 'desc');

        $allowedSorts = [
            'store_name'           => 'stores.name',
            'product_name'         => 'products.name',
            'product_sku'          => 'products.sku',
            'product_buying_price' => 'products.buying_price',
            'product_selling_price'=> 'products.selling_price',
            'stock'                => 'store_products.stock',
            'updated_at'           => 'store_products.updated_at'
        ];

        if (isset($allowedSorts[$sort])) {
            $query->orderBy($allowedSorts[$sort], $direction);
        } else {
            $query->orderBy('store_products.updated_at', 'desc');
        }

        return Inertia::render('StoreProducts/Index', [
            'stocks' => $query->paginate(10)->withQueryString(),
            'stores' => Store::where('stores.status', '!=', 2)->get(['id', 'name', 'store_type_id']),
            'storeTypes' => StoreType::all(['id', 'name']),
            'products' => Product::where('products.status', '!=', 2)->get(['id', 'name', 'sku', 'buying_price', 'selling_price']),
            'categories' => ProductCategory::all(['id', 'name']),
            'filters' => $request->only(['search', 'store_id', 'store_type_id', 'product_category_id', 'sort', 'direction']),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->has('batch') && is_array($request->batch)) {
            $request->validate([
                'store_id'           => 'required|exists:stores,id',
                'batch.*.store_id'   => 'required|exists:stores,id',
                'batch.*.product_id' => 'required|exists:products,id',
                'batch.*.stock'      => 'required|integer|min:0',
            ]);
        } else {
            $request->validate([
                'store_id'   => 'required|exists:stores,id',
                'product_id' => 'required|exists:products,id',
                'stock'      => 'required|integer|min:0',
            ]);
        }

        return DB::transaction(function () use ($request) {
            $adminEmail = auth()->user()->email;
            $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
            $createdBy = $posUser ? $posUser->id : null;

            if ($request->has('batch')) {
                // 1. Buat SATU transaksi purchase utama menggunakan store_id dari tingkat utama payload request
                $purchase = Purchase::create([
                    'store_id'    => $request->store_id,
                    'created_by'  => $createdBy,
                    'status'      => 0,
                    'total_harga' => 0, // Nilai awal diset 0 sebelum dihitung secara dinamis
                ]);

                // Variabel bantuan untuk menampung total akumulasi dari seluruh item detail
                $grandTotal = 0;

                // 2. Iterasi seluruh item di dalam batch keranjang
                foreach ($request->batch as $batchItem) {
                    // Cari product untuk mengambil nama dan harga modal (buying_price) terbaru
                    $product = Product::find($batchItem['product_id']);
                    
                    $buyingPrice = $product ? $product->buying_price : 0;
                    $qty = $batchItem['stock'];
                    $itemTotal = $buyingPrice * $qty;

                    // Akumulasikan ke grand total keseluruhan purchase
                    $grandTotal += $itemTotal;

                    // 3. Masukkan item detail purchase menggunakan id yang sama beserta harga modal dan total baris
                    PurchaseDetail::create([
                        'purchase_id'  => $purchase->id,
                        'product_id'   => $batchItem['product_id'],
                        'product_name' => $product ? $product->name : 'Unknown',
                        'buying_price' => $buyingPrice,
                        'qty'          => $qty,
                        'total'        => $itemTotal,
                    ]);

                    // 4. Update atau create stock di cabang terkait
                    $this->processItem($batchItem, $createdBy);
                }

                // 5. Perbarui kolom total_harga di tabel purchases berdasarkan akumulasi semua item detail
                $purchase->update([
                    'total_harga' => $grandTotal
                ]);
            } else {
                // Proses edit konvensional per baris (jika tidak via batch)
                $this->processItem($request->all(), $createdBy);
            }

            return back()->with('message', 'Data stok cabang berhasil diperbarui!');
        });
    }

    private function processItem(array $data, $createdBy)
    {
        $existing = StoreProduct::where('store_id', $data['store_id'])
            ->where('product_id', $data['product_id'])
            ->first();

        $oldStock = $existing ? $existing->stock : 0;
        $oldData = $existing ? $existing->getRawOriginal() : null;
        $isRestoring = ($existing && $existing->status == 2);
        
        $logType = ($existing && !$isRestoring) ? "update" : "create";
        $actionLabel = ($existing && !$isRestoring) ? "Memperbarui" : "Menambah";

        $stockChangeInfo = ($existing && !$isRestoring) 
            ? "dari {$oldStock} menjadi {$data['stock']}" 
            : "menjadi {$data['stock']}";

        $sp = StoreProduct::updateOrCreate(
            ['store_id' => $data['store_id'], 'product_id' => $data['product_id']],
            [
                'stock' => $data['stock'], 
                'created_by' => $createdBy,
                'status' => 0, 
                'deleted_at' => null 
            ]
        );

        $product = Product::find($data['product_id']);
        $store = Store::find($data['store_id']);

        ActivityLogger::log(
            $logType,
            'store_products',
            $sp->id,
            "{$actionLabel} stok produk {$product->name} di {$store->name} {$stockChangeInfo}",
            $createdBy,
            ['old' => $oldData, 'new' => $sp->getAttributes()],
            $data['store_id']
        );
    }

    public function update(Request $request, $id)
    {
        return $this->store($request);
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            try {
                $sp = StoreProduct::with(['product', 'store'])->findOrFail($id);
                $oldData = $sp->getRawOriginal();
                
                $adminEmail = auth()->user()->email;
                $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
                $userId = $posUser ? $posUser->id : null;

                // Soft Delete Manual (Archived)
                $sp->update([
                    'status' => 2,
                    'deleted_at' => now()
                ]);

                ActivityLogger::log(
                    'delete',
                    'store_products',
                    $id,
                    "Menghapus record stok produk: {$sp->product->name} dari toko {$sp->store->name}",
                    $userId,
                    ['old' => $oldData, 'new' => $sp->getAttributes()],
                    $sp->store_id
                );

                return back()->with('message', 'Data stok cabang berhasil dihapus.');
            } catch (\Exception $e) {
                return back()->withErrors(['message' => 'Gagal menghapus data stok.']);
            }
        });
    }
    
    public function export(Request $request)
    {
        $query = StoreProduct::where('status', '!=', 2);

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('product_category_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('product_category_id', $request->product_category_id);
            });
        }

        $storeLabel = $request->filled('store_id') ? Store::find($request->store_id)->name : 'Semua-Toko';
        $categoryLabel = $request->filled('product_category_id') ? ProductCategory::find($request->product_category_id)->name : 'Semua-Kategori';
        
        $fileName = Str::slug("stok-{$storeLabel}-{$categoryLabel}") . '.xlsx';

        return Excel::download(
            new StoreProductExport($query, $request->product_category_id, $request->store_id), 
            $fileName
        );
    }
}