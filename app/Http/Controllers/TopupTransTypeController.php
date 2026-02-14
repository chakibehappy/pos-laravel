<?php

namespace App\Http\Controllers;

use App\Models\TopupTransType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger; // Import Helper

class TopupTransTypeController extends Controller
{
    /**
     * Menampilkan data dengan Paginasi, Search, Dynamic Sorting, dan Eager Loading pos_users.
     */
    public function index(Request $request)
    {
        // Menangkap parameter sorting dari DataTable.vue
        $sortField = $request->input('sort', 'created_at'); // Default sort ke tanggal
        $sortDirection = $request->input('direction', 'desc'); // Default urutan terbaru

        $data = TopupTransType::query()
            // Mengambil relasi creator (yang terhubung ke tabel pos_users)
            ->with(['creator']) 
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%");
                });
            })
            // Logika Sorting Dinamis
            ->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('TopupTransType/Index', [
            'data' => $data,
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    /**
     * Logika Privat: Mencocokkan Email Admin dengan Username di pos_users.
     */
    private function getPosUserId()
    {
        // 1. Ambil email dari user (admin) yang sedang login di tabel users
        $adminEmail = Auth::user()->email;

        // 2. Cari di tabel pos_users yang username-nya cocok dengan email admin tersebut
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();

        // 3. Jika ketemu ambil id-nya, jika tidak ada return null
        return $posUser ? $posUser->id : null;
    }

    /**
     * Simpan data secara BATCH.
     */
    public function store(Request $request)
    {
        // Validasi input array 'items'
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.type' => 'required|string|max:50',
        ]);

        // Dapatkan ID dari pos_users berdasarkan logika mapping
        $posUserId = $this->getPosUserId();

        // Gunakan Database Transaction agar data tersimpan semua atau tidak sama sekali
        DB::transaction(function () use ($request, $posUserId) {
            foreach ($request->items as $item) {
                $newType = TopupTransType::create([
                    'name'       => $item['name'],
                    'type'       => $item['type'],
                    'created_by' => $posUserId,
                ]);

                // LOG ACTIVITY (Batch)
                ActivityLogger::log(
                    'create',
                    'topup_trans_types',
                    $newType->id,
                    "Menambahkan tipe transaksi topup: {$newType->name} ({$newType->type})",
                    $posUserId
                );
            }
        });

        return back()->with('message', 'Batch data transaksi berhasil ditambahkan.');
    }

    /**
     * Update data (Single Update).
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
        ]);

        $item = TopupTransType::findOrFail($id);
        
        // Dapatkan ID dari pos_users untuk mencatat siapa yang melakukan update
        $posUserId = $this->getPosUserId();

        $item->update([
            'name'       => $request->name,
            'type'       => $request->type,
            'created_by' => $posUserId, 
        ]);

        // LOG ACTIVITY
        ActivityLogger::log(
            'update',
            'topup_trans_types',
            $id,
            "Memperbarui tipe transaksi topup: {$item->name} ({$item->type})",
            $posUserId
        );

        return back()->with('message', 'Data berhasil diperbarui.');
    }

    /**
     * Hapus data.
     */
    public function destroy($id)
    {
        try {
            $item = TopupTransType::findOrFail($id);
            $posUserId = $this->getPosUserId();

            // LOG ACTIVITY (Sebelum hapus)
            ActivityLogger::log(
                'delete',
                'topup_trans_types',
                $id,
                "Menghapus tipe transaksi topup: {$item->name} ({$item->type})",
                $posUserId
            );

            $item->delete();
            return back()->with('message', 'Data berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus! Data mungkin terikat dengan transaksi lain.']);
        }
    }
}