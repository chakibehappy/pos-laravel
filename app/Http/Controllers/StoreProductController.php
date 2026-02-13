<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Product;
use App\Exports\StoreProductExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger; // Import Helper

class StoreProductController extends Controller
{
    public function index(Request $request)
    {
        $query = StoreProduct::with(['store', 'product.unitType'])
            ->join('stores', 'store_products.store_id', '=', 'stores.id')
            ->join('products', 'store_products.product_id', '=', 'products.id')
            // Join ke pos_users untuk mengambil nama berdasarkan ID di created_by
            ->leftJoin('pos_users', 'store_products.created_by', '=', 'pos_users.id')
            ->select(
                'store_products.*', 
                'stores.name as store_name', 
                'products.name as product_name',
                'products.buying_price as product_buying_price',
                'products.sku as product_sku',
                'pos_users.name as creator_name' // Kolom nama dari pos_users
            );

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('products.name', 'like', "%{$request->search}%")
                  ->orWhere('products.sku', 'like', "%{$request->search}%")
                  ->orWhere('stores.name', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
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

        return Inertia::render('StoreProducts/Index', [
            'stocks' => $query->latest('store_products.updated_at')->paginate(10)->withQueryString(),
            'stores' => Store::all(['id', 'name', 'store_type_id']),
            'storeTypes' => \App\Models\StoreType::all(['id', 'name']),
            'products' => Product::all(['id', 'name', 'sku', 'buying_price']),
            'categories' => \App\Models\ProductCategory::all(['id', 'name']),
            'filters' => $request->only(['search','store_id','store_type_id','product_category_id']),
        ]);
    }

    /**
     * Menyimpan atau Update stok toko secara mandiri
     */
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

        // Cek data lama untuk menentukan tipe log (Create/Update)
        $existing = StoreProduct::where('store_id', $request->store_id)
            ->where('product_id', $request->product_id)
            ->first();

        $actionLabel = $existing ? "Memperbarui" : "Menambah";
        $logType = $existing ? "update" : "create";

        $sp = StoreProduct::updateOrCreate(
            [
                'store_id' => $request->store_id, 
                'product_id' => $request->product_id
            ],
            [
                'stock' => $request->stock,
                'created_by' => $createdBy
            ]
        );

        // Ambil info relasi untuk deskripsi log
        $product = Product::find($request->product_id);
        $store = Store::find($request->store_id);

        ActivityLogger::log(
            $logType,
            'store_products',
            $sp->id,
            "{$actionLabel} produk {$product->name} di {$store->name} menjadi  {$request->stock}",
            $createdBy
        );

        return back()->with('message', 'Data stok cabang berhasil diperbarui!');
    }

    public function update(Request $request, $id)
    {
        return $this->store($request);
    }

    /**
     * Menghapus stok di toko tanpa mengembalikan ke gudang.
     */
    public function destroy($id)
    {
        try {
            $sp = StoreProduct::with(['product', 'store'])->findOrFail($id);
            
            $adminEmail = auth()->user()->email;
            $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
            $userId = $posUser ? $posUser->id : null;

            // LOG ACTIVITY (Sebelum hapus)
            ActivityLogger::log(
                'delete',
                'store_products',
                $id,
                "Menghapus record stok produk: {$sp->product->name} dari toko {$sp->store->name}",
                $userId
            );

            $sp->delete();
            return back()->with('message', 'Data stok cabang berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Gagal menghapus data stok.']);
        }
    }
    
   public function export(Request $request)
    {
        $query = StoreProduct::query();

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('product_category_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('product_category_id', $request->product_category_id);
            });
        }

        $storeLabel = $request->filled('store_id') ? \App\Models\Store::find($request->store_id)->name : 'Semua-Toko';
        $categoryLabel = $request->filled('product_category_id') ? \App\Models\ProductCategory::find($request->product_category_id)->name : 'Semua-Kategori';
        
        $fileName = \Illuminate\Support\Str::slug("stok-{$storeLabel}-{$categoryLabel}") . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new StoreProductExport($query, $request->product_category_id, $request->store_id), 
            $fileName
        );
    }
}