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
        return Inertia::render('PaymentMethods/Index', [
            'methods' => PaymentMethod::latest()->get()
        ]);
    }

    /**
     * Menyimpan atau Memperbarui metode pembayaran (Satu Pintu)
     */
    public function store(Request $request)
    {
        $request->validate([
            'id'   => 'nullable|numeric',
            // Validasi unik, namun abaikan pengecekan jika ID cocok (saat edit)
            'name' => 'required|string|max:255|unique:payment_methods,name,' . $request->id,
        ]);

        // Jika request memiliki ID, Laravel akan melakukan UPDATE.
        // Jika ID adalah null/tidak ada, Laravel akan melakukan CREATE.
        PaymentMethod::updateOrCreate(
            ['id' => $request->id],
            ['name' => $request->name]
        );

        return redirect()->back()->with('success', 'Data berhasil diproses!');
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