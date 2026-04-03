<?php

namespace App\Http\Controllers;

use App\Models\ExpenseType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

class ExpenseTypeController extends Controller
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
        
        $data = ExpenseType::query()
            ->where('status', 0) 
            ->with(['creator']) 
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'LIKE', '%' . $search . '%');
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('ExpenseType/Index', [
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
                    $type = ExpenseType::create([
                        'name'       => $name,
                        'created_by' => $posUserId,
                        'status'     => 0
                    ]);

                    // LOG ACTIVITY BATCH
                    ActivityLogger::log(
                        'create',
                        'expense_types',
                        $type->id,
                        "Menambah tipe pengeluaran (Batch): {$name}",
                        $posUserId,
                        ['old' => null, 'new' => $type->getAttributes()],
                        null
                    );
                }
            });
            return back()->with('message', 'Batch data berhasil disimpan.');
        }

        // Logika Single Store
        $request->validate(['name' => 'required|string|max:255']);
        $name = strtoupper($request->name);
        
        $type = ExpenseType::create([
            'name'       => $name,
            'created_by' => $posUserId,
            'status'     => 0
        ]);

        // LOG ACTIVITY SINGLE
        ActivityLogger::log(
            'create',
            'expense_types',
            $type->id,
            "Menambah tipe pengeluaran: {$name}",
            $posUserId,
            ['old' => null, 'new' => $type->getAttributes()],
            null
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
        $expenseType = ExpenseType::findOrFail($id);
        
        // Simpan snapshot data lama
        $oldData = $expenseType->getRawOriginal();
        $newName = strtoupper($request->name);
        
        $expenseType->update([
            'name'       => $newName,
            'created_by' => $posUserId,
            'status'     => 0,
            'deleted_at' => null 
        ]);

        // LOG ACTIVITY UPDATE
        ActivityLogger::log(
            'update',
            'expense_types',
            $id,
            "Memperbarui tipe pengeluaran: {$oldData['name']} -> {$newName}",
            $posUserId,
            ['old' => $oldData, 'new' => $expenseType->getAttributes()],
            null
        );

        return back()->with('message', 'Data berhasil diperbarui.');
    }

    /**
     * Hapus Data (Manual Archive/Soft Delete manual)
     */
    public function destroy($id)
    {
        try {
            $expenseType = ExpenseType::findOrFail($id);
            $posUserId = $this->getPosUserId();

            // Cek relasi transaksi sebelum hapus
            if (method_exists($expenseType, 'transactions') && $expenseType->transactions()->exists()) {
                return back()->withErrors(['error' => 'Gagal! Tipe pengeluaran ini sudah digunakan dalam transaksi dan tidak bisa dihapus.']);
            }

            // Simpan snapshot sebelum diupdate
            $oldData = $expenseType->getRawOriginal();

            // Archive status (status 2 = Deleted/Archived)
            $expenseType->update([
                'status' => 2,
                'deleted_at' => now()
            ]);

            // LOG ACTIVITY DELETE
            ActivityLogger::log(
                'delete',
                'expense_types',
                $id,
                "Menghapus/Mengarsipkan tipe pengeluaran: {$expenseType->name}",
                $posUserId,
                ['old' => $oldData, 'new' => $expenseType->getAttributes()],
                null
            );

            return back()->with('message', 'Data berhasil diarsipkan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus data.']);
        }
    }
}