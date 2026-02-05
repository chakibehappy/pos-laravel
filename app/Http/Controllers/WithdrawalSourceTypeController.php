<?php

namespace App\Http\Controllers; // Pastikan ini benar

use App\Models\WithdrawalSourceType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WithdrawalSourceTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = WithdrawalSourceType::query();

        if ($request->search) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        return Inertia::render('WithdrawalSourceType/Index', [
            'data' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->has('items') && is_array($request->items)) {
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.name' => 'required|string|max:255',
            ]);

            foreach ($request->items as $item) {
                WithdrawalSourceType::create([
                    'name' => strtoupper($item['name']),
                ]);
            }
            return back();
        }

        $request->validate(['name' => 'required|string|max:255']);
        WithdrawalSourceType::create(['name' => strtoupper($request->name)]);
        return back();
    }

    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $sourceType = WithdrawalSourceType::findOrFail($id);
        $sourceType->update(['name' => strtoupper($request->name)]);
        return back();
    }

    public function destroy($id)
    {
        $sourceType = WithdrawalSourceType::findOrFail($id);
        $sourceType->delete();
        return back();
    }
}