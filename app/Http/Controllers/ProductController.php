<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\ProductCategory;
use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Query standar tanpa join berat atau sinkronisasi massal
        $products = Product::with(['category', 'store', 'unitType'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $products->getCollection()->transform(function ($product) {
            $posUser = DB::table('pos_users')->where('id', $product->created_by)->first();

            return [
                'id' => $product->id,
                'store_name' => $product->store->name ?? 'N/A',
                'product_category_id' => $product->product_category_id,
                'category_name' => $product->category->name ?? 'Umum',
                'unit_type_id' => $product->unit_type_id,
                'unit_name' => $product->unitType->name ?? '-',
                'name' => $product->name,
                'sku' => $product->sku,
                'buying_price' => $product->buying_price,
                'selling_price' => $product->selling_price,
                'stock' => $product->stock, // Menampilkan nilai apa adanya dari database
                'image_url' => $product->image ? asset('storage/' . $product->image) : null,
                'created_at' => $product->updated_at->format('d/m/Y H:i'),
                'created_by' => $posUser->name ?? 'System',
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

    public function store(Request $request)
    {
        try {
            $request->validate([
                'id'                  => 'nullable|numeric',
                'product_category_id' => 'required|exists:product_categories,id',
                'unit_type_id'        => 'required|exists:unit_types,id',
                'name'                => 'required|string|max:150',
                'sku'                 => 'nullable|string|max:50',
                'buying_price'        => 'required|numeric|min:0',
                'selling_price'       => 'required|numeric|min:0',
                'image'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            ]);

            $data = $request->only([
                'product_category_id', 
                'unit_type_id',
                'name', 
                'sku', 
                'buying_price', 
                'selling_price',
            ]);

            $product = $request->id ? Product::find($request->id) : null;

            // Jika TAMBAH BARU
            if (!$product) {
                $userEmail = auth()->user()->email;
                $posUser = DB::table('pos_users')->where('username', $userEmail)->first();
                if ($posUser) {
                    $data['created_by'] = $posUser->id;
                }
                // Set stok ke nilai default 0 agar tidak error database
                $data['stock'] = 0; 
            }

            // Upload Gambar
            // if ($request->hasFile('image')) {
            //     if ($product && $product->image) {
            //         Storage::disk('public')->delete($product->image);
            //     }

            //     $file = $request->file('image');
            //     $filename = time() . '_' . uniqid() . '.webp';
            //     $manager = new ImageManager(new Driver());
            //     $image = $manager->read($file);
            //     $image->scale(width: 800);
            //     $encoded = $image->toWebp(70);

            //     $path = 'products/' . $filename;
            //     Storage::disk('public')->put($path, (string) $encoded);
            //     $data['image'] = $path;
            // }

            Product::updateOrCreate(['id' => $request->id], $data);

            return back()->with('message', 'Data berhasil diproses!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal simpan: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();
        return back()->with('message', 'Product deleted!');
    }
}