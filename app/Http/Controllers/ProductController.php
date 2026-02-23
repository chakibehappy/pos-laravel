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
use App\Helpers\ActivityLogger;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $sortField = $request->input('sort', 'updated_at');
        $sortDirection = $request->input('direction', 'desc');

        // Mengambil data dengan status 0 (Aktif)
        $products = Product::with(['category', 'store', 'unitType'])
            ->where('status', 0) 
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->category, function ($query, $categoryId) {
                $query->where('product_category_id', $categoryId);
            })
            ->orderBy($sortField, $sortDirection)
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
                'stock' => $product->stock,
                'image_url' => $product->image ? asset('storage/' . $product->image) : null,
                'created_at' => $product->updated_at->format('d/m/Y H:i'),
                'created_by' => $posUser->name ?? 'System',
            ];
        });

        return Inertia::render('Products/Index', [
            'products' => $products,
            'stores' => Store::where('status', 0)->get(['id', 'name']),
            'categories' => ProductCategory::where('status', 0)->get(['id', 'name']),
            'unitTypes' => UnitType::all(['id', 'name']),
            'filters' => $request->only(['search', 'category', 'sort', 'direction'])
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

            // Set status ke 0 (Aktif) dan reset deleted_at
            $data['status'] = 0;
            $data['deleted_at'] = null;

            $product = $request->id ? Product::find($request->id) : null;

            if (!$product) {
                $userEmail = auth()->user()->email;
                $posUser = DB::table('pos_users')->where('username', $userEmail)->first();
                if ($posUser) {
                    $data['created_by'] = $posUser->id;
                }
                $data['stock'] = 0; 
            }

            if ($request->hasFile('image')) {
                if ($product && $product->image) {
                    Storage::disk('public')->delete($product->image);
                }

                $file = $request->file('image');
                $filename = time() . '_' . uniqid() . '.png';
                
                $manager = new ImageManager(new Driver());
                $image = $manager->read($file);
                $image->scale(width: 800);
                $encoded = $image->toPng(); 

                $path = 'products/' . $filename;
                Storage::disk('public')->put($path, (string) $encoded);
                $data['image'] = $path;
            }

            $logType = $request->id ? 'update' : 'create';
            $logDesc = ($request->id ? 'Memperbarui' : 'Membuat') . " produk: " . $request->name;

            $savedProduct = Product::updateOrCreate(['id' => $request->id], $data);

            $posUserAudit = DB::table('pos_users')->where('username', auth()->user()->email)->first();
            ActivityLogger::log(
                $logType,
                'products',
                $savedProduct->id,
                $logDesc,
                $posUserAudit ? $posUserAudit->id : null
            );

            return back()->with('message', 'Data berhasil diproses!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal simpan: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        $posUserAudit = DB::table('pos_users')->where('username', auth()->user()->email)->first();
        ActivityLogger::log(
            'delete',
            'products',
            $id,
            "Menghapus produk  : {$product->name}",
            $posUserAudit ? $posUserAudit->id : null
        );

        // Opsional: Jika ingin gambar tetap ada saat diarsip, hapus bagian Storage::delete ini
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
            $product->image = null;
        }

        //   Manual: Ubah status ke 2 DAN isi deleted_at
        $product->update([
            'status' => 2,
            'deleted_at' => now()
        ]);

        return back()->with('message', 'Product archived!');
    }
}