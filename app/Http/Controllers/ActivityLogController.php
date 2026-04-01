<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Store;
use App\Models\PosUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    /**
     * Menampilkan daftar log aktivitas dengan Filter, Sorting & Join Dinamis
     */
    public function index(Request $request)
    {
        // 1. Menangkap parameter sorting dari UI
        $sortField = $request->input('sort', 'created_at'); 
        $sortDirection = $request->input('direction', 'desc'); 

        // 2. Query dasar dengan Eager Loading (Relasi: executor & store)
        $query = ActivityLog::with(['executor', 'store']);

        // 3. LOGIKA SORTING
        if ($sortField === 'store_name') {
            $query->leftJoin('stores', 'activity_logs.store_id', '=', 'stores.id')
                  ->select('activity_logs.*') 
                  ->orderBy('stores.name', $sortDirection);
        } 
        elseif ($sortField === 'user_name') {
            // Join ke pos_users untuk mengurutkan berdasarkan nama Eksekutor
            $query->leftJoin('pos_users', 'activity_logs.created_by', '=', 'pos_users.id')
                  ->select('activity_logs.*')
                  ->orderBy('pos_users.name', $sortDirection);
        } 
        else {
            // Sort standar (prefix tabel untuk menghindari ambiguitas saat join)
            $query->orderBy("activity_logs.$sortField", $sortDirection);
        }

        // 4. LOGIKA FILTERING
        
        // Pencarian Global
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('description', 'like', "%{$request->search}%")
                  ->orWhere('action', 'like', "%{$request->search}%")
                  ->orWhere('reference_type', 'like', "%{$request->search}%");
            });
        }

        // Filter per Toko
        $query->when($request->filled('store_id'), function ($q) use ($request) {
            return $q->where('activity_logs.store_id', $request->store_id);
        });

        // Filter per Eksekutor
        $query->when($request->filled('pos_user_id'), function ($q) use ($request) {
            return $q->where('activity_logs.created_by', $request->pos_user_id);
        });

        // Filter per Tindakan (CREATE, UPDATE, dll)
        $query->when($request->filled('action'), function ($q) use ($request) {
            return $q->where('activity_logs.action', $request->action);
        });

        // Filter Range Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('activity_logs.created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }

        // 5. Eksekusi & Return ke Inertia
        return Inertia::render('ActivityLogs/Index', [
            'logs' => $query->paginate(15) 
                            ->withQueryString()
                            ->through(fn ($log) => [
                                'id'             => $log->id,
                                'store_name'     => $log->store ? $log->store->name : 'GLOBAL / SYSTEM', 
                                'user_name'      => $log->executor ? $log->executor->name : 'SYSTEM',
                                'created_by'     => $log->created_by,
                                'action'         => $log->action,
                                'reference_type' => $log->reference_type,
                                'reference_id'   => $log->reference_id,
                                'description'    => $log->description,
                                'created_at'     => $log->created_at ? $log->created_at->format('d/m/Y H:i') : '-',
                                'payload'        => $log->payload, 
                            ]),
            
            // Dropdown Toko
            'stores' => Store::select('id', 'name')->orderBy('name')->get(),
            
            // DROPDOWN EKSEKUTOR: Hanya menampilkan user yang benar-benar punya riwayat aktivitas
            'posUsers' => PosUser::whereIn('id', function($q) {
                                $q->select('created_by')
                                  ->from('activity_logs')
                                  ->whereNotNull('created_by')
                                  ->distinct();
                            })
                            ->select('id', 'name')
                            ->orderBy('name')
                            ->get(),
            
            'filters' => $request->only([
                'search', 'sort', 'direction', 'store_id', 
                'pos_user_id', 'action', 'reference_type', 
                'start_date', 'end_date'
            ]),
        ]);
    }
}