<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreController extends Controller
{
    public function index() {
        return Inertia::render('Stores/Index', [
            'stores' => Store::join('store_types', 'stores.store_type_id', '=', 'store_types.id')
                ->select('stores.*', 'store_types.name as type_name') // ALIAS CREATED HERE
                ->latest('stores.created_at')
                ->paginate(10),
            'store_types' => StoreType::all(['id', 'name']),
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'store_type_id' => 'required|exists:store_types,id',
            'address'       => 'nullable|string',
        ]);

        // We use account_id 1 as a placeholder for the dev phase
        Store::updateOrCreate(
            ['id' => $request->id], 
            array_merge($data, ['account_id' => 1])
        );

        return back()->with('message', 'Store saved successfully');
    }

    public function destroy($id) {
        Store::findOrFail($id)->delete();
        return back()->with('message', 'Store deleted');
    }
}