<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    /**
     * Menampilkan daftar log aktivitas (Read-Only) dengan Sorting & Search Dinamis
     */
    public function index(Request $request)
    {
        // Menangkap parameter sorting dari DataTable.vue
        $sortField = $request->input('sort', 'created_at'); // Default field
        $sortDirection = $request->input('direction', 'desc'); // Default urutan

        // Mapping jika field sorting di UI berbeda dengan nama kolom di DB
        if ($sortField === 'user_name') {
            $sortField = 'created_by';
        }

        $query = ActivityLog::with(['user']);

        // Fitur Pencarian Global
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('description', 'like', "%{$request->search}%")
                  ->orWhere('action', 'like', "%{$request->search}%")
                  ->orWhere('reference_type', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function($qu) use ($request) {
                      $qu->where('name', 'like', "%{$request->search}%");
                  });
            });
        }

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $query->orderBy($sortField, $sortDirection)
                            ->paginate(15) // Menambah pagination agar lebih proporsional untuk log
                            ->withQueryString()
                            ->through(fn ($log) => [
                                'id' => $log->id,
                                'user_name' => $log->user ? $log->user->name : 'SYSTEM',
                                'action' => $log->action,
                                'reference_type' => $log->reference_type,
                                'reference_id' => $log->reference_id,
                                'description' => $log->description,
                                'created_at' => $log->created_at ? $log->created_at->format('d/m/Y H:i') : '-',
                            ]),
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }
}