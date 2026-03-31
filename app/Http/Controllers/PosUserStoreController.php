<?php

namespace App\Http\Controllers;

use App\Models\PosUserStore;
use App\Models\Store;
use App\Models\PosUser; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;
use Illuminate\Support\Facades\DB;

class PosUserStoreController extends Controller
{
    public function index(Request $request)
    {
        // Tambahkan filter status != 2 untuk menyembunyikan data yang dihapus
        $query = PosUserStore::with(['posUser', 'store', 'creator'])
            ->where('status', '!=', 2) 
            ->whereHas('posUser', function($q) {
                $q->where('role', '!=', 'developer'); 
            });
        
        // --- LOGIKA PENCARIAN ---
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $term = "%{$request->search}%";
                $q->whereHas('posUser', function($sq) use ($term) {
                    $sq->where('name', 'like', $term);
                })->orWhereHas('store', function($sq) use ($term) {
                    $sq->where('name', 'like', $term);
                });
            });
        }

        // --- FILTER DROPDOWN ---
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('store_type_id')) {
            $query->whereHas('store', function($q) use ($request) {
                $q->where('store_type_id', $request->store_type_id);
            });
        }

        // --- LOGIKA SORTING DINAMIS ---
        $sortField = $request->input('sort');
        $direction = $request->input('direction', 'asc');

        if ($sortField) {
            $tableName = (new PosUserStore())->getTable();

            switch ($sortField) {
                case 'store_name':
                    $query->join('stores', "$tableName.store_id", '=', 'stores.id')
                          ->orderBy('stores.name', $direction)
                          ->select("$tableName.*");
                    break;
                case 'pos_user_name':
                    $query->join('pos_users', "$tableName.pos_user_id", '=', 'pos_users.id')
                          ->orderBy('pos_users.name', $direction)
                          ->select("$tableName.*");
                    break;
                case 'creator_name':
                    $query->leftJoin('pos_users as creators', "$tableName.created_by", '=', 'creators.id')
                          ->orderBy('creators.name', $direction)
                          ->select("$tableName.*");
                    break;
                case 'created_at':
                    $query->orderBy("$tableName.created_at", $direction);
                    break;
                default:
                    $query->latest("$tableName.created_at");
                    break;
            }
        } else {
            $query->latest();
        }

        return Inertia::render('PosUserStores/Index', [
            'resource' => $query->paginate(10)->withQueryString(),
            'posUsers' => PosUser::where('role', '!=', 'developer')->get(['id', 'name']),
            'stores'   => Store::where('status', '!=', 2)->get(['id', 'name']), // Hanya toko aktif
            'storeTypes' => \App\Models\StoreType::all(['id', 'name']),
            'filters'   => $request->only(['search', 'store_id', 'store_type_id', 'sort', 'direction']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pos_user_id' => 'required|exists:pos_users,id', 
            'store_id'    => 'required|exists:stores,id',
        ]);

        return DB::transaction(function () use ($request) {
            $creator = PosUser::where('username', Auth::user()->email)->first();
            $creatorId = $creator ? $creator->id : null;

            // Cek jika relasi ini sudah pernah ada (termasuk yang statusnya 2)
            $existing = PosUserStore::where('pos_user_id', $request->pos_user_id)
                                    ->where('store_id', $request->store_id)
                                    ->first();

            $oldData = $existing ? $existing->getRawOriginal() : null;
            $action = $existing ? 'update' : 'create';

            if ($existing) {
                $existing->update([
                    'status' => 0,
                    'created_by' => $creatorId,
                    'deleted_at' => null
                ]);
                $assignment = $existing;
                $msgLabel = "Mengaktifkan kembali penugasan";
            } else {
                $assignment = PosUserStore::create([
                    'pos_user_id' => $request->pos_user_id,
                    'store_id'    => $request->store_id,
                    'created_by'  => $creatorId,
                    'status'      => 0
                ]);
                $msgLabel = "Menugaskan";
            }

            // LOG ACTIVITY
            $targetUser = PosUser::find($request->pos_user_id);
            $targetStore = Store::find($request->store_id);
            
            ActivityLogger::log(
                $action,
                'pos_user_stores',
                $assignment->id,
                "$msgLabel user {$targetUser->name} ke toko {$targetStore->name}",
                $creatorId,
                ['old' => $oldData, 'new' => $assignment->getAttributes()]
            );

            return back()->with('message', 'Penugasan user ke toko berhasil diproses!');
        });
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'pos_user_id' => 'required|exists:pos_users,id',
            'store_id'    => 'required|exists:stores,id',
        ]);

        return DB::transaction(function () use ($request, $id) {
            // Cek duplikasi akses yang aktif
            $exists = PosUserStore::where('pos_user_id', $request->pos_user_id)
                                  ->where('store_id', $request->store_id)
                                  ->where('status', '!=', 2)
                                  ->where('id', '!=', $id)
                                  ->exists();

            if ($exists) {
                return back()->withErrors([
                    'pos_user_id' => 'USER INI SUDAH MEMILIKI AKSES KE TOKO TERSEBUT!'
                ]);
            }

            $akses = PosUserStore::findOrFail($id);
            $oldData = $akses->getRawOriginal(); // TANGKAP DATA LAMA

            $akses->update([
                'pos_user_id' => $request->pos_user_id,
                'store_id'    => $request->store_id,
                'status'      => 0 
            ]);

            // LOG ACTIVITY
            $creator = PosUser::where('username', Auth::user()->email)->first();
            $targetUser = PosUser::find($request->pos_user_id);
            $targetStore = Store::find($request->store_id);

            ActivityLogger::log(
                'update',
                'pos_user_stores',
                $id,
                "Memperbarui akses user {$targetUser->name} ke {$targetStore->name}",
                $creator ? $creator->id : null,
                ['old' => $oldData, 'new' => $akses->getAttributes()]
            );

            return back()->with('message', 'Akses user berhasil diperbarui!');
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            try {
                $akses = PosUserStore::with(['posUser', 'store'])->findOrFail($id);
                $oldData = $akses->getRawOriginal(); // TANGKAP DATA SEBELUM DI-ARCHIVE
                
                $creator = PosUser::where('username', Auth::user()->email)->first();

                // Soft Delete Manual (Status 2)
                $akses->update([
                    'status' => 2,
                    'deleted_at' => now()
                ]);

                ActivityLogger::log(
                    'delete',
                    'pos_user_stores',
                    $id,
                    "Mencabut akses user {$akses->posUser->name} dari toko {$akses->store->name}",
                    $creator ? $creator->id : null,
                    ['old' => $oldData, 'new' => $akses->getAttributes()]
                );

                return back()->with('message', 'Akses user ke toko telah dicabut.');
            } catch (\Exception $e) {
                return back()->withErrors(['message' => 'Gagal mencabut akses']);
            }
        });
    }
}