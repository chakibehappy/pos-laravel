<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger; // Import Helper

class ProductCategoryController extends Controller
{
    /**
     * Menampilkan daftar kategori dengan Pagination & Search.
     */
    public function index(Request $request)
    {
        return Inertia::render('ProductCategories/Index', [
            'categories' => ProductCategory::query()
                ->with(['creator']) // Load relasi creator dari pos_users
                ->when($request->search, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * Logika Private: Mapping User Admin ke ID PosUser.
     */
    private function getPosUserId()
    {
        $adminEmail = Auth::user()->email;
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
        return $posUser ? $posUser->id : null;
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

        $posUserId = $this->getPosUserId();
        
        // Identifikasi tipe log sebelum eksekusi
        $logType = $request->id ? 'update' : 'create';
        $actionLabel = $request->id ? 'Memperbarui' : 'Membuat';

        $category = ProductCategory::updateOrCreate(
            ['id' => $request->id],
            [
                'name' => $request->name,
                'created_by' => $posUserId // Tercantum di kolom created_by
            ]
        );

        // LOG ACTIVITY
        ActivityLogger::log(
            $logType,
            'product_categories',
            $category->id,
            "$actionLabel kategori produk: {$category->name}",
            $posUserId
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

        $posUserId = $this->getPosUserId();

        // LOG ACTIVITY (Sebelum hapus)
        ActivityLogger::log(
            'delete',
            'product_categories',
            $id,
            "Menghapus kategori produk: {$category->name}",
            $posUserId
        );

        $category->delete();

        return back()->with('message', 'Kategori berhasil dihapus!');
    }
}