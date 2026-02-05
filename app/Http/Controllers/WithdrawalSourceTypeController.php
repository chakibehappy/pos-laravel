<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalSourceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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
                    WithdrawalSourceType::create([
                        'name'       => strtoupper($item['name']),
                        'created_by' => $posUserId // Simpan ID pos_users
                    ]);
                }
            });
            return back();
        }

        // Simpan Single (Fallback)
        $request->validate(['name' => 'required|string|max:255']);
        WithdrawalSourceType::create([
            'name'       => strtoupper($request->name),
            'created_by' => $posUserId
        ]);

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
        
        $sourceType->update([
            'name'       => strtoupper($request->name),
            'created_by' => $posUserId // Update pengedit terakhir
        ]);

        return back();
    }

    /**
     * Hapus Data
     */
    public function destroy($id)
    {
        $sourceType = WithdrawalSourceType::findOrFail($id);
        $sourceType->delete();
        
        return back();
    }
}