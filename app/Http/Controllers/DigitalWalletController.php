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
        $sortField = $request->input('sort', 'id');
        $sortDirection = $request->input('direction', 'desc');

        if (empty($sortField)) {
            $sortField = 'id';
        }
        
        $resource = DigitalWallet::query()
            ->where('status', 0)
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('DigitalWallets/Index', [
            'resource' => $resource,
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
            $oldData = null;

            // Jika ada ID, berarti Update. Ambil data LAMA sebelum ditimpa.
            if ($request->id) {
                $existingWallet = DigitalWallet::find($request->id);
                if ($existingWallet) {
                    $oldData = $existingWallet->getRawOriginal();
                }
            }

            $wallet = DigitalWallet::updateOrCreate(
                ['id' => $request->id],
                [
                    'name' => $request->name,
                    'created_by' => $posUserId,
                    'status' => 0,
                    'deleted_at' => null
                ]
            );

            // Tentukan label dan tipe log
            $logType = $request->id ? 'update' : 'create';
            $actionLabel = $request->id ? 'Memperbarui' : 'Membuat';

            // LOG ACTIVITY dengan Payload
            ActivityLogger::log(
                $logType,
                'digital_wallet',
                $wallet->id,
                "$actionLabel platform wallet: {$wallet->name}",
                $posUserId,
                ['old' => $oldData, 'new' => $wallet->getAttributes()],
                null
            );

            return back()->with('message', 'Platform Wallet berhasil disimpan!');
        } catch (\Exception $e) {
            return back()->withErrors(['name' => 'Gagal menyimpan data platform: ' . $e->getMessage()]);
        }
    }

    /**
     * Hapus Master Platform Wallet.
     */
    public function destroy($id)
    {
        try {
            $wallet = DigitalWallet::findOrFail($id);
            
            if ($wallet->storeAssignments()->exists()) {
                return back()->withErrors(['error' => 'Gagal! Platform masih digunakan oleh beberapa toko.']);
            }

            $posUserId = $this->getPosUserId();
            $oldData = $wallet->getRawOriginal(); // Tangkap data sebelum diarsipkan

            // LOG ACTIVITY
            ActivityLogger::log(
                'delete',
                'digital_wallet',
                $id,
                "Menghapus platform wallet: {$wallet->name}",
                $posUserId,
                ['old' => $oldData, 'new' => array_merge($oldData, ['status' => 2, 'deleted_at' => now()])],
                null
            );

            // Proses Arsipkan
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