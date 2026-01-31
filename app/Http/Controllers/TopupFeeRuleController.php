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
            'topup_trans_type_id' => 'required|exists:topup_trans_type,id',
            
            // Validasi hanya angka (boleh negatif), tanpa filter perbandingan
            'min_limit'           => 'required|numeric',
            'max_limit'           => 'required|numeric',
            'fee'                 => 'required|numeric',
        ]);

        TopupFeeRule::create($validated);

        return redirect()->back()->with('message', 'Aturan biaya berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $rule = TopupFeeRule::findOrFail($id);
        
        $validated = $request->validate([
            'topup_trans_type_id' => 'required|exists:topup_trans_type,id',
            'min_limit'           => 'required|numeric',
            'max_limit'           => 'required|numeric',
            'fee'                 => 'required|numeric',
        ]);

        $rule->update($validated);

        return redirect()->back()->with('message', 'Aturan biaya berhasil diperbarui!');
    }

    public function destroy($id)
    {
        TopupFeeRule::findOrFail($id)->delete();
        return redirect()->back()->with('message', 'Aturan biaya berhasil dihapus!');
    }
}