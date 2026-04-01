<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

class AccountController extends Controller
{
    /**
     * Menampilkan daftar akun dengan Search, Sorting, dan Pagination
     */
    public function index(Request $request) 
    {
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        if (empty($sortField)) {
            $sortField = 'created_at';
        }

        $query = Account::query();

        // Logika Pencarian
        if ($request->search) {
            $query->where('company_name', 'like', "%{$request->search}%");
        }

        return Inertia::render('Accounts/Index', [
            'accounts' => $query->orderBy($sortField, $sortDirection)
                                ->paginate(10)
                                ->withQueryString(),
            'filters'  => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    /**
     * Helper untuk mendapatkan ID pos_users (Operator)
     */
    private function getPosUserId()
    {
        $adminEmail = Auth::user()->email;
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
        return $posUser ? $posUser->id : null;
    }

    /**
     * Menyimpan atau memperbarui akun
     */
    public function store(Request $request) 
    {
        $request->validate([
            'id'           => 'nullable|numeric',
            'company_name' => 'required|string|max:200|unique:accounts,company_name,' . $request->id,
            'status'       => 'required|integer', // Menggunakan integer jika status 0/1/2
        ]);

        return DB::transaction(function () use ($request) {
            $posUserId = $this->getPosUserId();
            $logType = $request->id ? 'update' : 'create';
            $actionLabel = $request->id ? 'Memperbarui' : 'Membuat';
            
            $oldData = null;

            // Tangkap data lama jika update
            if ($request->id) {
                $accountBefore = Account::find($request->id);
                $oldData = $accountBefore ? $accountBefore->getRawOriginal() : null;
            }

            $account = Account::updateOrCreate(
                ['id' => $request->id], 
                [
                    'company_name' => $request->company_name,
                    'status'       => $request->status,
                    'deleted_at'   => null // Reset jika data lama dipulihkan
                ]
            );

            // LOG ACTIVITY dengan Old & New data
            ActivityLogger::log(
                $logType,
                'accounts',
                $account->id,
                "$actionLabel akun perusahaan: {$account->company_name}",
                $posUserId,
                ['old' => $oldData, 'new' => $account->getAttributes()],
                null
            );

            return back()->with('message', 'Account saved successfully');
        });
    }

    /**
     * Menghapus akun (Soft Delete Manual ke status 2)
     */
    public function destroy($id) 
    {
        return DB::transaction(function () use ($id) {
            $account = Account::findOrFail($id);
            $posUserId = $this->getPosUserId();
            $oldData = $account->getRawOriginal();

            // Ubah status ke 2 (Archived/Deleted)
            $account->update([
                'status' => 2,
                'deleted_at' => now()
            ]);

            // LOG ACTIVITY DELETE
            ActivityLogger::log(
                'delete',
                'accounts',
                $id,
                "Menghapus akun perusahaan: {$account->company_name}",
                $posUserId,
                ['old' => $oldData, 'new' => $account->getAttributes()],
                null
            );

            return back()->with('message', 'Account deleted successfully');
        });
    }
}