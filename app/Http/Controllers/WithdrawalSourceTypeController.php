<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalSourceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger; // Import Helper

class WithdrawalSourceTypeController extends Controller
{
    /**
     * Tampilan Utama dengan Paginasi dan Search
     */
    public function index(Request $request)
    {
        $query = WithdrawalSourceType::query()
            ->with(['creator']) // Eager load relasi ke pos_users
            ->latest();

        if ($request->search) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        return Inertia::render('WithdrawalSourceType/Index', [
            'data' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Logika Private untuk mengambil ID pos_users berdasarkan email Admin (users)
     */
    private function getPosUserId()
    {
        // Ambil email dari user yang sedang login di tabel 'users'
        $adminEmail = Auth::user()->email;

        // Cari di tabel 'pos_users' yang username-nya sama dengan email admin tersebut
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();

        // Kembalikan ID jika ditemukan, jika tidak ada kirim null
        return $posUser ? $posUser->id : null;
    }

    /**
     * Simpan Data Baru (Mendukung Batch Store)
     */
    public function store(Request $request)
    {
        $posUserId = $this->getPosUserId();

        if ($request->has('items') && is_array($request->items)) {
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.name' => 'required|string|max:255',
            ]);

            DB::transaction(function () use ($request, $posUserId) {
                foreach ($request->items as $item) {
                    $name = strtoupper($item['name']);
                    $source = WithdrawalSourceType::create([
                        'name'       => $name,
                        'created_by' => $posUserId // Simpan ID pos_users
                    ]);

                    // LOG ACTIVITY BATCH
                    ActivityLogger::log(
                        'create',
                        'withdrawal_source_types',
                        $source->id,
                        "Menambah sumber dana penarikan (Batch): {$name}",
                        $posUserId
                    );
                }
            });
            return back();
        }

        // Simpan Single (Fallback)
        $request->validate(['name' => 'required|string|max:255']);
        $name = strtoupper($request->name);
        
        $source = WithdrawalSourceType::create([
            'name'       => $name,
            'created_by' => $posUserId
        ]);

        // LOG ACTIVITY SINGLE
        ActivityLogger::log(
            'create',
            'withdrawal_source_types',
            $source->id,
            "Menambah sumber dana penarikan: {$name}",
            $posUserId
        );

        return back();
    }

    /**
     * Update Data (Mode Edit)
     */
    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        
        $posUserId = $this->getPosUserId();
        $sourceType = WithdrawalSourceType::findOrFail($id);
        $newName = strtoupper($request->name);
        
        // Simpan nama lama untuk deskripsi log jika diperlukan, 
        // namun untuk konsistensi kita gunakan format standar.
        $sourceType->update([
            'name'       => $newName,
            'created_by' => $posUserId // Update pengedit terakhir
        ]);

        // LOG ACTIVITY UPDATE
        ActivityLogger::log(
            'update',
            'withdrawal_source_types',
            $id,
            "Memperbarui sumber dana penarikan menjadi: {$newName}",
            $posUserId
        );

        return back();
    }

    /**
     * Hapus Data
     */
    public function destroy($id)
    {
        $sourceType = WithdrawalSourceType::findOrFail($id);
        $posUserId = $this->getPosUserId();

        // LOG ACTIVITY DELETE (Sebelum hapus)
        ActivityLogger::log(
            'delete',
            'withdrawal_source_types',
            $id,
            "Menghapus sumber dana penarikan: {$sourceType->name}",
            $posUserId
        );

        $sourceType->delete();
        
        return back();
    }
}