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
        // 1. Menangkap parameter sorting (Gunakan filled untuk memastikan tidak null)
        $sortField = $request->filled('sort') ? $request->sort : 'created_at'; 
        $sortDirection = $request->input('direction', 'desc'); 

        // 2. Query dasar dengan Eager Loading
        $query = ActivityLog::with(['executor', 'store']);

        // 3. LOGIKA SORTING (Amankan ambiguitas kolom dengan select)
        if ($sortField === 'store_name') {
            $query->leftJoin('stores', 'activity_logs.store_id', '=', 'stores.id')
                  ->select('activity_logs.*') 
                  ->orderBy('stores.name', $sortDirection);
        } 
        elseif ($sortField === 'user_name') {
            $query->leftJoin('pos_users', 'activity_logs.created_by', '=', 'pos_users.id')
                  ->select('activity_logs.*')
                  ->orderBy('pos_users.name', $sortDirection);
        } 
        else {
            // Mapping field untuk keamanan
            $allowedSorts = ['action', 'reference_type', 'description', 'created_at', 'id'];
            $orderField = in_array($sortField, $allowedSorts) ? $sortField : 'created_at';
            $query->orderBy("activity_logs.$orderField", $sortDirection);
        }

        // 4. LOGIKA FILTERING
        
        // Pencarian Global (Perbaikan: Tambahkan pencarian ke relasi agar lebih komprehensif)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('activity_logs.description', 'like', "%{$search}%")
                  ->orWhere('activity_logs.action', 'like', "%{$search}%")
                  ->orWhere('activity_logs.reference_type', 'like', "%{$search}%")
                  ->orWhereHas('store', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('executor', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
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

        // Filter per Tindakan
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
            
            'stores' => Store::select('id', 'name')->orderBy('name')->get(),
            
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