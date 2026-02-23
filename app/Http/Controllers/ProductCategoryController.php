<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

class ProductCategoryController extends Controller
{
    /**
     * Menampilkan daftar kategori dengan Pagination, Search & Sorting.
     */
    public function index(Request $request)
    {
        // Ambil parameter sort, default ke 'id' dan 'desc'
        $sortField = $request->input('sort', 'id');
        $sortDirection = $request->input('direction', 'desc');

        return Inertia::render('ProductCategories/Index', [
            'categories' => ProductCategory::query()
                ->where('status', 0) // Hanya tampilkan yang aktif
                ->with(['creator']) // Load relasi creator dari pos_users
                ->when($request->search, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                // Logika Sorting Dinamis
                ->orderBy($sortField, $sortDirection)
                ->paginate(10)
                ->withQueryString(),
            
            // Sertakan parameter sort & direction di filters agar UI konsisten
            'filters' => $request->only(['search', 'sort', 'direction'])
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
        
        $logType = $request->id ? 'update' : 'create';
        $actionLabel = $request->id ? 'Memperbarui' : 'Membuat';

        $category = ProductCategory::updateOrCreate(
            ['id' => $request->id],
            [
                'name' => $request->name,
                'created_by' => $posUserId,
                'status' => 0,      // Pastikan status aktif
                'deleted_at' => null // Reset deleted_at jika data dipulihkan
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
     *   kategori (status diubah menjadi 2 dan deleted_at diisi).
     */
    public function destroy($id)
    {
        $category = ProductCategory::findOrFail($id);
        
        // Cek jika kategori masih dipakai oleh produk yang AKTIF (status 0)
        $hasProducts = DB::table('products')
            ->where('product_category_id', $id)
            ->where('status', 0)
            ->exists();

        if ($hasProducts) {
            return back()->withErrors(['error' => 'Gagal! Kategori masih digunakan oleh produk aktif.']);
        }

        $posUserId = $this->getPosUserId();

        // LOG ACTIVITY
        ActivityLogger::log(
            'delete',
            'product_categories',
            $id,
            "Menghapus kategori produk  : {$category->name}",
            $posUserId
        );

        // Ubah status menjadi 2 DAN isi deleted_at secara manual
        $category->update([
            'status' => 2,
            'deleted_at' => now()
        ]);

        return back()->with('message', 'Kategori berhasil diarsipkan!');
    }
}