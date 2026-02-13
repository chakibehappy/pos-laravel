<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger; // Import Helper

class TransactionApprovalController extends Controller
{
    public function index()
    {
        $requests = Transaction::with(['store', 'posUser', 'details', 'requester'])
            ->where('status', 1)
            ->latest()
            ->paginate(10); 

        return Inertia::render('Transactions/Approval', [
            'requests' => $requests
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

    public function handleAction(Request $request, $id)
    {
        // Memuat relasi requester agar nama pengaju tersedia untuk log
        $transaction = Transaction::with('requester')->findOrFail($id);
        $posUserId = $this->getPosUserId();
        
        // Mengambil nama pengaju dari relasi, jika tidak ada tampilkan 'Sistem/Tidak Diketahui'
        $requesterName = $transaction->requester ? $transaction->requester->name : 'Tidak Diketahui';
        $referenceNo = $transaction->reference_no ?? "#$id";

        if ($request->action === 'approve') {
            $transaction->update([
                'status' => 2,
                'admin_approved_by' => $posUserId, // Menggunakan ID pos_users agar konsisten
                'deleted_at' => now(),
            ]);

            // LOG ACTIVITY APPROVE
            ActivityLogger::log(
                'update',
                'transactions',
                $transaction->id,
                "Menyetujui penghapusan transaksi yang diajukan oleh: {$requesterName}",
                $posUserId
            );

        } else {
            $transaction->update([
                'status' => 0,
                'delete_reason' => null,
                'delete_requested_by' => null,
            ]);

            // LOG ACTIVITY REJECT
            ActivityLogger::log(
                'update',
                'transactions',
                $transaction->id,
                "Menolak penghapusan transaksi yang diajukan oleh: {$requesterName}",
                $posUserId
            );
        }

        return redirect()->back()->with('message', 'Aksi berhasil diproses!');
    }
}