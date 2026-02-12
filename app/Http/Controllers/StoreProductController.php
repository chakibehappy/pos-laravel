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
            'products' => Product::all(['id', 'name', 'sku']),
            'categories' => \App\Models\ProductCategory::all(['id', 'name']),
            'filters' => $request->only(['search','store_id','store_type_id','product_category_id']),
        ]);
    }

    /**
     * Menyimpan atau Update stok toko secara mandiri
     * Serta mencatat siapa yang melakukan aksi berdasarkan mapping email -> pos_users
     */
    public function store(Request $request)
    {
        $request->validate([
            'store_id'   => 'required|exists:stores,id',
            'product_id' => 'required|exists:products,id',
            'stock'      => 'required|integer|min:0',
        ]);

        /** * LOGIKA MAPPING USER:
         * 1. Ambil email dari user yang login di sistem Admin.
         * 2. Cari di tabel 'pos_users' yang username-nya sama dengan email tersebut.
         */
        $adminEmail = auth()->user()->email;
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
        
        // Ambil ID-nya jika ketemu, jika tidak set null
        $createdBy = $posUser ? $posUser->id : null;

        // Proses Update atau Create (Mandiri tanpa memotong stok gudang)
        StoreProduct::updateOrCreate(
            [
                'store_id' => $request->store_id, 
                'product_id' => $request->product_id
            ],
            [
                'stock' => $request->stock,
                'created_by' => $createdBy
            ]
        );

        return back()->with('message', 'Data stok cabang berhasil diperbarui!');
    }

    public function update(Request $request, $id)
    {
        // Mengarahkan ke store untuk konsistensi logic
        return $this->store($request);
    }

    /**
     * Menghapus stok di toko tanpa mengembalikan ke gudang.
     */
    public function destroy($id)
    {
        $sp = StoreProduct::findOrFail($id);
        $sp->delete();

        return back()->with('message', 'Data stok cabang berhasil dihapus.');
    }
    
   public function export(Request $request)
    {
        $query = StoreProduct::query();

        // Terapkan filter dasar jika dipilih
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('product_category_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('product_category_id', $request->product_category_id);
            });
        }

        // Penamaan File
        $storeLabel = $request->filled('store_id') ? \App\Models\Store::find($request->store_id)->name : 'Semua-Toko';
        $categoryLabel = $request->filled('product_category_id') ? \App\Models\ProductCategory::find($request->product_category_id)->name : 'Semua-Kategori';
        
        $fileName = \Illuminate\Support\Str::slug("stok-{$storeLabel}-{$categoryLabel}") . '.xlsx';

        // Kirim query, category_id, dan store_id
        return \Maatwebsite\Excel\Facades\Excel::download(
            new StoreProductExport($query, $request->product_category_id, $request->store_id), 
            $fileName
        );
    }

}