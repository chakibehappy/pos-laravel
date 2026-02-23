<?php

namespace App\Http\Controllers;

use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

class UnitTypeController extends Controller
{
    /**
     * Menampilkan daftar satuan dengan Sorting, Pagination & Search.
     */
    public function index(Request $request)
    {
        // Ambil parameter sort, default ke 'id' dan 'desc'
        $sortField = $request->input('sort', 'id');
        $sortDirection = $request->input('direction', 'desc');

        if (empty($sortField)) {
            $sortField = 'id';
        }
        
        return Inertia::render('UnitTypes/Index', [
            'units' => UnitType::query()
                // Global Scope di Model otomatis memfilter status 0
                ->with(['creator']) 
                ->when($request->search, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->orderBy($sortField, $sortDirection)
                ->paginate(10)
                ->withQueryString(),
            
            'filters' => $request->only(['search', 'sort', 'direction'])
        ]);
    }

    /**
     * Logika Private: Mapping User Admin ke ID PosUser.
     */
    private function getPosUserId()
    {
        $adminEmail = Auth::user()->email;
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
        return $posUser ? $posUser->id : null;
    }

    /**
     * Menyimpan satuan baru atau memperbarui satuan lama.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id'   => 'nullable|numeric',
            'name' => 'required|string|max:100|unique:unit_types,name,' . $request->id,
        ], [
            'name.unique' => 'Nama satuan sudah ada!',
            'name.required' => 'Nama satuan wajib diisi.'
        ]);

        $posUserId = $this->getPosUserId();
        
        $logType = $request->id ? 'update' : 'create';
        $actionLabel = $request->id ? 'Memperbarui' : 'Membuat';

        $unit = UnitType::updateOrCreate(
            ['id' => $request->id],
            [
                'name' => $request->name,
                'created_by' => $posUserId,
                'status' => 0,       // Pastikan status aktif
                'deleted_at' => null // Reset deleted_at jika data lama dipulihkan
            ]
        );

        // LOG ACTIVITY
        ActivityLogger::log(
            $logType,
            'unit_types',
            $unit->id,
            "$actionLabel satuan: {$unit->name}",
            $posUserId
        );

        return back()->with('message', 'Satuan berhasil disimpan!');
    }

    /**
     *   satuan (status diubah menjadi 2 dan deleted_at diisi).
     */
    public function destroy($id)
    {
        $unit = UnitType::findOrFail($id);
        
        // Cek jika satuan masih dipakai oleh produk yang AKTIF (status 0)
        $hasProducts = DB::table('products')
            ->where('unit_type_id', $id)
            ->where('status', 0)
            ->exists();

        if ($hasProducts) {
            return back()->withErrors(['error' => 'Gagal! Satuan masih digunakan oleh produk aktif.']);
        }

        $posUserId = $this->getPosUserId();

        // LOG ACTIVITY
        ActivityLogger::log(
            'delete',
            'unit_types',
            $id,
            "Menghapus satuan  : {$unit->name}",
            $posUserId
        );

        //   Manual: Ubah status ke 2 dan isi deleted_at
        $unit->update([
            'status' => 2,
            'deleted_at' => now(),
            'created_by' => $posUserId // Opsional: catat siapa yang menghapus di kolom created_by
        ]);

        return back()->with('message', 'Satuan berhasil diarsipkan!');
    }
}