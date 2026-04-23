<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Helpers\ActivityLogger;

class StoreController extends Controller
{
    public function index(Request $request) 
    {
        // Filter status != 2 agar data yang di-archive tidak muncul
        $query = Store::join('store_types', 'stores.store_type_id', '=', 'store_types.id')
            ->leftJoin('pos_users', 'stores.created_by', '=', 'pos_users.id')
            ->where('stores.status', '!=', 2)
            ->select(
                'stores.*', 
                'store_types.name as type_name',
                'pos_users.name as creator_name'
            );

        // --- FILTER PENCARIAN ---
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $searchTerm = "%{$request->search}%";
                $q->where('stores.name', 'like', $searchTerm)
                  ->orWhere('stores.keyname', 'like', $searchTerm)
                  ->orWhere('stores.address', 'like', $searchTerm)
                  ->orWhere('store_types.name', 'like', $searchTerm)
                  ->orWhere('pos_users.name', 'like', $searchTerm);
            });
        }

        // --- FILTER TIPE TOKO ---
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('stores.store_type_id', $request->type);
        }

        // --- LOGIKA SORTING DINAMIS ---
        if ($request->filled('sort') && $request->filled('direction')) {
            $sortField = $request->sort;
            $direction = $request->direction === 'desc' ? 'desc' : 'asc';

            $sortMapping = [
                'type_name'    => 'store_types.name',
                'creator_name' => 'pos_users.name',
                'name'         => 'stores.name',
                'keyname'      => 'stores.keyname',
                'created_at'   => 'stores.created_at'
            ];

            $finalSort = $sortMapping[$sortField] ?? $sortField;
            $query->orderBy($finalSort, $direction);
        } else {
            $query->latest('stores.created_at');
        }

        return Inertia::render('Stores/Index', [
            'stores' => $query->paginate(10)->withQueryString(), 
            'store_types' => StoreType::all(['id', 'name']),
            'filters' => $request->only(['search', 'type', 'sort', 'direction']), 
        ]);
    }

    public function store(Request $request) {
    $rules = [
        'name'          => 'required|string|max:150',
        'store_type_id' => 'required|exists:store_types,id',
        'address'       => 'nullable|string',
        'keyname'       => 'required|string|unique:stores,keyname,' . $request->id,
    ];

    if (!$request->id) {
        $rules['password'] = 'required|string|min:4';
    } else {
        $rules['password'] = 'nullable|string|min:4';
    }

    $request->validate($rules);

    $account = DB::table('accounts')->first(['id']);
    if (!$account) {
        return back()->withErrors(['message' => 'Gagal: Tabel Accounts kosong.']);
    }

    $posUser = DB::table('pos_users')
        ->where('username', auth()->user()->email)
        ->first(['id']);

    if (!$posUser) {
        return back()->withErrors([
            'message' => 'Gagal: Email admin tidak ditemukan di tabel pos_users.'
        ]);
    }

    return DB::transaction(function () use ($request, $account, $posUser) {
        $oldData = null;
        $changeDetails = ""; // Variabel untuk menyimpan detail perubahan

        if ($request->id) {
            $existingStore = Store::find($request->id);
            if ($existingStore) {
                $oldData = $existingStore->getRawOriginal();
                
                // --- LOGIKA DETEKSI PERUBAHAN ---
                $fieldsToWatch = [
                    'name'    => 'Nama Toko',
                    'keyname' => 'Keyname',
                    'address' => 'Alamat',
                ];

                $changes = [];
                foreach ($fieldsToWatch as $field => $label) {
                    // Gunakan Str::upper untuk keyname karena di simpan sebagai uppercase
                    $requestVal = ($field === 'keyname') ? Str::upper($request->$field) : $request->$field;
                    
                    if ($oldData[$field] != $requestVal) {
                        $oldVal = $oldData[$field] ?: '-';
                        $newVal = $requestVal ?: '-';
                        $changes[] = "$label dari ($oldVal) menjadi ($newVal)";
                    }
                }

                // Cek Perubahan Tipe Toko (karena melibatkan relasi ID)
                if ($oldData['store_type_id'] != $request->store_type_id) {
                    $oldType = DB::table('store_types')->where('id', $oldData['store_type_id'])->value('name');
                    $newType = DB::table('store_types')->where('id', $request->store_type_id)->value('name');
                    $changes[] = "Tipe Toko dari ($oldType) menjadi ($newType)";
                }

                if (!empty($changes)) {
                    $changeDetails = " " . implode(", ", $changes);
                }
                // --------------------------------

                unset($oldData['password']); // Keamanan
            }
        }

        $updateData = [
            'account_id'    => $account->id,
            'name'          => $request->name,
            'keyname'       => Str::upper($request->keyname),
            'store_type_id' => $request->store_type_id,
            'address'       => $request->address,
            'status'        => 0, 
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        if (!$request->id) {
            $updateData['created_by'] = $posUser->id;
        }

        $actionLabel = $request->id ? "Memperbarui" : "Membuat";
        $logType = $request->id ? "update" : "create";

        $store = Store::updateOrCreate(
            ['id' => $request->id], 
            $updateData
        );

        $newData = $store->getAttributes();
        unset($newData['password']);

        // LOG ACTIVITY dengan detail perubahan data
        $logMessage = $request->id 
            ? "{$actionLabel} data toko {$store->name}:" . ($changeDetails ?: " Tidak ada perubahan data signifikan")
            : "Membuat toko baru: {$store->name}";

        ActivityLogger::log(
            $logType,
            'stores',
            $store->id,
            $logMessage,
            $posUser->id,
            ['old' => $oldData, 'new' => $newData],
            $store->id
        );

        return back()->with('message', 'Data toko berhasil diproses.');
    });
}

    public function destroy($id) {
        return DB::transaction(function () use ($id) {
            $store = Store::findOrFail($id);
            $oldData = $store->getRawOriginal();
            unset($oldData['password']);
            
            $posUser = DB::table('pos_users')
                ->where('username', auth()->user()->email)
                ->first(['id']);

            // Soft Delete Manual
            $store->status = 2;
            $store->deleted_at = now();
            $store->save();

            // LOG ACTIVITY
            ActivityLogger::log(
                'delete',
                'stores',
                $id,
                "Menghapus toko: {$store->name}",
                $posUser ? $posUser->id : null,
                ['old' => $oldData, 'new' => ['status' => 2, 'deleted_at' => $store->deleted_at]],
                $id
            );

            return back()->with('message', 'Toko telah dihapus.');
        });
    }
}