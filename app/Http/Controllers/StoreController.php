<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreController extends Controller
{
    public function index(Request $request) // Tambahkan Request $request
    {
        $query = Store::join('store_types', 'stores.store_type_id', '=', 'store_types.id')
            ->select('stores.*', 'store_types.name as type_name');

        // LOGIKA PENCARIAN (Hanya Nama, Jenis, dan Alamat - Tanggal diabaikan)
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $searchTerm = "%{$request->search}%";
                $q->where('stores.name', 'like', $searchTerm)
                  ->orWhere('stores.address', 'like', $searchTerm)
                  ->orWhere('store_types.name', 'like', $searchTerm);
            });
        }

        return Inertia::render('Stores/Index', [
            'stores' => $query->latest('stores.created_at')
                ->paginate(10)
                ->withQueryString(), // PENTING: Agar pagination tetap membawa kata kunci pencarian
            
            'store_types' => StoreType::all(['id', 'name']),
            
            // PENTING: Kirim balik kata kunci ke View agar kotak search tidak kosong
            'filters' => $request->only(['search']), 
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'store_type_id' => 'required|exists:store_types,id',
            'address'       => 'nullable|string',
        ]);

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