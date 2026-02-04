<?php

namespace App\Http\Controllers;

use App\Models\TopupFeeRule;
use App\Models\TopupTransType;
use App\Models\DigitalWallet; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TopupFeeRuleController extends Controller
{
    /**
     * Menampilkan daftar rule dengan Paginasi 10 Item.
     */
    public function index(Request $request)
    {
        $data = TopupFeeRule::with(['topup_trans_type', 'wallet_target', 'creator'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('topup_trans_type', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('wallet_target', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10) // DIUBAH MENJADI 10
            ->withQueryString();

        return Inertia::render('TopupFeeRules/Index', [
            'data' => $data,
            'filters' => $request->only(['search']),
            'transTypes' => TopupTransType::all(),
            'walletTargets' => DigitalWallet::all(),
        ]);
    }

    /**
     * Store & Update (Kompatibel dengan sistem Batch Vue)
     */
    public function store(Request $request)
    {
        $request->validate([
            'rules' => 'required|array|min:1',
            'rules.*.topup_trans_type_id' => 'required',
            'rules.*.wallet_target_id'    => 'required',
            'rules.*.min_limit'           => 'required|numeric',
            'rules.*.max_limit'           => 'required|numeric',
            'rules.*.fee'                 => 'required|numeric|min:0',
            'rules.*.admin_fee'           => 'required|numeric|min:0',
        ]);

        try {
            $authEmail = Auth::user()->email;
            $posUser = DB::table('pos_users')->where('username', $authEmail)->first();

            if (!$posUser) {
                return back()->withErrors(['error' => "Akun ($authEmail) tidak terdeteksi di pos_users."]);
            }

            DB::transaction(function () use ($request, $posUser) {
                // Logika UPDATE jika ada ID (Mode Edit Tunggal)
                if ($request->id) {
                    $rule = TopupFeeRule::findOrFail($request->id);
                    $rule->update([
                        'topup_trans_type_id' => $request->rules[0]['topup_trans_type_id'],
                        'wallet_target_id'    => $request->rules[0]['wallet_target_id'],
                        'min_limit'           => $request->rules[0]['min_limit'],
                        'max_limit'           => $request->rules[0]['max_limit'],
                        'fee'                 => $request->rules[0]['fee'],
                        'admin_fee'           => $request->rules[0]['admin_fee'],
                    ]);
                } else {
                    // Logika INSERT MASSAL (Mode Batch Tambah)
                    foreach ($request->rules as $ruleData) {
                        TopupFeeRule::create([
                            'topup_trans_type_id' => $ruleData['topup_trans_type_id'],
                            'wallet_target_id'    => $ruleData['wallet_target_id'],
                            'min_limit'           => $ruleData['min_limit'],
                            'max_limit'           => $ruleData['max_limit'],
                            'fee'                 => $ruleData['fee'],
                            'admin_fee'           => $ruleData['admin_fee'],
                            'created_by'          => $posUser->id,
                        ]);
                    }
                }
            });

            return redirect()->route('topup-fee-rules.index')
                ->with('message', 'Data berhasil diproses!');
                
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal: ' . $e->getMessage()]);
        }
    }

    /**
     * Menghapus rule menggunakan ID (Mengatasi masalah Model Binding)
     */
    public function destroy($id)
    {
        try {
            $rule = TopupFeeRule::findOrFail($id);
            $rule->delete();
            
            return back()->with('message', 'Rule berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus data: ' . $e->getMessage()]);
        }
    }
}