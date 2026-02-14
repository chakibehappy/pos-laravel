<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger; // Import Helper

class PaymentMethodController extends Controller
{
    /**
     * Menampilkan data dengan Search dan Dynamic Sorting.
     */
    public function index(Request $request)
    {
        // Menangkap parameter sorting dari DataTable.vue
        $sortField = $request->input('sort', 'created_at'); // Default sort ke tanggal
        $sortDirection = $request->input('direction', 'desc'); // Default urutan terbaru

        $query = PaymentMethod::query()
            ->with(['creator']) // Eager load relasi creator (pos_users)
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            // Logika Sorting Dinamis
            ->orderBy($sortField, $sortDirection);

        return Inertia::render('PaymentMethods/Index', [
            'methods' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'sort', 'direction'])
        ]);
    }

    /**
     * Logika Private: Mapping User Admin ke ID PosUser.
     */
    private function getPosUserId()
    {
        // 1. Ambil email admin yang sedang login dari tabel 'users'
        $adminEmail = Auth::user()->email;

        // 2. Cari di tabel 'pos_users' yang username-nya sama dengan email admin
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();

        // 3. Kembalikan ID dari pos_users jika ketemu
        return $posUser ? $posUser->id : null;
    }

    /**
     * Simpan atau Update data (Mendukung Batch Store).
     */
    public function store(Request $request)
    {
        // Dapatkan ID pos_users berdasarkan email login
        $posUserId = $this->getPosUserId();

        // 1. Logic untuk MODE EDIT
        if ($request->id) {
            $request->validate([
                'name' => 'required|string|max:255|unique:payment_methods,name,' . $request->id,
            ]);

            $method = PaymentMethod::findOrFail($request->id);
            
            $method->update([
                'name'       => $request->name,
                'created_by' => $posUserId // Update pengedit terakhir
            ]);

            // LOG ACTIVITY UPDATE
            ActivityLogger::log(
                'update',
                'payment_methods',
                $method->id,
                "Memperbarui metode pembayaran: {$request->name}",
                $posUserId
            );
        } 
        // 2. Logic untuk MODE CREATE (Batch Antrian)
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
                        'created_by' => $posUserId 
                    ]);

                    // LOG ACTIVITY CREATE (Batch)
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
     * Hapus Data.
     */
    public function destroy($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $posUserId = $this->getPosUserId();

        // LOG ACTIVITY DELETE (Sebelum hapus)
        ActivityLogger::log(
            'delete',
            'payment_methods',
            $id,
            "Menghapus metode pembayaran: {$method->name}",
            $posUserId
        );

        $method->delete();

        return redirect()->back()->with('message', 'Metode pembayaran berhasil dihapus!');
    }
}