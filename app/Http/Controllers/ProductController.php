<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index() {
        $products = Product::join('stores', 'products.store_id', '=', 'stores.id')
            ->select('products.*', 'stores.name as store_name')
            ->latest('products.created_at')
            ->paginate(10);

        

        // Menambahkan URL lengkap untuk gambar agar mudah diakses di Vue
        $products->getCollection()->transform(function ($product) {
            $product->image_url = $product->image 
                ? asset('storage/' . $product->image) 
                : null;
            return $product;
        });

        return Inertia::render('Products/Index', [
            'products' => $products,
            'stores' => Store::all(['id', 'name']),
        ]);
    }

    public function store(Request $request) {
        $request->validate([
            'id'            => 'nullable|numeric',
            'store_id'      => 'required|exists:stores,id',
            'name'          => 'required|string|max:150',
            'sku'           => 'nullable|string|max:50',
            'buying_price'  => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock'         => 'required|integer',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Validasi file gambar
        ]);

        $data = $request->only(['store_id', 'name', 'sku', 'buying_price', 'selling_price', 'stock']);

        // Cari data produk jika ini adalah proses update
        $product = $request->id ? Product::find($request->id) : null;

        if ($request->hasFile('image')) {
            // 1. Hapus gambar lama jika ada file baru yang diupload
            if ($product && $product->image) {
                Storage::disk('public')->delete($product->image);
            }
            
            // 2. Simpan gambar baru ke folder 'products' di disk 'public'
            $path = $request->file('image')->store('products', 'public');
            $data['image'] = $path;
        }

        Product::updateOrCreate(['id' => $request->id], $data);

        return back()->with('message', 'Product saved!');
    }

    public function destroy($id) {
        $product = Product::findOrFail($id);
        
        // Hapus file gambar dari storage sebelum menghapus data di database
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();
        
        return back()->with('message', 'Product deleted!');
    }
}