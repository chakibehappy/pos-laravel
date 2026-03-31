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
        $sortField = $request->input('sort', 'id');
        $sortDirection = $request->input('direction', 'desc');

        if (empty($sortField)) {
            $sortField = 'id';
        }
        
        return Inertia::render('ProductCategories/Index', [
            'categories' => ProductCategory::query()
                ->where('status', 0) 
                ->with(['creator']) 
                ->when($request->search, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->orderBy($sortField, $sortDirection)
                ->paginate(10)
                ->withQueryString(),
            
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
        $oldData = null;

        // TANGKAP DATA LAMA JIKA UPDATE
        if ($request->id) {
            $existingCategory = ProductCategory::find($request->id);
            if ($existingCategory) {
                $oldData = $existingCategory->getRawOriginal();
            }
        }
        
        $logType = $request->id ? 'update' : 'create';
        $actionLabel = $request->id ? 'Memperbarui' : 'Membuat';

        $category = ProductCategory::updateOrCreate(
            ['id' => $request->id],
            [
                'name' => $request->name,
                'created_by' => $posUserId,
                'status' => 0,      
                'deleted_at' => null 
            ]
        );

        // LOG ACTIVITY DENGAN PAYLOAD
        ActivityLogger::log(
            $logType,
            'product_categories',
            $category->id,
            "$actionLabel kategori produk: {$category->name}",
            $posUserId,
            ['old' => $oldData, 'new' => $category->getAttributes()]
        );

        return back()->with('message', 'Kategori berhasil disimpan!');
    }

    /**
     * Menghapus kategori (soft delete manual).
     */
    public function destroy($id)
    {
        $category = ProductCategory::findOrFail($id);
        $oldData = $category->getRawOriginal(); // TANGKAP DATA SEBELUM DIUBAH STATUSNYA
        
        // Cek jika kategori masih dipakai oleh produk yang AKTIF (status 0)
        $hasProducts = DB::table('products')
            ->where('product_category_id', $id)
            ->where('status', 0)
            ->exists();

        if ($hasProducts) {
            return back()->withErrors(['error' => 'Gagal! Kategori masih digunakan oleh produk aktif.']);
        }

        $posUserId = $this->getPosUserId();

        // Ubah status menjadi 2 DAN isi deleted_at secara manual
        $category->update([
            'status' => 2,
            'deleted_at' => now()
        ]);

        // LOG ACTIVITY DELETE
        ActivityLogger::log(
            'delete',
            'product_categories',
            $id,
            "Menghapus kategori produk: {$category->name}",
            $posUserId,
            ['old' => $oldData, 'new' => $category->getAttributes()]
        );

        return back()->with('message', 'Kategori berhasil diarsipkan!');
    }
}