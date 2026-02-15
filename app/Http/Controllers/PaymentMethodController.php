<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

class PaymentMethodController extends Controller
{
    /**
     * Menampilkan data dengan Search dan Dynamic Sorting.
     */
    public function index(Request $request)
    {
        $sortField = $request->input('sort', 'created_at'); 
        $sortDirection = $request->input('direction', 'desc'); 

        $methods = PaymentMethod::query()
            ->with(['creator'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('PaymentMethods/Index', [
            'methods' => $methods,
            'filters' => $request->only(['search', 'sort', 'direction'])
        ]);
    }

    /**
     * Logika Private: Mapping User Admin ke ID PosUser.
     */
    private function getPosUserId()
    {
        $adminEmail = Auth::user()->email;
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
        return $posUser ? $posUser->id : null;
    }

    /**
     * Simpan atau Update data.
     */
    public function store(Request $request)
    {
        $posUserId = $this->getPosUserId();

        if ($request->id) {
            $request->validate([
                'name' => 'required|string|max:255|unique:payment_methods,name,' . $request->id,
            ]);

            $method = PaymentMethod::findOrFail($request->id);
            
            $method->update([
                'name'       => $request->name,
                'created_by' => $posUserId,
                'status'     => 0,
                'deleted_at' => null
            ]);

            ActivityLogger::log(
                'update',
                'payment_methods',
                $method->id,
                "Memperbarui metode pembayaran: {$request->name}",
                $posUserId
            );
        } 
        else {
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.name' => 'required|string|max:255|unique:payment_methods,name',
            ], [
                'items.*.name.unique' => 'Salah satu metode sudah terdaftar.'
            ]);

            DB::transaction(function () use ($request, $posUserId) {
                foreach ($request->items as $item) {
                    $newMethod = PaymentMethod::create([
                        'name'       => $item['name'],
                        'created_by' => $posUserId,
                        'status'     => 0
                    ]);

                    ActivityLogger::log(
                        'create',
                        'payment_methods',
                        $newMethod->id,
                        "Menambah metode pembayaran baru: {$newMethod->name}",
                        $posUserId
                    );
                }
            });
        }

        return redirect()->back()->with('message', 'Data berhasil diproses!');
    }

    /**
     * Hapus Data (Manual   tanpa proteksi relasi).
     */
    public function destroy($id)
    {
        try {
            $method = PaymentMethod::findOrFail($id);
            $posUserId = $this->getPosUserId();

            // Proteksi Relasi Transaksi Dihapus sesuai permintaan
            // Data tetap diupdate ke status 2  
            $method->update([
                'status' => 2,
                'deleted_at' => now()
            ]);

            ActivityLogger::log(
                'delete',
                'payment_methods',
                $id,
                "Mengarsipkan metode pembayaran: {$method->name}",
                $posUserId
            );

            return redirect()->back()->with('message', 'Metode pembayaran berhasil diarsipkan!');
        } catch (\Exception $e) {
            // Mengembalikan pesan error teknis jika terjadi kegagalan sistem
            return back()->withErrors(['error' => 'Gagal mengarsipkan data: ' . $e->getMessage()]);
        }
    }
}