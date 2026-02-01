<?php

namespace App\Http\Controllers;

use App\Models\TopupFeeRule;
use App\Models\TopupTransType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TopupFeeRuleController extends Controller
{
    public function index(Request $request)
    {
        // 1. Query dengan Filter & Search (Berdasarkan Nama Tipe Transaksi)
        $rules = TopupFeeRule::with('transType')
            ->when($request->search, function ($query, $search) {
                $query->whereHas('transType', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // 2. Transformasi Data agar konsisten dengan tampilan Vue
        $rules->getCollection()->transform(function ($rule) {
            return [
                'id' => $rule->id,
                'topup_trans_type_id' => $rule->topup_trans_type_id,
                'trans_type_name' => $rule->transType->name ?? '-',
                'min_limit' => $rule->min_limit,
                'max_limit' => $rule->max_limit,
                'fee' => $rule->fee,
                'admin_fee' => $rule->admin_fee,
            ];
        });

        return Inertia::render('TopupFeeRules/Index', [
            'rules' => $rules,
            'transTypes' => TopupTransType::all(['id', 'name']),
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        // Mendukung UpdateOrCreate jika id dikirim (seperti sistem Product Anda)
        $validated = $request->validate([
            'id'                  => 'nullable|numeric',
            'topup_trans_type_id' => 'required|exists:topup_trans_type,id',
            'min_limit'           => 'nullable|numeric',
            'max_limit'           => 'nullable|numeric',
            'fee'                 => 'nullable|numeric',
            'admin_fee'           => 'nullable|numeric',
        ]);

        // Proteksi nilai null menjadi 0
        $data = [
            'topup_trans_type_id' => $validated['topup_trans_type_id'],
            'min_limit'           => $validated['min_limit'] ?? 0,
            'max_limit'           => $validated['max_limit'] ?? 0,
            'fee'                 => $validated['fee'] ?? 0,
            'admin_fee'           => $validated['admin_fee'] ?? 0,
        ];

        TopupFeeRule::updateOrCreate(['id' => $request->id], $data);

        return redirect()->back()->with('success', 'Aturan biaya berhasil disimpan!');
    }

    // Fungsi update tetap ada untuk kompatibilitas route, 
    // tapi isinya memanggil logika yang sama dengan store
    public function update(Request $request, $id)
    {
        return $this->store($request->merge(['id' => $id]));
    }

    public function destroy($id)
    {
        $rule = TopupFeeRule::findOrFail($id);
        $rule->delete();

        return redirect()->back()->with('success', 'Aturan biaya berhasil dihapus!');
    }
}