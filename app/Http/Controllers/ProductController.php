<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\ProductCategory;
use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

// Perbaikan Import: Menggunakan ImageManager v3 secara langsung
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProductController extends Controller
{
    public function index(Request $request) {
        // 1. Query dengan Filter & Search
        $products = Product::with(['category', 'store', 'unitType'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->category, function ($query, $category) {
                $query->where('product_category_id', $category);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // 2. Transformasi Data
        $products->getCollection()->transform(function ($product) {
            return [
                'id' => $product->id,
                'store_id' => $product->store_id,
                'store_name' => $product->store->name ?? 'N/A',
                'product_category_id' => $product->product_category_id,
                'category_name' => $product->category->name ?? 'Umum',
                'unit_type_id' => $product->unit_type_id,
                'unit_name' => $product->unitType->name ?? '-',
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
            'unitTypes' => UnitType::all(['id', 'name']),
            'filters' => $request->only(['search', 'category'])
        ]);
    }

    public function store(Request $request) {
        $request->validate([
            'id'                  => 'nullable|numeric',
            'store_id'            => 'required|exists:stores,id',
            'product_category_id' => 'required|exists:product_categories,id',
            'unit_type_id'        => 'required|exists:unit_types,id',
            'name'                => 'required|string|max:150',
            'sku'                 => 'nullable|string|max:50',
            'buying_price'        => 'required|numeric|min:0',
            'selling_price'       => 'required|numeric|min:0',
            'stock'               => 'required|integer',
            'image'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $data = $request->only([
            'store_id', 
            'product_category_id', 
            'unit_type_id',
            'name', 
            'sku', 
            'buying_price', 
            'selling_price', 
            'stock'
        ]);

        $product = $request->id ? Product::find($request->id) : null;

        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($product && $product->image) {
                Storage::disk('public')->delete($product->image);
            }

            // --- PROSES KOMPRESI GAMBAR V3 ---
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.webp';
            
            // Inisialisasi Manager dengan Driver GD
            $manager = new ImageManager(new Driver());
            
            // Baca, Resize ke 800px, dan simpan sebagai WebP (Kualitas 70%)
            $image = $manager->read($file);
            $image->scale(width: 800);
            $encoded = $image->toWebp(70);

            $path = 'products/' . $filename;
            Storage::disk('public')->put($path, (string) $encoded);
            
            $data['image'] = $path;
        }

        Product::updateOrCreate(['id' => $request->id], $data);

        return back()->with('message', 'Product saved successfully!');
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