<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalFeeRule;
use App\Models\PosUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Helpers\ActivityLogger; // Import Helper

class WithdrawalFeeRuleController extends Controller
{
    public function index(Request $request)
    {
        // Menangkap parameter sorting dari DataTable.vue
        $sortField = $request->input('sort', 'created_at'); // Default sort field
        $sortDirection = $request->input('direction', 'desc'); // Default direction

        $query = WithdrawalFeeRule::with(['creator']);

        // Logika Pencarian
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('fee', 'like', "%{$request->search}%")
                  ->orWhere('min_limit', 'like', "%{$request->search}%")
                  ->orWhere('max_limit', 'like', "%{$request->search}%");
            });
        }

        return Inertia::render('WithdrawalFeeRules/Index', [
            'resource' => $query->orderBy($sortField, $sortDirection)
                               ->paginate(10)
                               ->withQueryString(),
            'filters'  => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    /**
     * Helper untuk mendapatkan ID Operator (pos_users)
     */
    private function getOperatorId()
    {
        $posUser = PosUser::where('username', Auth::user()->email)->first();
        return $posUser ? $posUser->id : PosUser::first()?->id;
    }

    public function store(Request $request)
    {
        $request->validate([
            'min_limit' => 'required|numeric|min:0',
            'max_limit' => 'required|numeric|gt:min_limit',
            'fee'       => 'required|numeric|min:0',
        ]);

        $finalId = $this->getOperatorId();
        $actionLabel = $request->id ? "Memperbarui" : "Membuat";
        $logType = $request->id ? "update" : "create";

        $rule = WithdrawalFeeRule::updateOrCreate(
            ['id' => $request->id],
            [
                'min_limit'  => $request->min_limit,
                'max_limit'  => $request->max_limit,
                'fee'        => $request->fee,
                'created_by' => $finalId, 
            ]
        );

        // LOG ACTIVITY
        ActivityLogger::log(
            $logType, 
            'withdrawal_fee_rules', 
            $rule->id, 
            "$actionLabel aturan biaya penarikan ID: {$rule->id}", 
            $finalId
        );

        return back()->with('message', 'Aturan biaya penarikan berhasil disimpan!');
    }

    public function destroy($id)
    {
        try {
            $rule = WithdrawalFeeRule::findOrFail($id);
            $userOperatorId = $this->getOperatorId();

            // LOG ACTIVITY DELETE (Sebelum dihapus)
            ActivityLogger::log(
                'delete', 
                'withdrawal_fee_rules', 
                $id, 
                "Menghapus aturan biaya penarikan ID: $id", 
                $userOperatorId
            );

            $rule->delete();

            return back()->with('message', 'Aturan biaya berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus: ' . $e->getMessage()]);
        }
    }
}