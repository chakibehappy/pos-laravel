<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\ProductTest;
use App\Models\Store;
use App\Models\ProductCategory;
use App\Models\UnitType;
use App\Models\StoreType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Helpers\ActivityLogger;

class ProductTestController extends Controller
{
    public function index(Request $request)
    {
        $sortField = $request->input('sort', 'updated_at');
        $sortDirection = $request->input('direction', 'desc');

        if (empty($sortField)) {
            $sortField = 'updated_at';
        }

        $products = ProductTest::with(['category', 'unitType'])
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

        $targetStoreId = $request->input('store_id');

        $products->getCollection()->transform(function ($product) use ($targetStoreId) {
            $posUser = DB::table('pos_users')->where('id', $product->created_by)->first();
            
            $currentStock = 0;
            if ($targetStoreId) {
                $currentStock = DB::table('store_products')
                    ->where('product_id', $product->id)
                    ->where('store_id', $targetStoreId)
                    ->value('stock') ?? 0;
            }

            return [
                'id' => $product->id,
                'product_category_id' => $product->product_category_id,
                'category_name' => $product->category->name ?? 'Umum',
                'unit_type_id' => $product->unit_type_id,
                'unit_name' => $product->unitType->name ?? '-',
                'name' => $product->name,
                'sku' => $product->sku,
                'buying_price' => $product->buying_price,
                'selling_price' => $product->selling_price,
                'stock' => $currentStock, 
                'type_stock' => $product->type_stock, 
                'image_url' => $product->image ? asset('storage/' . $product->image) : null,
                'created_at' => $product->updated_at->format('d/m/Y H:i'),
                'created_by' => $posUser->name ?? 'System',
            ];
        });

        return Inertia::render('ProductTests/Index', [
            'products' => $products,
            'all_products_reference' => ProductTest::where('status', 0)->get(['id', 'name', 'sku', 'buying_price', 'selling_price', 'product_category_id', 'unit_type_id', 'type_stock']),
            'stores' => Store::where('status', 0)->get(['id', 'name', 'store_type_id']),
            'categories' => ProductCategory::where('status', 0)->get(['id', 'name']),
            'unitTypes' => UnitType::all(['id', 'name']),
            'storeTypes' => StoreType::where('status', 0)->get(['id', 'name']), 
            'filters' => $request->only(['search', 'category', 'sort', 'direction', 'store_id'])
        ]);
    }

    public function getStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required',
            'store_id'   => 'required',
        ]);

        $storeProduct = DB::table('store_products')
            ->where('product_id', $request->product_id)
            ->where('store_id', $request->store_id)
            ->first();

        return response()->json([
            'stock' => $storeProduct ? (int) $storeProduct->stock : 0
        ]);
    }

    public function store(Request $request)
    {
        $posUserAudit = DB::table('pos_users')->where('username', auth()->user()->email)->first();
        $posUserId = $posUserAudit ? $posUserAudit->id : null;

        // --- PROSES BATCH ---
        if ($request->has('products_batch')) {
            $request->validate([
                'store_id' => 'required|numeric',
                'products_batch' => 'required|array|min:1',
            ]);

            DB::beginTransaction();
            try {
                foreach ($request->products_batch as $item) {
                    $productId = $item['product_reference_id'] ?? null;

                    if ($item['status_tab'] === 'belum_terdaftar') {
                        $newProduct = ProductTest::create([
                            'product_category_id' => $item['product_category_id'],
                            'unit_type_id'        => $item['unit_type_id'],
                            'store_type_id'       => $item['store_type_id'] ?? null, 
                            'type_stock'          => $item['type_stock'] ?? 0, 
                            'name'                => $item['name'],
                            'sku'                 => $item['sku'] ?? null,
                            'buying_price'        => $item['buying_price'] ?? 0,
                            'selling_price'       => $item['selling_price'] ?? 0,
                            'status'              => 0,
                            'created_by'          => $posUserId,
                            'stock'               => 0 
                        ]);
                        $productId = $newProduct->id;

                        ActivityLogger::log('create', 'products', $productId, "Membuat produk baru via Batch: {$item['name']}", $posUserId, ['new' => $newProduct->getAttributes()], null);
                    }

                    $existingStock = DB::table('store_products')
                        ->where('store_id', $request->store_id)
                        ->where('product_id', $productId)
                        ->first();

                    if ($existingStock) {
                        DB::table('store_products')
                            ->where('id', $existingStock->id)
                            ->update([
                                'stock'      => $existingStock->stock + intval($item['stock']),
                                'created_by' => $posUserId,
                                'updated_at' => now()
                            ]);
                    } else {
                        DB::table('store_products')->insert([
                            'store_id'   => $request->store_id,
                            'product_id' => $productId,
                            'stock'      => intval($item['stock']),
                            'created_by' => $posUserId,
                            'status'     => 0,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }

                    ActivityLogger::log('update', 'store_products', $productId, "Alokasi stok produk ID {$productId} ke Toko ID {$request->store_id} sebanyak {$item['stock']} unit", $posUserId, null, null);
                }

                DB::commit();
                return back()->with('message', 'Batch produk & alokasi stok toko berhasil diproses bersamaan!');
            } catch (\Exception $e) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Gagal memproses batch alokasi: ' . $e->getMessage()]);
            }
        }

        // --- PROSES SINGLE INPUT ---
        try {
            $request->validate([
                'id'                  => 'nullable|numeric',
                'product_category_id' => 'required|exists:product_categories,id',
                'unit_type_id'        => 'required|exists:unit_types,id',
                'store_type_id'       => 'nullable|exists:store_types,id',
                'type_stock'          => 'required|in:0,1', 
                'name'                => 'required|string|max:150',
                'sku'                 => 'nullable|string|max:50',
                'buying_price'        => 'required|numeric|min:0',
                'selling_price'       => 'required|numeric|min:0',
                'image'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            ]);
            
            $oldData = null;
            $changeDetails = ""; 

            if ($request->id) {
                $existingProduct = ProductTest::find($request->id);
                if ($existingProduct) {
                    $oldData = $existingProduct->getRawOriginal();
                    
                    $fieldsToWatch = [
                        'name'          => 'Nama',
                        'sku'           => 'SKU',
                        'buying_price'  => 'Harga Beli',
                        'selling_price' => 'Harga Jual'
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

                            $changes[] = "$label dari ($oldVal) menjadi ($newVal)";
                        }
                    }
                    
                    if (!empty($changes)) {
                        $changeDetails = " " . implode(", ", $changes);
                    }
                }
            }

            $data = $request->only(['product_category_id', 'unit_type_id', 'store_type_id', 'type_stock', 'name', 'sku', 'buying_price', 'selling_price']);
            $data['status'] = 0;
            $data['deleted_at'] = null;

            if (!$request->id) {
                $data['created_by'] = $posUserId;
                $data['stock'] = 0;
            }

            if ($request->hasFile('image')) {
                if ($request->id) {
                    $prodForImg = ProductTest::find($request->id);
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

            $product = ProductTest::updateOrCreate(['id' => $request->id], $data);

            $logAction = $request->id ? 'update' : 'create';
            $logMessage = $request->id 
                ? "Memperbarui produk {$product->name}:" . ($changeDetails ?: " Tidak ada perubahan data signifikan")
                : "Membuat produk baru: " . $product->name;

            ActivityLogger::log($logAction, 'products', $product->id, $logMessage, $posUserId, ['old' => $oldData, 'new' => $product->getAttributes()], null);

            return back()->with('message', 'Data master berhasil diproses!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal simpan data master: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $product = ProductTest::findOrFail($id);
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