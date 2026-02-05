<?php

namespace App\Http\Controllers;

use App\Models\StoreType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StoreTypeController extends Controller
{
    /**
     * Tampilkan data dengan relasi creator (pos_users).
     */
    public function index(Request $request)
    {
        // Mendukung search dan eager load relasi creator
        $query = StoreType::query()
            ->with(['creator']) // Mengambil data pos_users melalui relasi
            ->latest();

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('StoreTypes/Index', [
            'types' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * Logika Private: Mapping User Admin ke ID PosUser.
     */
    private function getPosUserId()
    {
        // 1. Ambil email admin yang sedang login dari tabel 'users'
        $adminEmail = Auth::user()->email;

        // 2. Cari di tabel 'pos_users' yang username-nya sama dengan email admin
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();

        // 3. Kembalikan ID-nya jika ketemu, jika tidak kembalikan null
        return $posUser ? $posUser->id : null;
    }

    /**
     * Simpan data (Single Update atau Batch Create).
     */
    public function store(Request $request)
    {
        // Dapatkan ID pos_users terlebih dahulu
        $posUserId = $this->getPosUserId();

        // Mode Edit (Single Data)
        if ($request->id) {
            $request->validate([
                'name' => 'required|string|max:255|unique:store_types,name,' . $request->id,
            ]);

            StoreType::where('id', $request->id)->update([
                'name'       => $request->name,
                'created_by' => $posUserId // Update pengedit terakhir
            ]);
        } 
        // Mode Create (Batch Data)
        else {
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.name' => 'required|string|max:255|unique:store_types,name',
            ], [
                'items.*.name.unique' => 'Salah satu nama jenis usaha sudah terdaftar.'
            ]);

            DB::transaction(function () use ($request, $posUserId) {
                foreach ($request->items as $item) {
                    StoreType::create([
                        'name'       => $item['name'],
                        'created_by' => $posUserId // Isi dengan ID pos_users
                    ]);
                }
            });
        }

        return redirect()->back()->with('success', 'Data berhasil diproses!');
    }

    /**
     * Hapus data.
     */
    public function destroy($id)
    {
        $type = StoreType::findOrFail($id);
        $type->delete();

        return redirect()->back()->with('success', 'Tipe Usaha berhasil dihapus!');
    }
}