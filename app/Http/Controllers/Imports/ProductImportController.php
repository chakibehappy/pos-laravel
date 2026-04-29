<?php

namespace App\Http\Controllers\Imports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class ProductImportController extends Controller
{
    /**
     * Menampilkan halaman import di Vue
     */
    public function index()
    {
        return inertia('Import/Product', [
            'categories' => ProductCategory::all(['id', 'name']),
            'unitTypes' => UnitType::all(['id', 'name']),
        ]);
    }

    /**
     * Memproses file Excel dengan logika Smart Identifier
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);

        try {
            $finalMap = [];

            Excel::import(new class($finalMap) implements ToCollection, WithHeadingRow {
                private $columnMap;

                /**
                 * DICTIONARY ALIAS (Mendukung urutan kolom acak)
                 */
                private $dictionary = [
                    'name'          => ['nama', 'nama_produk', 'item', 'produk', 'product_name', 'item_name'],
                    'sku'           => ['sku', 'kode', 'barcode', 'kode_barang', 'sn', 'article_no'],
                    'id_kategori'   => ['kategori', 'category', 'id_kategori', 'jenis', 'group'],
                    'id_satuan'     => ['satuan', 'unit', 'id_satuan', 'uom', 'measure'],
                    'buying_price'  => ['harga_modal', 'modal', 'harga_beli', 'buying_price', 'base_price', 'cost'],
                    'selling_price' => ['harga_jual', 'jual', 'selling_price', 'price', 'harga'],
                ];

                public function __construct(&$finalMap) {
                    $this->columnMap = &$finalMap;
                }

                public function collection(Collection $rows)
                {
                    if ($rows->isEmpty()) return;

                    // 1. IDENTIFIKASI HEADER (Apapun urutannya)
                    $actualHeaders = array_keys($rows->first()->toArray());
                    $this->columnMap = $this->buildColumnMap($actualHeaders);

                    // 2. VALIDASI WAJIB
                    if (!isset($this->columnMap['name'])) {
                        throw new \Exception("Kolom 'Nama Produk' tidak ditemukan.");
                    }
                    if (!isset($this->columnMap['id_kategori'])) {
                        throw new \Exception("Kolom 'Kategori' wajib ada di file Excel.");
                    }

                    // 3. EKSEKUSI DATA
                    foreach ($rows as $row) {
                        $nameValue = $row[$this->columnMap['name']] ?? null;
                        if (empty($nameValue)) continue;

                        $catRaw    = $row[$this->columnMap['id_kategori']] ?? 'Umum';
                        $unitRaw   = $row[$this->columnMap['id_satuan'] ?? ''] ?? 'pcs';
                        
                        // SKU diset null jika kolom tidak ada atau data kosong (tanpa random generate)
                        $skuValue  = isset($this->columnMap['sku']) ? ($row[$this->columnMap['sku']] ?? null) : null;
                        
                        $bPrice    = $row[$this->columnMap['buying_price'] ?? ''] ?? 0;
                        $sPrice    = $row[$this->columnMap['selling_price'] ?? ''] ?? 0;

                        /**
                         * Gunakan Nama sebagai identifier utama jika SKU kosong, 
                         * agar updateOrCreate tidak menimpa baris yang salah.
                         */
                        $identifier = $skuValue ? ['sku' => $skuValue] : ['name' => $nameValue];

                        Product::updateOrCreate(
                            $identifier,
                            [
                                'name'                => $nameValue,
                                'sku'                 => $skuValue,
                                'product_category_id' => $this->resolveCategoryId($catRaw),
                                'unit_type_id'        => $this->resolveUnitId($unitRaw),
                                'buying_price'        => $this->formatPrice($bPrice),
                                'selling_price'       => $this->formatPrice($sPrice),
                                'stock'               => 0, 
                                'created_by'          => Auth::id(),
                                'status'              => 0,
                            ]
                        );
                    }
                }

                private function buildColumnMap($headers)
                {
                    $map = [];
                    foreach ($this->dictionary as $dbField => $aliases) {
                        foreach ($headers as $header) {
                            $cleanHeader = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $header));
                            foreach ($aliases as $alias) {
                                $cleanAlias = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $alias));
                                if ($cleanHeader === $cleanAlias) {
                                    $map[$dbField] = $header;
                                    break 2;
                                }
                            }
                        }
                    }
                    return $map;
                }

                private function resolveCategoryId($input)
                {
                    $input = trim($input);
                    if (is_numeric($input)) return (int)$input;

                    $category = ProductCategory::firstOrCreate(
                        ['name' => $input],
                        ['created_by' => Auth::id(), 'status' => 0]
                    );

                    return $category->id;
                }

                private function resolveUnitId($input)
                {
                    $input = trim($input);
                    if (is_numeric($input)) return (int)$input;

                    $unit = UnitType::firstOrCreate(
                        ['name' => $input],
                        ['created_by' => Auth::id(), 'status' => 0]
                    );

                    return $unit->id;
                }

                private function formatPrice($value)
                {
                    if (empty($value)) return 0;
                    if (is_string($value)) $value = str_replace(['.', ','], ['', '.'], $value);
                    return (float)$value;
                }
            }, $request->file('file'));

            return redirect()->route('products.index')->with('success', 'Import Berhasil! Produk telah diperbarui.');

        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Gagal: ' . $e->getMessage()]);
        }
    }
}