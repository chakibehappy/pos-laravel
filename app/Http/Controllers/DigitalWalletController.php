<?php

namespace App\Http\Controllers;

use App\Models\DigitalWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

class DigitalWalletController extends Controller
{
    /**
     * Menampilkan daftar platform wallet dengan Sorting, Pagination & Search.
     */
    public function index(Request $request)
    {
        // Ambil parameter sort, default ke 'id' dan 'desc'
        $sortField = $request->input('sort', 'id');
        $sortDirection = $request->input('direction', 'desc');

        $resource = DigitalWallet::query()
            ->where('status', 0) // Pastikan hanya mengambil yang aktif
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            // Logika Sorting Dinamis
            ->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('DigitalWallets/Index', [
            'resource' => $resource, // Nama variabel tetap 'resource' sesuai sistem DataTable
            'filters'  => $request->only(['search', 'sort', 'direction']),
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
     * Simpan atau Update Master Platform Wallet.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id'   => 'nullable|numeric',
            'name' => 'required|string|max:255|unique:digital_wallet,name,' . $request->id,
        ], [
            'name.unique' => 'Nama platform sudah terdaftar!',
            'name.required' => 'Nama platform wajib diisi.'
        ]);

        try {
            $posUserId = $this->getPosUserId();
            $logType = $request->id ? 'update' : 'create';
            $actionLabel = $request->id ? 'Memperbarui' : 'Membuat';

            $wallet = DigitalWallet::updateOrCreate(
                ['id' => $request->id],
                [
                    'name' => $request->name,
                    'created_by' => $posUserId,
                    'status' => 0,       // Set aktif
                    'deleted_at' => null // Reset jika data dipulihkan
                ]
            );

            // LOG ACTIVITY
            ActivityLogger::log(
                $logType,
                'digital_wallet',
                $wallet->id,
                "$actionLabel platform wallet: {$wallet->name}",
                $posUserId
            );

            return back()->with('message', 'Platform Wallet berhasil disimpan!');
        } catch (\Exception $e) {
            return back()->withErrors(['name' => 'Gagal menyimpan data platform: ' . $e->getMessage()]);
        }
    }

    /**
     * Hapus Master Platform Wallet ( ).
     */
    public function destroy($id)
    {
        try {
            $wallet = DigitalWallet::findOrFail($id);
            
            // Cek relasi jika platform masih digunakan di toko (DigitalWalletStore)
            if ($wallet->storeAssignments()->exists()) {
                return back()->withErrors(['error' => 'Gagal! Platform masih digunakan oleh beberapa toko.']);
            }

            $posUserId = $this->getPosUserId();

            // LOG ACTIVITY
            ActivityLogger::log(
                'delete',
                'digital_wallet',
                $id,
                "Menghapus platform wallet  : {$wallet->name}",
                $posUserId
            );

            // Manual  
            $wallet->update([
                'status' => 2,
                'deleted_at' => now()
            ]);

            return back()->with('message', 'Platform Wallet berhasil diarsipkan!');
        } catch (\Exception $e) {
            return back()->withErrors(['id' => 'Gagal menghapus platform.']);
        }
    }
}