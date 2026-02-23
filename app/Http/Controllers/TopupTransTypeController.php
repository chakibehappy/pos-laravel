<?php

namespace App\Http\Controllers;

use App\Models\TopupTransType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

class TopupTransTypeController extends Controller
{
    /**
     * Menampilkan data dengan Paginasi, Search, Dynamic Sorting, dan Eager Loading pos_users.
     */
    public function index(Request $request)
    {
        // Menangkap parameter sorting dari DataTable.vue
        $sortField = $request->input('sort', 'created_at'); 
        $sortDirection = $request->input('direction', 'desc');

        if (empty($sortField)) {
            $sortField = 'created_at';
        }
        
        $data = TopupTransType::query()
            ->where('status', 0) // Hanya tampilkan yang aktif
            ->with(['creator']) 
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('TopupTransType/Index', [
            'data' => $data,
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    /**
     * Logika Privat: Mapping User Admin ke ID PosUser.
     */
    private function getPosUserId()
    {
        $adminEmail = Auth::user()->email;
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
        return $posUser ? $posUser->id : null;
    }

    /**
     * Simpan data secara BATCH.
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.type' => 'required|string|max:50',
        ]);

        $posUserId = $this->getPosUserId();

        DB::transaction(function () use ($request, $posUserId) {
            foreach ($request->items as $item) {
                $newType = TopupTransType::create([
                    'name'       => $item['name'],
                    'type'       => $item['type'],
                    'created_by' => $posUserId,
                    'status'     => 0, // Pastikan status aktif
                ]);

                // LOG ACTIVITY
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
        $posUserId = $this->getPosUserId();

        $item->update([
            'name'       => $request->name,
            'type'       => $request->type,
            'created_by' => $posUserId,
            'status'     => 0,      // Pastikan status tetap aktif saat update
            'deleted_at' => null    // Reset jika sebelumnya pernah diarsip
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
     * Hapus data (  Manual).
     */
    public function destroy($id)
    {
        try {
            $item = TopupTransType::findOrFail($id);
            $posUserId = $this->getPosUserId();

            // LOG ACTIVITY
            ActivityLogger::log(
                'delete',
                'topup_trans_types',
                $id,
                "Menghapus tipe transaksi topup  : {$item->name} ({$item->type})",
                $posUserId
            );

            // Manual  : Ubah status ke 2 dan isi deleted_at
            $item->update([
                'status' => 2,
                'deleted_at' => now()
            ]);

            return back()->with('message', 'Data berhasil diarsipkan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus data.']);
        }
    }
}