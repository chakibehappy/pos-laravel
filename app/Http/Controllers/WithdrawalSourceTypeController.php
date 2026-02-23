<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalSourceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

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

        if (empty($sortField)) {
            $sortField = 'created_at';
        }
        
        $data = WithdrawalSourceType::query()
            ->where('status', 0) // Hanya tampilkan yang aktif (Manual Filter)
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
        $adminEmail = Auth::user()->email;
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
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
                        'created_by' => $posUserId,
                        'status'     => 0 // Default Aktif
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

        // Simpan Single
        $request->validate(['name' => 'required|string|max:255']);
        $name = strtoupper($request->name);
        
        $source = WithdrawalSourceType::create([
            'name'       => $name,
            'created_by' => $posUserId,
            'status'     => 0
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
            'created_by' => $posUserId,
            'status'     => 0,      // Pastikan tetap aktif
            'deleted_at' => null    // Reset jika sebelumnya terarsip
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
     * Hapus Data (  Manual)
     */
    public function destroy($id)
    {
        try {
            $sourceType = WithdrawalSourceType::findOrFail($id);
            $posUserId = $this->getPosUserId();

            // Cek apakah sumber dana ini pernah digunakan di transaksi (opsional namun disarankan)
            if ($sourceType->withdrawals()->exists()) {
                return back()->withErrors(['error' => 'Gagal! Sumber dana ini sudah memiliki riwayat transaksi dan tidak bisa dihapus, hanya bisa diarsip.']);
            }

            // LOG ACTIVITY DELETE (Archive)
            ActivityLogger::log(
                'delete',
                'withdrawal_source_types',
                $id,
                "Menghapus/Mengarsipkan sumber dana penarikan: {$sourceType->name}",
                $posUserId
            );

            // Manual  
            $sourceType->update([
                'status' => 2,
                'deleted_at' => now()
            ]);
            
            return back()->with('message', 'Data berhasil diarsipkan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus data.']);
        }
    }
}