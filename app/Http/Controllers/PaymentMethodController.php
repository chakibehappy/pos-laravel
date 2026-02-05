<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        // Mendukung fitur search di DataTable.vue
        $query = PaymentMethod::query()->latest();

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('PaymentMethods/Index', [
            // DataTable membutuhkan format paginasi, bukan ->get()
            'methods' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        // 1. Logic untuk MODE EDIT
        if ($request->id) {
            $request->validate([
                'name' => 'required|string|max:255|unique:payment_methods,name,' . $request->id,
            ]);

            PaymentMethod::where('id', $request->id)->update([
                'name' => $request->name
            ]);
        } 
        // 2. Logic untuk MODE CREATE (Batch Antrian)
        else {
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.name' => 'required|string|max:255|unique:payment_methods,name',
            ], [
                'items.*.name.unique' => 'Salah satu metode sudah terdaftar.'
            ]);

            foreach ($request->items as $item) {
                PaymentMethod::create([
                    'name' => $item['name']
                ]);
            }
        }

        return redirect()->back()->with('success', 'Data berhasil diproses!');
    }

    public function destroy($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->delete();

        return redirect()->back()->with('success', 'Metode pembayaran berhasil dihapus!');
    }
}