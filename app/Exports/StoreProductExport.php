<?php

namespace App\Exports;

use App\Models\Store;
use App\Models\ProductCategory;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StoreProductExport implements WithMultipleSheets
{
    protected $query;
    protected $categoryId;
    protected $storeId;

    public function __construct($query, $categoryId = null, $storeId = null)
    {
        $this->query = $query;
        $this->categoryId = $categoryId;
        $this->storeId = $storeId;
    }

    public function sheets(): array
    {
        $sheets = [];

        // KONDISI 1: User memilih Toko Spesifik tapi TIDAK memilih Kategori
        if ($this->storeId && !$this->categoryId) {
            $store = Store::find($this->storeId);
            $categories = ProductCategory::all();

            foreach ($categories as $category) {
                $categoryQuery = clone $this->query;
                $categoryQuery->whereHas('product', function($q) use ($category) {
                    $q->where('product_category_id', $category->id);
                });

                // Hanya tambahkan sheet jika kategori tersebut ada produknya di toko itu
                $sheets[] = new StoreProductPerStoreSheet($categoryQuery, $store->name, $category->name);
            }
        } 
        // KONDISI 2: User TIDAK memilih Toko (Logika sebelumnya: Banyak Toko)
        else if (!$this->storeId) {
            $stores = Store::all();
            $categoryName = $this->categoryId ? ProductCategory::find($this->categoryId)->name : 'All';
            
            foreach ($stores as $store) {
                $storeQuery = clone $this->query;
                $storeQuery->where('store_id', $store->id);
                
                $sheets[] = new StoreProductPerStoreSheet($storeQuery, $store->name, $categoryName);
            }
        } 
        // KONDISI 3: User memilih Keduanya (Satu Toko & Satu Kategori)
        else {
            $store = Store::find($this->storeId);
            $categoryName = ProductCategory::find($this->categoryId)->name;
            $sheets[] = new StoreProductPerStoreSheet($this->query, $store->name, $categoryName);
        }

        return $sheets;
    }
}