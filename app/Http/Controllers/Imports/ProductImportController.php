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
use Illuminate\Pagination\LengthAwarePaginator;
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
     * TAHAP 1: Membaca file Excel, memetakan kolom secara cerdas, 
     * dan menampilkan preview menggunakan DataTable.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);

        try {
            $importData = [];

            // Proses pembacaan file
            Excel::import(new class($importData, $this) implements ToCollection, WithHeadingRow {
                private $data;
                private $parent;
                
                // Kamus alias untuk mendukung berbagai variasi header Excel
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

                    // Ambil header asli dari baris pertama
                    $actualHeaders = array_keys($rows->first()->toArray());
                    $map = $this->buildColumnMap($actualHeaders);

                    // Validasi minimal: Nama Produk harus ada
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
                            // Bersihkan header dari karakter non-alfanumerik untuk perbandingan
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

            // Simpan data mentah ke session agar bisa diproses saat store()
            session(['pending_import_products' => $importData]);

            /**
             * PENTING: Membungkus array ke Paginator.
             * Ini mencegah error 'reading data' di DataTable.vue karena sistem tersebut 
             * mengharapkan objek dengan struktur { data: [], meta: {}, links: {} }.
             */
            $total = count($importData);
            $paginatedData = new LengthAwarePaginator(
                $importData, 
                $total, 
                $total > 0 ? $total : 15, 
                1,
                ['path' => route('products.import.preview')]
            );

            return inertia('Import/PreviewProduct', [
                'importData' => $paginatedData
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
        $data = session('pending_import_products');
        
        if (!$data || empty($data)) {
            return redirect()->route('products.import.index')
                ->withErrors(['file' => 'Data tidak ditemukan atau sesi telah berakhir.']);
        }

        try {
            foreach ($data as $item) {
                // Gunakan SKU jika tersedia, jika tidak gunakan Nama sebagai pembanding unik
                $identifier = !empty($item['sku']) ? ['sku' => $item['sku']] : ['name' => $item['name']];

                Product::updateOrCreate(
                    $identifier,
                    [
                        'name'                => $item['name'],
                        'sku'                 => $item['sku'],
                        'product_category_id' => $this->resolveCategoryId($item['category_raw']),
                        'unit_type_id'        => $this->resolveUnitId($item['unit_raw']),
                        'buying_price'        => $item['buying_price'],
                        'selling_price'       => $item['selling_price'],
                        'stock'               => 0,
                        'created_by'          => Auth::id(),
                        'status'              => 0,
                    ]
                );
            }

            session()->forget('pending_import_products');

            return redirect()->route('products.index')
                ->with('success', 'Import Berhasil! Data produk telah diperbarui.');

        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        }
    }

    /**
     * Mencari ID Kategori berdasarkan nama, atau membuat baru jika belum ada.
     */
    public function resolveCategoryId($input) 
    {
        $input = trim($input);
        if (is_numeric($input)) return (int)$input;

        return ProductCategory::firstOrCreate(
            ['name' => $input],
            ['created_by' => Auth::id(), 'status' => 0]
        )->id;
    }

    /**
     * Mencari ID Satuan berdasarkan nama, atau membuat baru jika belum ada.
     */
    public function resolveUnitId($input) 
    {
        $input = trim($input);
        if (is_numeric($input)) return (int)$input;

        return UnitType::firstOrCreate(
            ['name' => $input],
            ['created_by' => Auth::id(), 'status' => 0]
        )->id;
    }

    /**
     * Menghapus karakter pemisah ribuan dan mengonversi string harga ke float.
     */
    public function formatPrice($value) 
    {
        if (empty($value)) return 0;
        if (is_string($value)) {
            // Menghapus titik (ribuan) dan mengganti koma dengan titik (desimal) jika ada
            $value = str_replace(['.', ','], ['', '.'], $value);
        }
        return (float)$value;
    }
}