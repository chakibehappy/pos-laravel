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
     * Tampilan Utama dengan Paginasi, Search, dan Sorting
     */
    public function index(Request $request)
    {
        // Menangkap parameter sorting, default ke created_at desc
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        $data = WithdrawalSourceType::query()
            ->with(['creator']) // Eager load relasi ke pos_users
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'LIKE', '%' . $search . '%');
            })
            // Logika Sorting Dinamis
            ->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('WithdrawalSourceType/Index', [
            'data' => $data,
            'filters' => $request->only(['search', 'sort', 'direction']),
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
            return back()->with('message', 'Batch data berhasil disimpan.');
        }

        // Simpan Single (Fallback jika form tidak menggunakan array items)
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

        return back()->with('message', 'Data berhasil disimpan.');
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

        return back()->with('message', 'Data berhasil diperbarui.');
    }

    /**
     * Hapus Data
     */
    public function destroy($id)
    {
        try {
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
            
            return back()->with('message', 'Data berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus data.']);
        }
    }
}