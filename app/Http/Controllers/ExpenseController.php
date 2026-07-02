<?php

namespace App\Http\Controllers;

use App\Models\ExpenseTransaction;
use App\Models\ExpenseType;
use App\Models\Store;
use App\Models\PosUser;
use App\Models\CashStore; // Import Model CashStore
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
        $query = ExpenseTransaction::with(['store', 'posUser', 'creator', 'expenseType'])
            ->where('status', '!=', 2);

        // Filter Pencarian
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhereHas('posUser', function($qu) use ($request) {
                      $qu->where('name', 'like', '%' . $request->search . '%');
                  })
                  ->orWhereHas('expenseType', function($qe) use ($request) {
                      $qe->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // Filter Toko
        if ($request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        // Sorting
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        $allowedSorts = ['amount', 'transaction_at', 'created_at', 'description'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        $posUsers = PosUser::select('id', 'name', 'role', 'username')
            ->where('is_active', 1)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->role ? "{$user->name} ({$user->role})" : $user->name,
                    'role' => $user->role,
                    'username' => $user->username
                ];
            });

        return Inertia::render('Expenses/Index', [
            'resource' => $query->paginate(10)->through(function ($item) {
                return [
                    'id'               => $item->id,
                    'transaction_at'   => $item->transaction_at,
                    'image'            => $item->image,
                    'description'      => $item->description,
                    'amount'           => $item->amount,
                    'expense_type_id'  => $item->expense_type_id,
                    'expense_type_name'=> $item->expenseType?->name,
                    'nama_cabang'      => $item->store?->name,
                    'pos_user'         => $item->posUser,
                    'store_id'         => $item->store_id,
                    'pos_user_id'      => $item->pos_user_id,
                ];
            })->withQueryString(),
            'stores'       => Store::select('id', 'name')->get(),
            'expenseTypes' => ExpenseType::select('id', 'name')->get(),
            'posUsers'     => $posUsers,
            'filters'      => $request->only(['search', 'sort', 'direction', 'store_id']),
            'columns'      => [
                ['key' => 'transaction_at', 'label' => 'Tanggal', 'sortable' => true],
                ['key' => 'image', 'label' => 'Dokumentasi', 'sortable' => false],
                ['key' => 'expense_type_name', 'label' => 'Tipe', 'sortable' => false],
                ['key' => 'description', 'label' => 'Keterangan', 'sortable' => true],
                ['key' => 'amount', 'label' => 'Nominal', 'sortable' => true],
                ['key' => 'store_name', 'label' => 'Toko', 'sortable' => false],
                ['key' => 'user_name', 'label' => 'PIC/Staf', 'sortable' => false],
            ]
        ]);
    }

    /**
     * Helper untuk mendapatkan ID pos_user berdasarkan user yang login.
     */
    private function getPosUserId()
    {
        $adminEmail = Auth::user()->email;
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
        return $posUser ? $posUser->id : null;
    }

    /**
     * Menyimpan atau memperbarui data transaksi dan memotong kas toko.
     */
    public function store(Request $request)
    {
        $messages = [
            'store_id.exists'         => 'Toko yang dipilih tidak valid.',
            'expense_type_id.required'=> 'Tipe pengeluaran wajib dipilih.',
            'expense_type_id.exists'   => 'Tipe pengeluaran tidak valid.',
            'pos_user_id.required'    => 'Nama PIC/Staf wajib dipilih.',
            'pos_user_id.exists'      => 'Staff yang dipilih tidak valid.',
            'amount.required'         => 'Nominal pengeluaran tidak boleh kosong.',
            'amount.numeric'          => 'Nominal harus berupa angka.',
            'amount.min'              => 'Nominal tidak boleh kurang dari 0.',
            'description.required'    => 'Keterangan pengeluaran wajib diisi.',
            'transaction_at.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_at.date'     => 'Format tanggal tidak valid.',
            'image.image'             => 'File harus berupa gambar.',
            'image.mimes'             => 'Format gambar harus jpg, jpeg, atau png.',
            'image.max'               => 'Ukuran gambar maksimal adalah 2MB.',
        ];

        $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'expense_type_id' => 'required|exists:expense_types,id',
            'pos_user_id' => 'required|exists:pos_users,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'transaction_at' => 'required|date',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], $messages);

        $posUserIdForLog = $this->getPosUserId();
        
        // Jalankan Database Transaction agar jika kas gagal diupdate, data pengeluaran dibatalkan
        $expense = DB::transaction(function () use ($request, $posUserIdForLog, &$oldData) {
            $oldData = null;
            $diffAmount = (float) $request->amount;
            $oldStoreId = null;

            if ($request->id) {
                $existingExpense = ExpenseTransaction::find($request->id);
                if ($existingExpense) {
                    $oldData = $existingExpense->getRawOriginal();
                    $oldStoreId = $existingExpense->store_id;
                    
                    // Kembalikan saldo kas lama dulu sebelum memotong dengan saldo baru
                    if ($oldStoreId) {
                        $oldCashStore = CashStore::where('store_id', $oldStoreId)->first();
                        if ($oldCashStore) {
                            $oldCashStore->timestamps = false;
                            $oldCashStore->increment('cash', (float)$oldData['amount']);
                        }
                    }
                }
            }

            $data = $request->only(['store_id', 'pos_user_id', 'expense_type_id', 'amount', 'description', 'transaction_at']);
            
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('expenses', 'public');
            }

            if (!$request->id) {
                $data['created_by'] = $posUserIdForLog;
                $data['status'] = 0;
            }

            $expenseTransaction = ExpenseTransaction::updateOrCreate(['id' => $request->id], $data);

            // Potong Kas Toko Baru (jika store_id diisi / bukan Global)
            if ($request->store_id) {
                $newCashStore = CashStore::where('store_id', $request->store_id)->first();
                if ($newCashStore) {
                    $newCashStore->timestamps = false;
                    // Ambil nilai cash baru agar tidak minus di bawah 0
                    $finalCash = max(0, $newCashStore->cash - (float)$request->amount);
                    $newCashStore->update(['cash' => $finalCash]);
                }
            }

            return $expenseTransaction;
        });

        $expense->load(['store', 'posUser', 'expenseType']);

        $storeName = $expense->store ? $expense->store->name : 'Tanpa Lokasi';
        $typeName = $expense->expenseType ? $expense->expenseType->name : 'Tanpa Tipe';

        // Log Aktivitas
        ActivityLogger::log(
            $request->id ? 'update' : 'create',
            'expense_transactions',
            $expense->id,
            ($request->id ? "Update" : "Input") . " pengeluaran [{$typeName}]: Toko {$storeName} oleh {$expense->posUser->name} (" . number_format($expense->amount, 0, ',', '.') . ")",
            $posUserIdForLog,
            ['old' => $oldData, 'new' => $expense->getAttributes()],
            $expense->store_id
        );

        return back()->with('message', 'Data berhasil disimpan dan kas toko diperbarui.');
    }

    /**
     * Soft delete manual (status 2) dan mengembalikan saldo kas toko.
     */
    public function destroy($id)
    {
        $posUserId = $this->getPosUserId();

        $expense = DB::transaction(function () use ($id) {
            $expenseTransaction = ExpenseTransaction::with(['store', 'posUser'])->findOrFail($id);
            $oldData = $expenseTransaction->getRawOriginal();

            // Kembalikan kas toko jika pengeluaran dihapus
            if ($expenseTransaction->store_id) {
                $cashStore = CashStore::where('store_id', $expenseTransaction->store_id)->first();
                if ($cashStore) {
                    $cashStore->timestamps = false;
                    $cashStore->increment('cash', (float)$expenseTransaction->amount);
                }
            }

            $expenseTransaction->update([
                'status' => 2,
                'deleted_at' => now()
            ]);

            return $expenseTransaction;
        });

        $storeName = $expense->store ? $expense->store->name : 'Tanpa Lokasi';

        ActivityLogger::log(
            'delete',
            'expense_transactions',
            $id,
            "Hapus pengeluaran: Toko {$storeName} oleh {$expense->posUser->name}",
            $posUserId,
            ['old' => $expense->getRawOriginal(), 'new' => $expense->getAttributes()],
            $expense->store_id
        );

        return back()->with('message', 'Data berhasil dihapus dan kas toko dikembalikan.');
    }
}