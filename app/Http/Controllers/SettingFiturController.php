<?php

namespace App\Http\Controllers;

use App\Models\PosUser;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingFiturController extends Controller
{
    public function index(Request $request)
    {
        // Ambil daftar user yang memiliki role 'admin'
        $admins = PosUser::where('role', 'admin')
            ->select('id', 'name')
            ->get();

        // Default admin terpilih diset langsung ke ID 1
        $selectedAdminId = $request->query('user_id', 1);

        // Ambil data user terpilih langsung dari tabel pos_users
        $initialModules = [];
        if ($selectedAdminId) {
            $user = PosUser::find($selectedAdminId);
            if ($user) {
                // Pastikan dikembalikan sebagai integer (1 atau 0)
                $initialModules = [
                    'edit_kas'  => $user->edit_cashstore ? 1 : 0,
                    'reset_kas' => $user->reset_cashstore ? 1 : 0,
                    'edit_saldo' => $user->edit_digitalwalletstore ? 1 : 0,
                    'edit_produk' => $user->edit_products ? 1 : 0,
                    'delete_produk' => $user->delete_products ? 1 : 0,
                    'add_transaksi' => $user->add_transactions ? 1 : 0,
                    'edit_transaksi' => $user->edit_transactions ? 1 : 0,
                    'delete_transaksi' => $user->delete_transactions ? 1 : 0,
                    'detail_transaksi' => $user->detail_transactions ? 1 : 0,
                    'add_pengeluaran' => $user->add_expense ? 1 : 0,
                    'edit_pengeluaran' => $user->edit_expense ? 1 : 0,
                    'delete_pengeluaran' => $user->delete_expense ? 1 : 0,
                    'add_fee_topup' => $user->add_topup_rules ? 1 : 0,
                    'edit_fee_topup' => $user->edit_topup_rules ? 1 : 0,
                    'delete_fee_topup' => $user->delete_topup_rules ? 1 : 0,
                    'add_fee_withdraw' => $user->add_withdraw_rules ? 1 : 0,
                    'edit_fee_withdraw' => $user->edit_withdraw_rules ? 1 : 0,
                    'delete_fee_withdraw' => $user->delete_withdraw_rules ? 1 : 0,
                ];
            }
        }

        return Inertia::render('SettingFiturs/Index', [
            'admins' => $admins,
            'selectedAdminId' => (int) $selectedAdminId,
            'initialModules' => $initialModules,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:pos_users,id',
            'modules' => 'required|array',
        ]);

        $user = PosUser::findOrFail($request->user_id);

        // Konversi input ke integer 1 atau 0 secara tegas
        $modules = $request->input('modules', []);

        $user->update([
            'edit_cashstore'  => !empty($modules['edit_kas']) ? 1 : 0,
            'reset_cashstore' => !empty($modules['reset_kas']) ? 1 : 0,
            'edit_digitalwalletstore' => !empty($modules['edit_saldo']) ? 1 : 0,
            'edit_products' => !empty($modules['edit_produk']) ? 1 : 0,
            'delete_products' => !empty($modules['delete_produk']) ? 1 : 0,
            'add_transactions' => !empty($modules['add_transaksi']) ? 1 : 0,
            'edit_transactions' => !empty($modules['edit_transaksi']) ? 1 : 0,
            'delete_transactions' => !empty($modules['delete_transaksi']) ? 1 : 0,
            'detail_transactions' => !empty($modules['detail_transaksi']) ? 1 : 0,
            'add_expense' => !empty($modules['add_pengeluaran']) ? 1 : 0,
            'edit_expense' => !empty($modules['edit_pengeluaran']) ? 1 : 0,
            'delete_expense' => !empty($modules['delete_pengeluaran']) ? 1 : 0,
            'add_topup_rules' => !empty($modules['add_fee_topup']) ? 1 : 0,
            'edit_topup_rules' => !empty($modules['edit_fee_topup']) ? 1 : 0,
            'delete_topup_rules' => !empty($modules['delete_fee_topup']) ? 1 : 0,
            'add_withdraw_rules' => !empty($modules['add_fee_withdraw']) ? 1 : 0,
            'edit_withdraw_rules' => !empty($modules['edit_fee_withdraw']) ? 1 : 0,
            'delete_withdraw_rules' => !empty($modules['delete_fee_withdraw']) ? 1 : 0,
        ]);

        return redirect()->back()->with('message', 'Pengaturan hak akses berhasil disimpan');
    }
}