<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountController extends Controller
{
    /**
     * Menampilkan daftar akun
     */
    public function index() {
        return Inertia::render('Accounts/Index', [
            'accounts' => Account::query()
                ->latest()
                ->paginate(10),
        ]);
    }

    /**
     * Menyimpan atau memperbarui akun
     */
    public function store(Request $request) {
        $data = $request->validate([
            'company_name' => 'required|string|max:200',
            'status'       => 'required|boolean',
        ]);

        Account::updateOrCreate(
            ['id' => $request->id], 
            $data
        );

        return back()->with('message', 'Account saved successfully');
    }

    /**
     * Menghapus akun
     */
    public function destroy($id) {
        Account::findOrFail($id)->delete();
        return back()->with('message', 'Account deleted');
    }
}