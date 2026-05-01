<?php

namespace App\Http\Controllers\Imports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class ProductImportController extends Controller
{
    /**
     * Menampilkan halaman upload awal
     */
    public function index()
    {
        return inertia('Import/Product', [
            'categories' => ProductCategory::all(['id', 'name']),
            'unitTypes' => UnitType::all(['id', 'name']),
        ]);
    }

    /**
     * TAHAP 1: Membaca file Excel dan menampilkan preview.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);

        try {
            $importData = [];

            // Proses pembacaan file dengan Anonymous Class
            Excel::import(new class($importData, $this) implements ToCollection, WithHeadingRow {
                private $data;
                private $parent;
                
                private $dictionary = [
                    'name'          => ['nama', 'nama_produk', 'item', 'produk', 'product_name', 'item_name'],
                    'sku'           => ['sku', 'kode', 'barcode', 'kode_barang', 'sn', 'article_no'],
                    'id_kategori'   => ['kategori', 'category', 'id_kategori', 'jenis', 'group'],
                    'id_satuan'     => ['satuan', 'unit', 'id_satuan', 'uom', 'measure'],
                    'buying_price'  => ['harga_modal', 'modal', 'harga_beli', 'buying_price', 'base_price', 'cost'],
                    'selling_price' => ['harga_jual', 'jual', 'selling_price', 'price', 'harga'],
                ];

                public function __construct(&$importData, $parent) {
                    $this->data = &$importData;
                    $this->parent = $parent;
                }

                public function collection(Collection $rows) {
                    if ($rows->isEmpty()) return;

                    $actualHeaders = array_keys($rows->first()->toArray());
                    $map = $this->buildColumnMap($actualHeaders);

                    if (!isset($map['name'])) {
                        throw new \Exception("Kolom 'Nama Produk' tidak dapat diidentifikasi.");
                    }

                    foreach ($rows as $row) {
                        $nameValue = $row[$map['name'] ?? ''] ?? null;
                        if (empty($nameValue)) continue;

                        $this->data[] = [
                            'name'          => $nameValue,
                            'sku'           => $row[$map['sku'] ?? ''] ?? null,
                            'category_raw'  => $row[$map['id_kategori'] ?? ''] ?? 'Umum',
                            'unit_raw'      => $row[$map['id_satuan'] ?? ''] ?? 'pcs',
                            'buying_price'  => $this->parent->formatPrice($row[$map['buying_price'] ?? ''] ?? 0),
                            'selling_price' => $this->parent->formatPrice($row[$map['selling_price'] ?? ''] ?? 0),
                        ];
                    }
                }

                private function buildColumnMap($headers) {
                    $map = [];
                    foreach ($this->dictionary as $dbField => $aliases) {
                        foreach ($headers as $header) {
                            $cleanH = strtolower(preg_replace('/[^a-z0-9]/', '', $header));
                            foreach ($aliases as $alias) {
                                if ($cleanH === strtolower(preg_replace('/[^a-z0-9]/', '', $alias))) {
                                    $map[$dbField] = $header;
                                    break 2;
                                }
                            }
                        }
                    }
                    return $map;
                }
            }, $request->file('file'));

            $newData = [];
            $identicalData = [];
            $similarData = []; 
            
            // Eager load relasi agar pengecekan efisien
            $existingProducts = Product::with(['category', 'unitType'])
            ->where('status', '!=', 2)
            ->get();

            foreach ($importData as $item) {
                // 1. Konversi kategori/satuan (Mengembalikan Object hasil perbaikan minor)
                $categoryObj = $this->resolveCategoryId($item['category_raw']);
                $unitObj = $this->resolveUnitId($item['unit_raw']);

                $excelCategoryId = $categoryObj->id;
                $excelUnitId = $unitObj->id;

                // Ambil nama langsung dari object (Tanpa query tambahan)
                $item['category_name'] = $categoryObj->name;
                $item['unit_name'] = $unitObj->name;

                // 2. Cek apakah ada Nama yang Sama Persis (Exact Match)
                $exactMatch = $existingProducts->firstWhere('name', $item['name']);
                
                if ($exactMatch) {
                    // CEK IDENTIK
                    $isIdentical = 
                        $exactMatch->name === $item['name'] &&
                        $exactMatch->sku === $item['sku'] &&
                        $exactMatch->product_category_id === $excelCategoryId &&
                        $exactMatch->unit_type_id === $excelUnitId &&
                        (float)$exactMatch->buying_price === (float)$item['buying_price'] &&
                        (float)$exactMatch->selling_price === (float)$item['selling_price'];

                    if ($isIdentical) {
                        $identicalData[] = $item;
                        continue; 
                    }
                }

                // 3. Jika tidak identik, cari Nominasi Kemiripan (Similar)
                $matches = collect();

                foreach ($existingProducts as $dbProduct) {
                    similar_text(strtolower($item['name']), strtolower($dbProduct->name), $percent);
                    
                    if ($percent >= 50) {
                        $matches->push([
                            'name'          => $dbProduct->name,
                            'sku'           => $dbProduct->sku,
                            'category_name' => $dbProduct->category->name ?? 'Umum',
                            'unit_name'     => $dbProduct->unitType->name ?? '-',
                            'buying_price'  => $dbProduct->buying_price,
                            'selling_price' => $dbProduct->selling_price,
                            'similarity'    => round($percent, 2)
                        ]);
                    }
                }

                $topMatches = $matches->sortByDesc('similarity')->take(3)->values();

                // 4. Tentukan masuk kategori Similar atau New Data
                if ($topMatches->isNotEmpty() && $topMatches->first()['similarity'] >= 80) {
                    $similarData[] = [
                        'excel'      => $item,
                        'databases'  => $topMatches,
                        'similarity' => $topMatches->first()['similarity'] 
                    ];
                } else {
                    $newData[] = $item;
                }
            }

            session([
                'pending_new_data' => $newData,
                'pending_similar_data' => $similarData
            ]);

            return inertia('Import/PreviewProduct', [
                'importData' => [
                    'headers' => [
                        ['label' => 'Nama Produk', 'key' => 'name'],
                        ['label' => 'SKU', 'key' => 'sku'],
                        ['label' => 'Kategori', 'key' => 'category_name'],
                        ['label' => 'Satuan', 'key' => 'unit_name'],
                        ['label' => 'Harga Beli', 'key' => 'buying_price', 'align' => 'right'],
                        ['label' => 'Harga Jual', 'key' => 'selling_price', 'align' => 'right'],
                    ],
                    'new_data'  => $newData,
                    'similar'   => $similarData,
                    'identical' => $identicalData,
                    'meta' => [
                        'total_new'       => count($newData),
                        'total_similar'   => count($similarData),
                        'total_identical' => count($identicalData)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Gagal membaca file: ' . $e->getMessage()]);
        }
    }

    /**
     * TAHAP 2: Eksekusi penyimpanan final ke Database
     */
    public function store(Request $request)
    {
        $newData = session('pending_new_data', []);
        $similarData = session('pending_similar_data', []);
        $excludedIndices = $request->input('excluded_indices', []);

        try {
            foreach ($newData as $item) {
                $this->saveProduct($item);
            }

            foreach ($similarData as $index => $item) {
                if (in_array($index, $excludedIndices)) {
                    continue;
                }
                $this->saveProduct($item['excel']);
            }

            // session()->forget(['pending_new_data', 'pending_similar_data']);

            return redirect()->route('products.index')
                ->with('success', 'Import Berhasil!');

        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Gagal: ' . $e->getMessage()]);
        }
        
            // return redirect()->route('products.index')
            //     ->with('success', 'Import Berhasil!');
    }

    private function saveProduct($item) {
        Product::updateOrCreate(
            ['name' => $item['name']],
            [
                'sku'                 => $item['sku'],
                'product_category_id' => $this->resolveCategoryId($item['category_raw'])->id,
                'unit_type_id'        => $this->resolveUnitId($item['unit_raw'])->id,
                'buying_price'        => $item['buying_price'],
                'selling_price'       => $item['selling_price'],
                'stock'               => 0,
                'created_by'          => Auth::id(),
                'status'              => 0, 
            ]
        );
    }

    /**
     * Helper: Mencari Object Kategori atau membuat baru jika tidak ada
     */
    public function resolveCategoryId($input) 
    {
        $input = trim($input);
        if (empty($input)) $input = 'Umum';
        
        // Perbaikan Minor: Mengembalikan Object secara konsisten
        return ProductCategory::firstOrCreate(
            ['name' => $input],
            ['created_by' => Auth::id(), 'status' => 0]
        );
    }

    /**
     * Helper: Mencari Object Satuan atau membuat baru jika tidak ada
     */
    public function resolveUnitId($input) 
    {
        $input = trim($input);
        if (empty($input)) $input = 'pcs';

        // Perbaikan Minor: Mengembalikan Object secara konsisten
        return UnitType::firstOrCreate(
            ['name' => $input],
            ['created_by' => Auth::id(), 'status' => 0]
        );
    }

    /**
     * Helper: Membersihkan format harga
     */
    public function formatPrice($value) 
    {
        if (empty($value)) return 0;
        if (is_string($value) && str_contains($value, '.')) {
            $value = preg_replace('/[^0-9]/', '', $value);
        }
        return (float)$value;
    }
}