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
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        if (empty($sortField)) {
            $sortField = 'created_at';
        }
        
        $data = WithdrawalSourceType::query()
            ->where('status', 0) 
            ->with(['creator']) 
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'LIKE', '%' . $search . '%');
            })
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

        // Logika Batch Store
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
                        'status'     => 0
                    ]);

                    // LOG ACTIVITY BATCH dengan New Data
                    ActivityLogger::log(
                        'create',
                        'withdrawal_source_types',
                        $source->id,
                        "Menambah sumber dana penarikan (Batch): {$name}",
                        $posUserId,
                        ['old' => null, 'new' => $source->getAttributes()]
                    );
                }
            });
            return back()->with('message', 'Batch data berhasil disimpan.');
        }

        // Logika Single Store
        $request->validate(['name' => 'required|string|max:255']);
        $name = strtoupper($request->name);
        
        $source = WithdrawalSourceType::create([
            'name'       => $name,
            'created_by' => $posUserId,
            'status'     => 0
        ]);

        // LOG ACTIVITY SINGLE dengan New Data
        ActivityLogger::log(
            'create',
            'withdrawal_source_types',
            $source->id,
            "Menambah sumber dana penarikan: {$name}",
            $posUserId,
            ['old' => null, 'new' => $source->getAttributes()]
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
        
        // Simpan snapshot data lama
        $oldData = $sourceType->getRawOriginal();
        $newName = strtoupper($request->name);
        
        $sourceType->update([
            'name'       => $newName,
            'created_by' => $posUserId,
            'status'     => 0,
            'deleted_at' => null 
        ]);

        // LOG ACTIVITY UPDATE dengan Old & New Data
        ActivityLogger::log(
            'update',
            'withdrawal_source_types',
            $id,
            "Memperbarui sumber dana penarikan: {$oldData['name']} -> {$newName}",
            $posUserId,
            ['old' => $oldData, 'new' => $sourceType->getAttributes()]
        );

        return back()->with('message', 'Data berhasil diperbarui.');
    }

    /**
     * Hapus Data (Manual Archive)
     */
    public function destroy($id)
    {
        try {
            $sourceType = WithdrawalSourceType::findOrFail($id);
            $posUserId = $this->getPosUserId();

            // Cek relasi transaksi (asumsi nama relasi: withdrawals)
            if (method_exists($sourceType, 'withdrawals') && $sourceType->withdrawals()->exists()) {
                return back()->withErrors(['error' => 'Gagal! Sumber dana ini sudah memiliki riwayat transaksi dan tidak bisa dihapus.']);
            }

            // Simpan snapshot sebelum diupdate statusnya
            $oldData = $sourceType->getRawOriginal();

            // Archive status (status 2)
            $sourceType->update([
                'status' => 2,
                'deleted_at' => now()
            ]);

            // LOG ACTIVITY DELETE (Archive)
            ActivityLogger::log(
                'delete',
                'withdrawal_source_types',
                $id,
                "Menghapus/Mengarsipkan sumber dana penarikan: {$sourceType->name}",
                $posUserId,
                ['old' => $oldData, 'new' => $sourceType->getAttributes()]
            );

            return back()->with('message', 'Data berhasil diarsipkan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus data.']);
        }
    }
}