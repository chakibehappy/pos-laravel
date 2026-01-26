<?php

namespace App\Http\Controllers;

use App\Models\PosUser;
use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PosUserController extends Controller
{
    public function index() {
        return Inertia::render('PosUser/Index', [
            // Join stores to get the store name as an alias
            'staff' => PosUser::join('stores', 'pos_users.store_id', '=', 'stores.id')
                ->select('pos_users.*', 'stores.name as store_name')
                ->latest('pos_users.created_at')
                ->paginate(10),
            // Need this for the "Assign to Store" dropdown
            'stores' => Store::all(['id', 'name']),
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'name'     => 'required|string|max:150',
            'pin'      => 'required|numeric|digits_between:4,6',
            'role'     => 'required|string',
        ]);
        // Hash the PIN before saving
        $data['pin'] = Hash::make($data['pin']);

        PosUser::updateOrCreate(['id' => $request->id], $data);

        return back()->with('message', 'Staff saved successfully');
    }

    public function destroy($id) {
        PosUser::findOrFail($id)->delete();
        return back()->with('message', 'Staff removed');
    }
}