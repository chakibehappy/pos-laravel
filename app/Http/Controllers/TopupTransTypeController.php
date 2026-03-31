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
            ->where('status', '!=', 2) // Tampilkan data yang tidak dihapus/archived
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
     * Helper: Mapping User Admin ke ID PosUser.
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

        return DB::transaction(function () use ($request, $posUserId) {
            foreach ($request->items as $item) {
                $newType = TopupTransType::create([
                    'name'       => $item['name'],
                    'type'       => $item['type'],
                    'created_by' => $posUserId,
                    'status'     => 0, 
                ]);

                // LOG ACTIVITY CREATE
                ActivityLogger::log(
                    'create',
                    'topup_trans_types',
                    $newType->id,
                    "Menambahkan tipe transaksi topup: {$newType->name} ({$newType->type})",
                    $posUserId,
                    ['old' => null, 'new' => $newType->getAttributes()]
                );
            }
            return back()->with('message', 'Batch data transaksi berhasil ditambahkan.');
        });
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

        return DB::transaction(function () use ($request, $id) {
            $item = TopupTransType::findOrFail($id);
            $oldData = $item->getRawOriginal();
            $posUserId = $this->getPosUserId();

            $item->update([
                'name'       => $request->name,
                'type'       => $request->type,
                'created_by' => $posUserId,
                'status'     => 0,
                'deleted_at' => null
            ]);

            // LOG ACTIVITY UPDATE
            ActivityLogger::log(
                'update',
                'topup_trans_types',
                $id,
                "Memperbarui tipe transaksi topup: {$oldData['name']} menjadi {$request->name}",
                $posUserId,
                ['old' => $oldData, 'new' => $item->getAttributes()]
            );

            return back()->with('message', 'Data berhasil diperbarui.');
        });
    }

    /**
     * Hapus data (Soft Delete Manual).
     */
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            try {
                $item = TopupTransType::findOrFail($id);
                $oldData = $item->getRawOriginal();
                $posUserId = $this->getPosUserId();

                // Ubah status ke 2 (Archived)
                $item->update([
                    'status' => 2,
                    'deleted_at' => now()
                ]);

                // LOG ACTIVITY DELETE
                ActivityLogger::log(
                    'delete',
                    'topup_trans_types',
                    $id,
                    "Menghapus tipe transaksi topup: {$item->name} ({$item->type})",
                    $posUserId,
                    ['old' => $oldData, 'new' => $item->getAttributes()]
                );

                return back()->with('message', 'Data berhasil diarsipkan.');
            } catch (\Exception $e) {
                return back()->withErrors(['error' => 'Gagal menghapus data.']);
            }
        });
    }
}