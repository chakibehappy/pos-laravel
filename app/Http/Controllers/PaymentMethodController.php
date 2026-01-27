<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentMethodController extends Controller
{
    /**
     * Menampilkan daftar metode pembayaran
     */
    public function index()
    {
        $methods = PaymentMethod::latest()->get();
        
        return Inertia::render('PaymentMethods/Index', [
            'methods' => $methods
        ]);
    }

    /**
     * Menyimpan metode pembayaran baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:payment_methods,name',
        ]);

        PaymentMethod::create([
            'name' => $request->name
        ]);

        return redirect()->back()->with('success', 'Metode pembayaran berhasil ditambahkan!');
    }

    /**
     * Memperbarui metode pembayaran
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            // Validasi unique kecuali untuk ID yang sedang diedit
            'name' => 'required|string|max:255|unique:payment_methods,name,' . $id,
        ]);

        $method = PaymentMethod::findOrFail($id);
        $method->update([
            'name' => $request->name
        ]);

        return redirect()->back()->with('success', 'Metode pembayaran berhasil diperbarui!');
    }

    /**
     * Menghapus metode pembayaran
     */
    public function destroy($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->delete();

        return redirect()->back()->with('success', 'Metode pembayaran berhasil dihapus!');
    }
}