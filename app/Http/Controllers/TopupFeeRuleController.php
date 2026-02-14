<?php

namespace App\Http\Controllers;

use App\Models\TopupFeeRule;
use App\Models\TopupTransType;
use App\Models\DigitalWallet; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger; // Import ActivityLogger

class TopupFeeRuleController extends Controller
{
    public function index(Request $request)
    {
        // Menangkap parameter sorting dari DataTable.vue
        $sortField = $request->input('sort', 'created_at'); // Default sort ke tanggal dibuat
        $sortDirection = $request->input('direction', 'desc'); // Default urutan terbaru

        $data = TopupFeeRule::with(['topup_trans_type', 'wallet_target', 'creator'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    // Cari di tipe transaksi
                    $q->whereHas('topup_trans_type', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    // Cari di wallet target
                    ->orWhereHas('wallet_target', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    });
                });
            })
            // Logika Sorting Dinamis
            ->orderBy($sortField, $sortDirection)
            ->paginate(10) 
            ->withQueryString();

        return Inertia::render('TopupFeeRules/Index', [
            'data' => $data,
            'filters' => $request->only(['search', 'sort', 'direction']),
            'transTypes' => TopupTransType::all(),
            'walletTargets' => DigitalWallet::all(),
        ]);
    }

    /**
     * Helper untuk mendapatkan ID pos_users
     */
    private function getPosUserId()
    {
        $adminEmail = Auth::user()->email;
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
        return $posUser ? $posUser->id : null;
    }

    public function store(Request $request)
    {
        $request->validate([
            'rules' => 'required|array|min:1',
            'rules.*.topup_trans_type_id' => 'required|exists:topup_trans_type,id',
            'rules.*.wallet_target_id'    => 'nullable|exists:digital_wallet,id', 
            'rules.*.min_limit'           => 'required|numeric',
            'rules.*.max_limit'           => 'required|numeric',
            'rules.*.fee'                 => 'required|numeric|min:0',
            'rules.*.admin_fee'           => 'required|numeric|min:0',
        ]);

        try {
            $posUserId = $this->getPosUserId();

            if (!$posUserId) {
                return back()->withErrors(['error' => "Akun admin tidak terdeteksi di database pos_users."]);
            }

            DB::transaction(function () use ($request, $posUserId) {
                if ($request->id) {
                    $rule = TopupFeeRule::findOrFail($request->id);
                    $rule->update([
                        'topup_trans_type_id' => $request->rules[0]['topup_trans_type_id'],
                        'wallet_target_id'    => $request->rules[0]['wallet_target_id'] ?? null,
                        'min_limit'           => $request->rules[0]['min_limit'],
                        'max_limit'           => $request->rules[0]['max_limit'],
                        'fee'                 => $request->rules[0]['fee'],
                        'admin_fee'           => $request->rules[0]['admin_fee'],
                    ]);

                    // LOG ACTIVITY UPDATE
                    $typeName = TopupTransType::find($rule->topup_trans_type_id)->name ?? 'Unknown';
                    ActivityLogger::log(
                        'update',
                        'topup_fee_rules',
                        $rule->id,
                        "Memperbarui aturan biaya Top Up: $typeName",
                        $posUserId
                    );
                } else {
                    foreach ($request->rules as $ruleData) {
                        $newRule = TopupFeeRule::create([
                            'topup_trans_type_id' => $ruleData['topup_trans_type_id'],
                            'wallet_target_id'    => $ruleData['wallet_target_id'] ?? null,
                            'min_limit'           => $ruleData['min_limit'],
                            'max_limit'           => $ruleData['max_limit'],
                            'fee'                 => $ruleData['fee'],
                            'admin_fee'           => $ruleData['admin_fee'],
                            'created_by'          => $posUserId,
                        ]);

                        // LOG ACTIVITY CREATE
                        $typeName = TopupTransType::find($newRule->topup_trans_type_id)->name ?? 'Unknown';
                        ActivityLogger::log(
                            'create',
                            'topup_fee_rules',
                            $newRule->id,
                            "Membuat aturan biaya Top Up baru: $typeName",
                            $posUserId
                        );
                    }
                }
            });

            return redirect()->route('topup-fee-rules.index')
                ->with('message', 'Data berhasil diproses!');
                
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $rule = TopupFeeRule::findOrFail($id);
            $posUserId = $this->getPosUserId();
            $typeName = TopupTransType::find($rule->topup_trans_type_id)->name ?? 'Unknown';

            // LOG ACTIVITY DELETE (Dicatat sebelum hapus)
            ActivityLogger::log(
                'delete',
                'topup_fee_rules',
                $id,
                "Menghapus aturan biaya Top Up: $typeName",
                $posUserId ?? auth()->user()->id
            );

            $rule->delete();
            
            return back()->with('message', 'Rule berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus data: ' . $e->getMessage()]);
        }
    }
}