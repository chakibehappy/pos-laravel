<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionApprovalController extends Controller
{
    public function index()
    {
        // Penjelasan:
        // 'store' & 'details' = relasi yang sudah ada.
        // 'posUser' = mengambil nama pembuat transaksi (pos_user_id).
        // 'requester' = mengambil nama pengaju hapus (mencocokkan delete_requested_by ke ID di pos_users).
        $requests = Transaction::with(['store', 'posUser', 'details', 'requester'])
            ->where('status', 1)
            ->latest()
            ->paginate(10); 

        return Inertia::render('Transactions/Approval', [
            'requests' => $requests
        ]);
    }

    public function handleAction(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);
        
        if ($request->action === 'approve') {
            $transaction->update([
                'status' => 2,
                'admin_approved_by' => auth()->id(),
                'deleted_at' => now(),
            ]);
        } else {
            $transaction->update([
                'status' => 0,
                'delete_reason' => null,
                // Kita kosongkan juga pengajunya agar data kembali bersih seperti semula
                'delete_requested_by' => null,
            ]);
        }

        return redirect()->back();
    }
}