<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

class ServiceController extends Controller
{
    /**
     * Helper untuk mendapatkan ID pos_users berdasarkan email login.
     */
    private function getPosUserId()
    {
        $adminEmail = Auth::user()->email;
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
        return $posUser ? $posUser->id : null;
    }

    /**
     * Menampilkan daftar layanan aktif (status 0).
     */
    public function index(Request $request)
    {
        // 1. Inisialisasi Query dengan Eager Load 'user'
        $query = Service::with('user'); 

        // 2. Filter Status (Hanya tampilkan status 0 / Aktif)
        $query->where('status', 0);

        // 3. Logic Pencarian
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->search}%")
                  ->orWhere('description', 'LIKE', "%{$request->search}%");
            });
        }

        // 4. Konfigurasi Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $sortMapping = [
            'name'       => 'name',
            'price'      => 'price',
            'created_at' => 'created_at'
        ];

        $orderColumn = $sortMapping[$sortField] ?? 'created_at';
        $query->orderBy($orderColumn, $sortDirection);

        return Inertia::render('Services/Index', [
            'services' => $query->paginate(10)->withQueryString(),
            'filters'  => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    /**
     * Menyimpan data layanan baru.
     */
    public function store(Request $request)
    {
        return $this->processService($request);
    }

    /**
     * Memperbarui data layanan yang sudah ada.
     */
    public function update(Request $request, $id)
    {
        return $this->processService($request, $id);
    }

    /**
     * Menghapus data secara logika: 
     * Mengubah status menjadi 2 dan menjalankan Soft Delete.
     */
    public function destroy($id)
    {
        try {
            $posUserId = $this->getPosUserId();
            $service = Service::findOrFail($id);
            $serviceName = $service->name;
            
            DB::transaction(function () use ($service, $posUserId) {
                // Update status menjadi 2 (Arsip/Terhapus)
                // 'deleted_by' diganti menjadi 'admin_approved_by'
                $service->update([
                    'status' => 2,
                    'admin_approved_by' => $posUserId 
                ]);

                // Jalankan Soft Delete
                $service->delete(); 
            });

            ActivityLogger::log(
                "delete",
                'services',
                $id,
                "Menghapus layanan (Status diubah ke 2): " . $serviceName,
                $posUserId
            );

            return redirect()->back()->with('message', 'Layanan berhasil dihapus dari daftar.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => 'Gagal menghapus data: ' . $e->getMessage()]);
        }
    }

    /**
     * Logika utama untuk Simpan/Update data.
     */
    private function processService(Request $request, $id = null)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        try {
            $posUserId = $this->getPosUserId();

            if (!$posUserId) {
                return back()->withErrors(['message' => "Identitas admin tidak ditemukan."]);
            }

            $service = DB::transaction(function () use ($request, $id, $posUserId) {
                $data = [
                    'name'        => $request->name,
                    'description' => $request->description,
                    'price'       => $request->price,
                    'status'      => 0, // Reset ke aktif jika diedit atau dibuat baru
                ];

                if (!$id) {
                    $data['created_by'] = $posUserId;
                }

                // Perhatikan: updateOrCreate akan menggunakan fillable yang sudah kita set di model Service
                return Service::updateOrCreate(['id' => $id], $data);
            });

            ActivityLogger::log(
                $id ? "update" : "create",
                'services',
                $service->id,
                ($id ? "Memperbarui" : "Menambah") . " layanan: " . $service->name,
                $posUserId
            );

            return redirect()->route('services.index')->with('message', 'Data Layanan Berhasil Disimpan!');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
        }
    }
}