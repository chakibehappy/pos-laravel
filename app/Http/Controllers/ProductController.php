<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StoreType;
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

        if (empty($sortField)) {
            $sortField = 'updated_at';
        }

        $products = Product::with(['category', 'store', 'unitType'])
            ->where('products.status', 0) 
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
                'store_type_id' => $product->store_type_id, // Menambahkan store_type_id ke response
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
                'type_stock' => (int) $product->type_stock, 
                'image_url' => $product->image ? asset('storage/' . $product->image) : null,
                'created_at' => $product->updated_at->format('d/m/Y H:i'),
                'created_by' => $posUser->name ?? 'System',
            ];
        });

        return Inertia::render('Products/Index', [
            'products' => $products,
            'storeTypes' => StoreType::where('status', 0)->get(['id', 'name']), // Mengirim storeTypes sesuai kebutuhan prop di frontend
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
                'store_type_id' => 'nullable|exists:store_types,id', // Menambahkan validasi store_type_id
                'name'                => 'required|string|max:150',
                'sku'                 => 'nullable|string|max:50',
                'buying_price'        => 'required|numeric|min:0',
                'selling_price'       => 'required|numeric|min:0',
                'type_stock'          => 'required|in:0,1', 
                'image'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            ]);

            $posUserAudit = DB::table('pos_users')->where('username', auth()->user()->email)->first();
            $posUserId = $posUserAudit ? $posUserAudit->id : null;
            
            $oldData = null;
            $changeDetails = ""; 

            if ($request->id) {
                $existingProduct = Product::find($request->id);
                if ($existingProduct) {
                    $oldData = $existingProduct->getRawOriginal();
                    
                    // --- LOGIKA DETEKSI PERUBAHAN ---
                    $fieldsToWatch = [
                        'store_type_id' => 'Tipe Toko', // Menambahkan deteksi perubahan Tipe Toko
                        'name'          => 'Nama',
                        'sku'           => 'SKU',
                        'buying_price'  => 'Harga Beli',
                        'selling_price' => 'Harga Jual',
                        'type_stock'    => 'Jenis Stok' 
                    ];

                    $changes = [];
                    foreach ($fieldsToWatch as $field => $label) {
                        if ($oldData[$field] != $request->$field) {
                            $oldVal = $oldData[$field];
                            $newVal = $request->$field;

                            if (str_contains($field, 'price')) {
                                $oldVal = number_format($oldVal);
                                $newVal = number_format($newVal);
                            }

                            if ($field === 'type_stock') {
                                $oldVal = $oldVal == 1 ? 'Tidak Terbatas' : 'Terbatas';
                                $newVal = $newVal == 1 ? 'Tidak Terbatas' : 'Terbatas';
                            }

                            if ($field === 'store_type_id') {
                                $oldStore = StoreType::find($oldVal);
                                $newStore = StoreType::find($newVal);
                                $oldVal = $oldStore ? $oldStore->name : 'Semua Tipe Usaha';
                                $newVal = $newStore ? $newStore->name : 'Semua Tipe Usaha';
                            }

                            $changes[] = "$label dari ($oldVal) menjadi ($newVal)";
                        }
                    }
                    
                    if (!empty($changes)) {
                        $changeDetails = " " . implode(", ", $changes);
                    }
                }
            }

            // Menyertakan store_type_id ke dalam data array untuk eksekusi query database
            $data = $request->only(['product_category_id', 'unit_type_id', 'store_type_id', 'name', 'sku', 'buying_price', 'selling_price', 'type_stock']);
            $data['status'] = 0;
            $data['deleted_at'] = null;

            if (!$request->id) {
                $data['created_by'] = $posUserId;
                $data['stock'] = 0; 
            }

            if ($request->hasFile('image')) {
                if ($request->id) {
                    $prodForImg = Product::find($request->id);
                    if ($prodForImg && $prodForImg->image) {
                        Storage::disk('public')->delete($prodForImg->image);
                    }
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

            $product = Product::updateOrCreate(['id' => $request->id], $data);

            $logAction = $request->id ? 'update' : 'create';
            $logMessage = $request->id 
                ? "Memperbarui produk {$product->name}:" . ($changeDetails ?: " Tidak ada perubahan data signifikan")
                : "Membuat produk baru: " . $product->name;

            ActivityLogger::log(
                $logAction,
                'products',
                $product->id,
                $logMessage,
                $posUserId,
                ['old' => $oldData, 'new' => $product->getAttributes()],
                null
            );

            return back()->with('message', 'Data berhasil diproses!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal simpan: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $oldData = $product->getRawOriginal();

        $posUserAudit = DB::table('pos_users')->where('username', auth()->user()->email)->first();
        $posUserId = $posUserAudit ? $posUserAudit->id : null;

        ActivityLogger::log(
            'delete',
            'products',
            $id,
            "Menghapus produk: {$product->name}",
            $posUserId,
            ['old' => $oldData, 'new' => array_merge($product->getAttributes(), ['status' => 2])],
            null
        );

        $product->update([
            'status' => 2,
            'deleted_at' => now()
        ]);

        return back()->with('message', 'Produk berhasil diarsipkan!');
    }
}