<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Product;
use App\Models\StoreType;
use App\Models\ProductCategory;
use App\Models\StoreProduct;
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
            // Tambahkan filter status aktif (bukan 2)
            ->where('store_products.status', '!=', 2)
            ->select(
                'store_products.*', 
                'stores.name as store_name', 
                'products.name as product_name',
                'products.buying_price as product_buying_price',
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
            'products' => Product::where('products.status', '!=', 2)->get(['id', 'name', 'sku', 'buying_price']),
            'categories' => ProductCategory::all(['id', 'name']),
            'filters' => $request->only(['search', 'store_id', 'store_type_id', 'product_category_id', 'sort', 'direction']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id'   => 'required|exists:stores,id',
            'product_id' => 'required|exists:products,id',
            'stock'      => 'required|integer|min:0',
        ]);

        $adminEmail = auth()->user()->email;
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
        $createdBy = $posUser ? $posUser->id : null;

        // Cek apakah relasi produk-toko sudah ada (termasuk yang statusnya 2/deleted)
        $existing = StoreProduct::where('store_id', $request->store_id)
            ->where('product_id', $request->product_id)
            ->first();

        // Jika data ada dan statusnya 2, maka ini adalah pemulihan (create kembali)
        // Jika data ada dan statusnya 0, maka ini adalah update stok biasa
        $isRestoring = ($existing && $existing->status == 2);
        $actionLabel = ($existing && !$isRestoring) ? "Memperbarui" : "Menambah";
        $logType = ($existing && !$isRestoring) ? "update" : "create";

        $sp = StoreProduct::updateOrCreate(
            ['store_id' => $request->store_id, 'product_id' => $request->product_id],
            [
                'stock' => $request->stock, 
                'created_by' => $createdBy,
                'status' => 0, // Pastikan status kembali 0 (aktif)
                'deleted_at' => null // Reset deleted_at jika ada
            ]
        );

        $product = Product::find($request->product_id);
        $store = Store::find($request->store_id);

        ActivityLogger::log(
            $logType,
            'store_products',
            $sp->id,
            "{$actionLabel} produk {$product->name} di {$store->name} menjadi {$request->stock}",
            $createdBy
        );

        return back()->with('message', 'Data stok cabang berhasil diperbarui!');
    }

    public function update(Request $request, $id)
    {
        // Tetap arahkan ke fungsi store karena menggunakan updateOrCreate
        return $this->store($request);
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            try {
                $sp = StoreProduct::with(['product', 'store'])->findOrFail($id);
                
                $adminEmail = auth()->user()->email;
                $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
                $userId = $posUser ? $posUser->id : null;

                //   Manual (Ubah status ke 2)
                $sp->update([
                    'status' => 2,
                    'deleted_at' => now()
                ]);

                ActivityLogger::log(
                    'delete',
                    'store_products',
                    $id,
                    "Menghapus record stok produk: {$sp->product->name} dari toko {$sp->store->name} (Archived)",
                    $userId
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