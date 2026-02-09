<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductCategoryController extends Controller
{
    /**
     * Menampilkan daftar kategori dengan Pagination & Search.
     */
    public function index(Request $request)
    {
        return Inertia::render('ProductCategories/Index', [
            'categories' => ProductCategory::query()
                ->when($request->search, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->latest()
                ->paginate(10) // Wajib paginate untuk DataTable.vue
                ->withQueryString(),
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * Menyimpan kategori baru atau memperbarui kategori lama.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id'   => 'nullable|numeric',
            'name' => 'required|string|max:100|unique:product_categories,name,' . $request->id,
        ], [
            'name.unique' => 'Nama kategori sudah ada!',
            'name.required' => 'Nama kategori wajib diisi.'
        ]);

        ProductCategory::updateOrCreate(
            ['id' => $request->id],
            ['name' => $request->name]
        );

        return back()->with('message', 'Kategori berhasil disimpan!');
    }

    /**
     * Menghapus kategori.
     */
    public function destroy($id)
    {
        $category = ProductCategory::findOrFail($id);
        
        // Cek jika kategori masih dipakai produk
        if ($category->products()->exists()) {
            return back()->with('error', 'Gagal! Kategori masih digunakan oleh produk.');
        }

        $category->delete();

        return back()->with('message', 'Kategori berhasil dihapus!');
    }
}