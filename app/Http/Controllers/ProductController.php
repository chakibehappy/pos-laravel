<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\ProductCategory;
use App\Models\UnitType; // 1. Tambahkan import Model UnitType
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index() {
        // 2. Tambahkan 'unitType' ke dalam relasi with
        $products = Product::with(['category', 'store', 'unitType'])
            ->latest()
            ->paginate(10);

        $products->getCollection()->transform(function ($product) {
            return [
                'id' => $product->id,
                'store_id' => $product->store_id,
                'store_name' => $product->store->name ?? 'N/A',
                'product_category_id' => $product->product_category_id,
                'category_name' => $product->category->name ?? 'Umum',
                'unit_type_id' => $product->unit_type_id, // 3. Tambahkan ID satuan
                'unit_name' => $product->unitType->name ?? '-', // 4. Tambahkan Nama satuan
                'name' => $product->name,
                'sku' => $product->sku,
                'buying_price' => $product->buying_price,
                'selling_price' => $product->selling_price,
                'stock' => $product->stock,
                'image' => $product->image,
                'image_url' => $product->image ? asset('storage/' . $product->image) : null,
            ];
        });

        return Inertia::render('Products/Index', [
            'products' => $products,
            'stores' => Store::all(['id', 'name']),
            'categories' => ProductCategory::all(['id', 'name']),
            'unitTypes' => UnitType::all(['id', 'name']), // 5. Kirim data satuan ke dropdown
        ]);
    }

    public function store(Request $request) {
        $request->validate([
            'id'                  => 'nullable|numeric',
            'store_id'            => 'required|exists:stores,id',
            'product_category_id' => 'required|exists:product_categories,id',
            'unit_type_id'        => 'required|exists:unit_types,id', // 6. Tambahkan validasi satuan
            'name'                => 'required|string|max:150',
            'sku'                 => 'nullable|string|max:50',
            'buying_price'        => 'required|numeric|min:0',
            'selling_price'       => 'required|numeric|min:0',
            'stock'               => 'required|integer',
            'image'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // 7. Masukkan unit_type_id ke array data
        $data = $request->only([
            'store_id', 
            'product_category_id', 
            'unit_type_id', // Pastikan masuk ke array
            'name', 
            'sku', 
            'buying_price', 
            'selling_price', 
            'stock'
        ]);

        $product = $request->id ? Product::find($request->id) : null;

        if ($request->hasFile('image')) {
            if ($product && $product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $path = $request->file('image')->store('products', 'public');
            $data['image'] = $path;
        }

        Product::updateOrCreate(['id' => $request->id], $data);

        return back()->with('message', 'Product saved!');
    }

    public function destroy($id) {
        $product = Product::findOrFail($id);
        
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();
        
        return back()->with('message', 'Product deleted!');
    }
}