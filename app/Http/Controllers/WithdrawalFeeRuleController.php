<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalFeeRule;
use App\Models\PosUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WithdrawalFeeRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = WithdrawalFeeRule::with(['creator']);

        if ($request->search) {
            $query->where('fee', 'like', "%{$request->search}%")
                  ->orWhere('min_limit', 'like', "%{$request->search}%")
                  ->orWhere('max_limit', 'like', "%{$request->search}%");
        }

        return Inertia::render('WithdrawalFeeRules/Index', [
            'resource' => $query->latest()->paginate(10)->withQueryString(),
            'filters'  => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'min_limit' => 'required|numeric|min:0',
            'max_limit' => 'required|numeric|gt:min_limit',
            'fee'       => 'required|numeric|min:0',
        ]);

        // LOGIKA PENYELARASAN:
        // Mencari di pos_users yang username-nya sama dengan email User yang sedang login
        $posUser = PosUser::where('username', Auth::user()->email)->first();

        // Jika tidak ketemu, ambil ID pertama dari pos_users agar tidak kena error SQL 1452
        $finalId = $posUser ? $posUser->id : PosUser::first()?->id;

        WithdrawalFeeRule::updateOrCreate(
            ['id' => $request->id],
            [
                'min_limit'  => $request->min_limit,
                'max_limit'  => $request->max_limit,
                'fee'        => $request->fee,
                'created_by' => $finalId, 
            ]
        );

        return back()->with('message', 'Aturan biaya penarikan berhasil disimpan!');
    }

    public function destroy($id)
    {
        $rule = WithdrawalFeeRule::findOrFail($id);
        $rule->delete();

        return back()->with('message', 'Aturan biaya berhasil dihapus.');
    }
}