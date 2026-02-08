<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Product;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreProductController extends Controller
{
    public function index(Request $request)
    {
        $query = StoreProduct::with(['store', 'product.unitType'])
            ->join('stores', 'store_products.store_id', '=', 'stores.id')
            ->join('products', 'store_products.product_id', '=', 'products.id')
            ->select(
                'store_products.*', 
                'stores.name as store_name', 
                'products.name as product_name',
                'products.sku as product_sku'
            );

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('products.name', 'like', "%{$request->search}%")
                  ->orWhere('products.sku', 'like', "%{$request->search}%")
                  ->orWhere('stores.name', 'like', "%{$request->search}%");
            });
        }

        return Inertia::render('StoreProducts/Index', [
            'stocks' => $query->latest('store_products.updated_at')->paginate(10)->withQueryString(),
            'stores' => Store::all(['id', 'name']),
            'products' => Product::all(['id', 'name', 'sku']), // Tidak perlu ambil data stock gudang lagi
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Menyimpan atau Update stok toko tanpa mempedulikan stok gudang.
     */
    public function store(Request $request)
    {
        $request->validate([
            'store_id'   => 'required|exists:stores,id',
            'product_id' => 'required|exists:products,id',
            'stock'      => 'required|integer|min:0',
        ]);

        // Langsung update atau create tanpa mengecek dan memotong stok gudang
        StoreProduct::updateOrCreate(
            ['store_id' => $request->store_id, 'product_id' => $request->product_id],
            ['stock' => $request->stock]
        );

        return back()->with('message', 'Stok toko berhasil diperbarui (Mandiri).');
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
        $sp = StoreProduct::findOrFail($id);
        $sp->delete();

        return back()->with('message', 'Data stok toko telah dihapus.');
    }
}