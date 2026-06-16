<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Product;
use App\Models\StoreType;
use App\Models\ProductCategory;
use App\Models\StoreProduct;
use App\Exports\StoreProductExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;
use Illuminate\Support\Str;

class StoreProductController extends Controller
{
    public function index(Request $request)
    {
        $query = StoreProduct::with(['store', 'product.unitType'])
            ->join('stores', 'store_products.store_id', '=', 'stores.id')
            ->join('products', 'store_products.product_id', '=', 'products.id')
            ->leftJoin('pos_users', 'store_products.created_by', '=', 'pos_users.id')
            ->where('store_products.status', '!=', 2)
            ->select(
                'store_products.*', 
                'stores.name as store_name', 
                'products.name as product_name',
                'products.buying_price as product_buying_price',
                'products.selling_price as product_selling_price',
                'products.sku as product_sku',
                'pos_users.name as creator_name'
            );

        // 1. Logika Pencarian
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('products.name', 'like', "%{$request->search}%")
                  ->orWhere('products.sku', 'like', "%{$request->search}%")
                  ->orWhere('stores.name', 'like', "%{$request->search}%");
            });
        }

        // 2. Logika Filter
        if ($request->filled('store_id')) {
            $query->where('store_products.store_id', $request->store_id);
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

        // 3. LOGIKA SORTING DINAMIS
        $sort = $request->input('sort');
        $direction = $request->input('direction', 'desc');

        $allowedSorts = [
            'store_name'            => 'stores.name',
            'product_name'          => 'products.name',
            'product_sku'           => 'products.sku',
            'product_buying_price'  => 'products.buying_price',
            'product_selling_price' => 'products.selling_price',
            'stock'                 => 'store_products.stock',
            'updated_at'            => 'store_products.updated_at'
        ];

        if (isset($allowedSorts[$sort])) {
            $query->orderBy($allowedSorts[$sort], $direction);
        } else {
            $query->orderBy('store_products.updated_at', 'desc');
        }

        return Inertia::render('StoreProducts/Index', [
            'stocks' => $query->paginate(10)->withQueryString(),
            'stores' => Store::where('stores.status', '!=', 2)->get(['id', 'name', 'store_type_id']),
            'storeTypes' => StoreType::all(['id', 'name']),
            'products' => Product::where('products.status', '!=', 2)->get(['id', 'name', 'sku', 'buying_price', 'selling_price','product_category_id','type_stock']),
            'categories' => ProductCategory::all(['id', 'name']),
            'filters' => $request->only(['search', 'store_id', 'store_type_id', 'product_category_id', 'sort', 'direction']),
        ]);
    }

    public function getStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required',
            'store_id'   => 'required',
        ]);

        $storeProduct = StoreProduct::where('product_id', $request->product_id)
            ->where('store_id', $request->store_id)
            ->where('status', '!=', 2)
            ->first();

        return response()->json([
            'stock' => $storeProduct ? (int) $storeProduct->stock : 0
        ]);
    }

    public function store(Request $request)
    {
        // 1. Validasi Input Dinamis
        if ($request->has('batch') && is_array($request->batch)) {
            $request->validate([
                'batch.*.store_id'     => 'required|exists:stores,id',
                'batch.*.name'         => 'required|string|max:255', 
                'batch.*.stock'        => 'required|integer|min:0',
                'batch.*.buying_price' => 'required|numeric|min:0',
                'batch.*.selling_price'=> 'required|numeric|min:0',
            ]);
        } else {
            $request->validate([
                'store_id'   => 'required|exists:stores,id',
                'product_id' => 'required|exists:products,id',
                'stock'      => 'required|integer|min:0',
            ]);
        }

        return DB::transaction(function () use ($request) {
            $adminEmail = auth()->user()->email;
            $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
            $createdBy = $posUser ? $posUser->id : null;

            // KONDISI A: Request Tunggal (Edit Modal)
            if (!$request->has('batch')) {
                $this->processItem($request->all(), $createdBy);
                return back()->with('message', 'Data stok cabang berhasil diperbarui!');
            }

            // KONDISI B: Request Batch Alokasi (Mendukung Produk Baru & Terdaftar)
            $items = $request->batch;
            if (empty($items)) {
                return back()->withErrors(['message' => 'Data alokasi batch kosong.']);
            }

            $storeId = $items[0]['store_id'];
            $store = Store::findOrFail($storeId);

            // Buat induk Nota Pembelian (Purchases)
            $purchaseId = DB::table('purchases')->insertGetId([
                'store_id'    => $storeId,
                'total_harga' => 0.00, 
                'status'      => 0,
                'created_by'  => $createdBy,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $logDetails = [];
            $purchaseDetailsData = [];
            $grandTotalHarga = 0.00;

            foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;

            // LALUAN PROSES JIKA PRODUK BELUM TERDAFTAR (BARU)
            if (!$productId) {
                // Generate SKU otomatis berupa kombinasi BRG- dan string acak jika kosong
                $sku = !empty($item['sku']) ? $item['sku'] : 'BRG-' . strtoupper(Str::random(6));

                $newProduct = Product::create([
                    'name'                => strtoupper($item['name']),
                    'sku'                 => strtoupper($sku),
                    'product_category_id' => $item['product_category_id'] ?: null,
                    'type_stock'          => $item['type_stock'] ?? '0',
                    'buying_price'        => $item['buying_price'] ?: 0,
                    'selling_price'       => $item['selling_price'] ?: 0,
                    'status'              => 0, // Set status aktif (0)
                ]);

                $productId = $newProduct->id;
                $product = $newProduct;
            } else {
                // JIKA PRODUK SUDAH TERDAFTAR, UPDATE HARGA BELI DAN HARGA JUALNYA DI TB PRODUCTS
                $product = Product::findOrFail($productId);
                $product->update([
                    'buying_price'  => $item['buying_price'] ?: $product->buying_price,
                    'selling_price' => $item['selling_price'] ?: $product->selling_price,
                ]);
                
                // Refresh data object agar kalkulasi nota di bawah menggunakan harga terbaru
                $product = $product->fresh();
            }

            $inputQty = (int) $item['stock']; 

            // Cari data relasi stok lama di cabang berdasarkan store_id dan product_id
            $existing = StoreProduct::where('store_id', $storeId)
                ->where('product_id', $productId)
                ->first();

            if ($existing) {
                if ($existing->status == 2) {
                    $existing->update([
                        'stock'      => $inputQty,
                        'status'     => 0,
                        'deleted_at' => null
                    ]);
                    $newStockTarget = $inputQty;
                } else {
                    $existing->increment('stock', $inputQty);
                    $newStockTarget = $existing->fresh()->stock;
                }
            } else {
                StoreProduct::create([
                    'store_id'   => $storeId,
                    'product_id' => $productId,
                    'stock'      => $inputQty,
                    'created_by' => $createdBy,
                    'status'     => 0
                ]);
                $newStockTarget = $inputQty;
            }

            $calculatedTotal = $product->buying_price * $inputQty;
            $grandTotalHarga += $calculatedTotal;

            $purchaseDetailsData[] = [
                'purchase_id'  => $purchaseId,
                'product_id'   => $productId,
                'product_name' => $product->name,
                'buying_price' => $product->buying_price,
                'qty'          => $inputQty,
                'total'        => $calculatedTotal,
            ];

            $logDetails[] = "{$product->name} (+{$inputQty}, Total Akhir: {$newStockTarget})";
        }

            // Simpan rincian data transaksi ke detail nota pembelian
            if (!empty($purchaseDetailsData)) {
                DB::table('purchase_details')->insert($purchaseDetailsData);
            }

            // Perbarui total_harga pada induk transaksi purchases
            DB::table('purchases')
                ->where('id', $purchaseId)
                ->update(['total_harga' => $grandTotalHarga]);

            $detailsString = implode(', ', $logDetails);
            $reqSupplier = $request->input('supplier_name', 'Umum/Tanpa Supplier');
            ActivityLogger::log(
                'create',
                'purchases',
                $purchaseId,
                "Alokasi stok massal di cabang {$store->name} otomatis menerbitkan Nota Pembelian #{$purchaseId}. Supplier: {$reqSupplier}. Detail: {$detailsString}",
                $createdBy,
                ['new' => ['purchase_id' => $purchaseId, 'total_harga' => $grandTotalHarga]],
                $storeId
            );

            return back()->with('message', "Stok berhasil dialokasikan dan produk baru berhasil didaftarkan!");
        });
    }

    private function processItem(array $data, $createdBy)
    {
        $existing = StoreProduct::where('store_id', $data['store_id'])
            ->where('product_id', $data['product_id'])
            ->first();

        $oldStock = $existing ? $existing->stock : 0;
        $oldData = $existing ? $existing->getRawOriginal() : null;
        $isRestoring = ($existing && $existing->status == 2);
        
        $logType = ($existing && !$isRestoring) ? "update" : "create";
        $actionLabel = ($existing && !$isRestoring) ? "Memperbarui" : "Menambah";

        $stockChangeInfo = ($existing && !$isRestoring) 
            ? "dari {$oldStock} menjadi {$data['stock']}" 
            : "menjadi {$data['stock']}";

        $sp = StoreProduct::updateOrCreate(
            ['store_id' => $data['store_id'], 'product_id' => $data['product_id']],
            [
                'stock'      => $data['stock'], 
                'created_by' => $createdBy,
                'status'     => 0, 
                'deleted_at' => null 
            ]
        );

        $product = Product::find($data['product_id']);
        $store = Store::find($data['store_id']);

        ActivityLogger::log(
            $logType,
            'store_products',
            $sp->id,
            "{$actionLabel} stok produk {$product->name} di {$store->name} {$stockChangeInfo}",
            $createdBy,
            ['old' => $oldData, 'new' => $sp->getAttributes()],
            $data['store_id']
        );
    }

    public function update(Request $request, $id)
    {
        return $this->store($request);
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            try {
                $sp = StoreProduct::with(['product', 'store'])->findOrFail($id);
                $oldData = $sp->getRawOriginal();
                
                $adminEmail = auth()->user()->email;
                $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
                $userId = $posUser ? $posUser->id : null;

                // Soft Delete Manual (Archived)
                $sp->update([
                    'status'     => 2,
                    'deleted_at' => now()
                ]);

                ActivityLogger::log(
                    'delete',
                    'store_products',
                    $id,
                    "Menghapus record stok produk: {$sp->product->name} dari toko {$sp->store->name}",
                    $userId,
                    ['old' => $oldData, 'new' => $sp->getAttributes()],
                    $sp->store_id
                );

                return back()->with('message', 'Data stok cabang berhasil dihapus.');
            } catch (\Exception $e) {
                return back()->withErrors(['message' => 'Gagal menghapus data stok.']);
            }
        });
    }
    
    public function export(Request $request)
    {
        $query = StoreProduct::where('status', '!=', 2);

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('product_category_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('product_category_id', $request->product_category_id);
            });
        }

        $storeLabel = $request->filled('store_id') ? Store::find($request->store_id)->name : 'Semua-Toko';
        $categoryLabel = $request->filled('product_category_id') ? ProductCategory::find($request->product_category_id)->name : 'Semua-Kategori';
        
        $fileName = Str::slug("stok-{$storeLabel}-{$categoryLabel}") . '.xlsx';

        return Excel::download(
            new StoreProductExport($query, $request->product_category_id, $request->store_id), 
            $fileName
        );
    }
}