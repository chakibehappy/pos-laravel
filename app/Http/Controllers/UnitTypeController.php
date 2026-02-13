<?php

namespace App\Http\Controllers;

use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger; // Import Helper

class UnitTypeController extends Controller
{
    /**
     * Menampilkan daftar satuan.
     * Disesuaikan untuk mendukung Pagination & Search.
     */
    public function index(Request $request)
    {
        return Inertia::render('UnitTypes/Index', [
            'units' => UnitType::query()
                ->with(['creator']) // Load relasi creator dari pos_users
                ->when($request->search, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->latest()
                ->paginate(10) // WAJIB paginate agar DataTable tidak error
                ->withQueryString(),
            'filters' => $request->only(['search'])
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
        
        // Identifikasi tipe log sebelum eksekusi
        $logType = $request->id ? 'update' : 'create';
        $actionLabel = $request->id ? 'Memperbarui' : 'Membuat';

        $unit = UnitType::updateOrCreate(
            ['id' => $request->id],
            [
                'name' => $request->name,
                'created_by' => $posUserId // Tercantum di kolom created_by
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
     * Menghapus satuan.
     */
    public function destroy($id)
    {
        $unit = UnitType::findOrFail($id);
        
        // Ambil ID admin untuk audit log
        $posUserId = $this->getPosUserId();

        // LOG ACTIVITY (Sebelum hapus)
        ActivityLogger::log(
            'delete',
            'unit_types',
            $id,
            "Menghapus satuan: {$unit->name}",
            $posUserId
        );

        // Update created_by sebelum delete agar log audit di kolom created_by mencatat siapa yang menghapus
        $unit->update(['created_by' => $posUserId]);
        
        $unit->delete();

        return back()->with('message', 'Satuan berhasil dihapus!');
    }
}