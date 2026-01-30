<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentMethodController extends Controller
{
    
    public function index()
    {
        return Inertia::render('PaymentMethods/Index', [
            'methods' => PaymentMethod::latest()->get()
        ]);
    }

   
    public function store(Request $request)
    {
        $request->validate([
            'id'   => 'nullable|numeric',

            'name' => 'required|string|max:255|unique:payment_methods,name,' . $request->id,
        ]);

        PaymentMethod::updateOrCreate(
            ['id' => $request->id],
            ['name' => $request->name]
        );

        return redirect()->back()->with('success', 'Data berhasil diproses!');
    }


    public function destroy($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->delete();

        return redirect()->back()->with('success', 'Metode pembayaran berhasil dihapus!');
    }
}