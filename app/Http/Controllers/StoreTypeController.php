<?php

namespace App\Http\Controllers;

use App\Models\StoreType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

class StoreTypeController extends Controller
{
    /**
     * Tampilkan data dengan relasi creator (pos_users).
     */
    public function index(Request $request)
    {
        $query = StoreType::query()
            ->with(['creator']) 
            ->latest();

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('StoreTypes/Index', [
            'types' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search'])
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
     * Simpan data (Single Update atau Batch Create).
     */
    public function store(Request $request)
    {
        $posUserId = $this->getPosUserId();

        // --- MODE EDIT (SINGLE UPDATE) ---
        if ($request->id) {
            $request->validate([
                'name' => 'required|string|max:255|unique:store_types,name,' . $request->id,
            ]);

            return DB::transaction(function () use ($request, $posUserId) {
                $type = StoreType::findOrFail($request->id);
                $oldData = $type->getRawOriginal();

                $type->update([
                    'name'       => $request->name,
                    'created_by' => $posUserId 
                ]);

                // LOG ACTIVITY UPDATE
                ActivityLogger::log(
                    'update',
                    'store_types',
                    $type->id,
                    "Memperbarui jenis usaha: {$oldData['name']} menjadi {$request->name}",
                    $posUserId,
                    ['old' => $oldData, 'new' => $type->getAttributes()],
                    null
                );

                return redirect()->back()->with('success', 'Data berhasil diperbarui!');
            });
        } 
        
        // --- MODE CREATE (BATCH DATA) ---
        else {
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.name' => 'required|string|max:255|unique:store_types,name',
            ], [
                'items.*.name.unique' => 'Salah satu nama jenis usaha sudah terdaftar.'
            ]);

            DB::transaction(function () use ($request, $posUserId) {
                foreach ($request->items as $item) {
                    $newType = StoreType::create([
                        'name'       => $item['name'],
                        'created_by' => $posUserId
                    ]);

                    // LOG ACTIVITY CREATE (Per Item)
                    ActivityLogger::log(
                        'create',
                        'store_types',
                        $newType->id,
                        "Menambah jenis usaha baru: {$newType->name}",
                        $posUserId,
                        ['old' => null, 'new' => $newType->getAttributes()],
                        null
                    );
                }
            });

            return redirect()->back()->with('success', 'Batch data berhasil disimpan!');
        }
    }

    /**
     * Hapus data.
     */
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $type = StoreType::findOrFail($id);
            $oldData = $type->getRawOriginal();
            $posUserId = $this->getPosUserId();
            
            $typeName = $type->name;
            $type->delete();

            // LOG ACTIVITY DELETE
            ActivityLogger::log(
                'delete',
                'store_types',
                $id,
                "Menghapus jenis usaha: {$typeName}",
                $posUserId,
                ['old' => $oldData, 'new' => null],
                null
            );

            return redirect()->back()->with('success', 'Tipe Usaha berhasil dihapus!');
        });
    }
}