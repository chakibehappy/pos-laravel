<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalFeeRule;
use App\Models\PosUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

class WithdrawalFeeRuleController extends Controller
{
    public function index(Request $request)
    {
        // Menangkap parameter sorting dari DataTable.vue
        $sortField = $request->input('sort', 'created_at'); 
        $sortDirection = $request->input('direction', 'desc'); 

        if (empty($sortField)) {
            $sortField = 'created_at';
        }
        
        // Global Scope di model otomatis memfilter status != 2
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

        try {
            return DB::transaction(function () use ($request) {
                $finalId = $this->getOperatorId();
                $actionLabel = $request->id ? "Memperbarui" : "Membuat";
                $logType = $request->id ? "update" : "create";
                
                $oldData = null;

                // Ambil data lama jika ini adalah update
                if ($request->id) {
                    $ruleBefore = WithdrawalFeeRule::find($request->id);
                    $oldData = $ruleBefore ? $ruleBefore->getRawOriginal() : null;
                }

                $rule = WithdrawalFeeRule::updateOrCreate(
                    ['id' => $request->id],
                    [
                        'min_limit'  => $request->min_limit,
                        'max_limit'  => $request->max_limit,
                        'fee'        => $request->fee,
                        'created_by' => $finalId, 
                        'status'     => 0, // Reset ke aktif jika diupdate
                        'deleted_at' => null
                    ]
                );

                // LOG ACTIVITY dengan data OLD dan NEW
                ActivityLogger::log(
                    $logType, 
                    'withdrawal_fee_rules', 
                    $rule->id, 
                    "$actionLabel aturan biaya penarikan: Limit " . number_format($rule->min_limit) . " - " . number_format($rule->max_limit), 
                    $finalId,
                    ['old' => $oldData, 'new' => $rule->getAttributes()]
                );

                return back()->with('message', 'Aturan biaya penarikan berhasil disimpan!');
            });
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $rule = WithdrawalFeeRule::findOrFail($id);
                $oldData = $rule->getRawOriginal(); // Simpan snapshot sebelum diupdate statusnya
                $userOperatorId = $this->getOperatorId();

                // Soft Delete Manual
                $rule->update([
                    'status' => 2,
                    'deleted_at' => now()
                ]);

                // LOG ACTIVITY DELETE dengan data OLD dan NEW
                ActivityLogger::log(
                    'delete', 
                    'withdrawal_fee_rules', 
                    $id, 
                    "Menghapus aturan biaya penarikan: Limit " . number_format($rule->min_limit) . " - " . number_format($rule->max_limit), 
                    $userOperatorId,
                    ['old' => $oldData, 'new' => $rule->getAttributes()]
                );

                return back()->with('message', 'Aturan biaya berhasil diarsipkan.');
            });
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus: ' . $e->getMessage()]);
        }
    }
}