<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Product;
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
            // Gunakan alias updated_at agar tidak ambigu saat ordering
            'stocks' => $query->latest('store_products.updated_at')->paginate(10)->withQueryString(),
            'stores' => Store::all(['id', 'name']),
            'products' => Product::all(['id', 'name', 'sku', 'stock']),
            // Flash message menggunakan key 'message' agar sesuai dengan Index.vue Anda
            'flash' => ['message' => session('message')]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id'   => 'required|exists:stores,id',
            'product_id' => 'required|exists:products,id',
            'stock'      => 'required|integer|min:0',
        ]);

        try {
            DB::transaction(function () use ($request) {
                // Lock data produk di gudang agar tidak ada tabrakan stok
                $product = Product::lockForUpdate()->findOrFail($request->product_id);
                
                $storeProduct = StoreProduct::where('store_id', $request->store_id)
                    ->where('product_id', $request->product_id)
                    ->first();

                $oldStock = $storeProduct ? $storeProduct->stock : 0;
                $diff = $request->stock - $oldStock;

                // Jika stok ditambah, cek apakah stok gudang cukup
                if ($diff > 0 && $product->stock < $diff) {
                    throw new \Exception("Stok gudang tidak cukup! Sisa: {$product->stock}");
                }

                // Update jika ada, Create jika belum ada
                StoreProduct::updateOrCreate(
                    ['store_id' => $request->store_id, 'product_id' => $request->product_id],
                    ['stock' => $request->stock]
                );

                // Kurangi (atau tambah jika diff negatif) stok gudang utama
                $product->decrement('stock', $diff);
            });

            return back()->with('message', 'Data stok cabang berhasil diperbarui!');
        } catch (\Exception $e) {
            // Mengirim error ke form.errors.message di Vue
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        // Mengarahkan fungsi update ke store untuk efisiensi code
        return $this->store($request);
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $sp = StoreProduct::findOrFail($id);
                // Kembalikan stok cabang ke gudang utama sebelum dihapus
                Product::where('id', $sp->product_id)->increment('stock', $sp->stock);
                $sp->delete();
            });

            return back()->with('message', 'Stok ditarik kembali ke gudang pusat.');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Gagal menarik stok.']);
        }
    }
}