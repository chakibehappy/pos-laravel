<?php

namespace App\Http\Controllers;

use App\Models\TopupFeeRule;
use App\Models\TopupTransType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TopupFeeRuleController extends Controller
{
    public function index()
    {
        return Inertia::render('TopupFeeRules/Index', [
            'rules' => TopupFeeRule::with('transType')->latest()->get(),
            'transTypes' => TopupTransType::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Combo Box: Wajib diisi dan harus ada di tabel topup_trans_type
            'topup_trans_type_id' => 'required|exists:topup_trans_type,id',
            
            // Field Angka: Boleh kosong (nullable) dan harus angka (numeric)
            // Dengan nullable, user bisa mengosongkan input atau mengisi 0
            'min_limit'           => 'nullable|numeric',
            'max_limit'           => 'nullable|numeric',
            'fee'                 => 'nullable|numeric',
            'admin_fee'           => 'nullable|numeric', 
        ]);

        // Mengonversi nilai null (kosong) menjadi 0 agar data di DB tetap konsisten angka
        $validated['min_limit'] = $validated['min_limit'] ?? 0;
        $validated['max_limit'] = $validated['max_limit'] ?? 0;
        $validated['fee']       = $validated['fee'] ?? 0;
        $validated['admin_fee'] = $validated['admin_fee'] ?? 0;

        TopupFeeRule::create($validated);

        return redirect()->back()->with('success', 'Aturan biaya berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $rule = TopupFeeRule::findOrFail($id);
        
        $validated = $request->validate([
            'topup_trans_type_id' => 'required|exists:topup_trans_type,id',
            'min_limit'           => 'nullable|numeric',
            'max_limit'           => 'nullable|numeric',
            'fee'                 => 'nullable|numeric',
            'admin_fee'           => 'nullable|numeric',
        ]);

        // Tetap pastikan nilai null menjadi 0 saat update
        $validated['min_limit'] = $validated['min_limit'] ?? 0;
        $validated['max_limit'] = $validated['max_limit'] ?? 0;
        $validated['fee']       = $validated['fee'] ?? 0;
        $validated['admin_fee'] = $validated['admin_fee'] ?? 0;

        $rule->update($validated);

        return redirect()->back()->with('success', 'Aturan biaya berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $rule = TopupFeeRule::findOrFail($id);
        $rule->delete();

        return redirect()->back()->with('success', 'Aturan biaya berhasil dihapus!');
    }
}