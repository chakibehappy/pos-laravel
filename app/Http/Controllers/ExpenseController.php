<?php

namespace App\Http\Controllers;

use App\Models\ExpenseTransaction;
use App\Models\Store;
use App\Models\PosUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

class ExpenseController extends Controller
{
    /**
     * Menampilkan daftar transaksi pengeluaran.
     */
    public function index(Request $request)
    {
        $query = ExpenseTransaction::with(['store', 'posUser', 'creator'])
            ->where('status', '!=', 2);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhereHas('posUser', function($qu) use ($request) {
                      $qu->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        $allowedSorts = ['amount', 'transaction_at', 'created_at', 'description'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        return Inertia::render('Expenses/Index', [
            'resource' => $query->paginate(10)->withQueryString(),
            'stores' => Store::select('id', 'name')->get(),
            'posUsers' => PosUser::select('id', 'name')
                ->where('is_active', 1)
                ->get(),
            'filters' => $request->only(['search', 'sort', 'direction', 'store_id']),
            'columns' => [
                ['key' => 'transaction_at', 'label' => 'Tanggal', 'sortable' => true],
                ['key' => 'image', 'label' => 'Dokumentasi', 'sortable' => false],
                ['key' => 'description', 'label' => 'Keterangan', 'sortable' => true],
                ['key' => 'amount', 'label' => 'Nominal', 'sortable' => true],
                ['key' => 'store_name', 'label' => 'Toko', 'sortable' => false],
                ['key' => 'user_name', 'label' => 'PIC/Staf', 'sortable' => false],
                ['key' => 'created_by_name', 'label' => 'Input Oleh', 'sortable' => false],
            ]
        ]);
    }

    private function getPosUserId()
    {
        $adminEmail = Auth::user()->email;
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
        return $posUser ? $posUser->id : null;
    }

    /**
     * Menyimpan atau memperbarui data transaksi.
     */
    public function store(Request $request)
    {
        // Menambahkan pesan error kustom dalam bahasa Indonesia
        $messages = [
            'store_id.required' => 'Lokasi toko wajib dipilih.',
            'store_id.exists'   => 'Toko yang dipilih tidak valid.',
            'pos_user_id.required' => 'Nama PIC/Staf wajib dipilih.',
            'pos_user_id.exists'   => 'Staff yang dipilih tidak valid.',
            'amount.required'      => 'Nominal pengeluaran tidak boleh kosong.',
            'amount.numeric'       => 'Nominal harus berupa angka.',
            'amount.min'           => 'Nominal tidak boleh kurang dari 0.',
            'description.required' => 'Keterangan pengeluaran wajib diisi.',
            'transaction_at.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_at.date'     => 'Format tanggal tidak valid.',
            'image.image'          => 'File harus berupa gambar.',
            'image.mimes'          => 'Format gambar harus jpg, jpeg, atau png.',
            'image.max'            => 'Ukuran gambar maksimal adalah 2MB.',
        ];

        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'pos_user_id' => 'required|exists:pos_users,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'transaction_at' => 'required|date',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], $messages);

        $posUserId = $this->getPosUserId();
        $data = $request->only(['store_id', 'pos_user_id', 'amount', 'description', 'transaction_at']);
        
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('expenses', 'public');
        }

        if (!$request->id) {
            $data['created_by'] = $posUserId;
            $data['status'] = 0;
        }

        $expense = ExpenseTransaction::updateOrCreate(['id' => $request->id], $data);
        
        $expense->load(['store', 'posUser']);

        ActivityLogger::log(
            $request->id ? 'update' : 'create',
            'expense_transactions',
            $expense->id,
            ($request->id ? "Update" : "Input") . " pengeluaran: Toko {$expense->store->name} oleh {$expense->posUser->name} (" . number_format($expense->amount, 0, ',', '.') . ")",
            $posUserId
        );

        return back()->with('message', 'Data berhasil disimpan');
    }

    /**
     * Soft delete manual.
     */
    public function destroy($id)
    {
        $expense = ExpenseTransaction::with(['store', 'posUser'])->findOrFail($id);
        $posUserId = $this->getPosUserId();

        $expense->update([
            'status' => 2,
            'deleted_at' => now()
        ]);

        ActivityLogger::log(
            'delete',
            'expense_transactions',
            $id,
            "Hapus pengeluaran: Toko {$expense->store->name} oleh {$expense->posUser->name}",
            $posUserId
        );

        return back()->with('message', 'Data berhasil dihapus');
    }
}