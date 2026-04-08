<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportExpenseController extends Controller
{
    /**
     * Menampilkan halaman laporan biaya operasional.
     */
    public function index(Request $request)
    {
        // Data Dummy yang sudah disesuaikan dengan kolom di Vue (termasuk 'period')
        $dummyExpenses = [
            'data' => [
                [
                    'id' => 1, 
                    'date' => '2024-03-01', 
                    'period' => '2024-03', 
                    'store_name' => 'Cabang Jakarta', 
                    'category' => 'Listrik & Air', 
                    'amount' => 1250000, 
                    'creator' => 'Admin Keuangan'
                ],
                [
                    'id' => 2, 
                    'date' => '2024-03-05', 
                    'period' => '2024-03', 
                    'store_name' => 'Cabang Bandung', 
                    'category' => 'Gaji Karyawan', 
                    'amount' => 3000000, 
                    'creator' => 'Manager'
                ],
                [
                    'id' => 3, 
                    'date' => '2024-03-10', 
                    'period' => '2024-03', 
                    'store_name' => 'Cabang Surabaya', 
                    'category' => 'Logistik', 
                    'amount' => 450000, 
                    'creator' => 'Staff'
                ],
                [
                    'id' => 4, 
                    'date' => '2024-04-02', 
                    'period' => '2024-04', 
                    'store_name' => 'Cabang Jakarta', 
                    'category' => 'Pemeliharaan', 
                    'amount' => 600000, 
                    'creator' => 'System'
                ],
            ],
            'links' => [
                ['url' => null, 'label' => '&laquo; Previous', 'active' => false],
                ['url' => '#', 'label' => '1', 'active' => true],
                ['url' => null, 'label' => 'Next &raquo;', 'active' => false],
            ]
        ];

        return Inertia::render('ReportExpense/Index', [
            'expenses' => $dummyExpenses,
            'stores' => [
                ['id' => 1, 'name' => 'Cabang Jakarta'],
                ['id' => 2, 'name' => 'Cabang Bandung'],
                ['id' => 3, 'name' => 'Cabang Surabaya'],
            ],
            'expenseCategories' => [
                'Listrik & Air', 
                'Gaji Karyawan', 
                'Logistik', 
                'Sewa Gedung', 
                'Pemeliharaan', 
                'Lain-lain'
            ],
            // Mengirim balik filter agar dropdown tetap terpilih setelah reload
            'filters' => $request->only(['search', 'store_id', 'category'])
        ]);
    }

    /**
     * Simulasi menyimpan data pengeluaran.
     */
    public function store(Request $request)
    {
        // Validasi simpel (opsional untuk sementara)
        // $request->validate([...]);

        return redirect()->back()->with('success', 'Data pengeluaran berhasil dicatat (Simulasi)');
    }

    /**
     * Simulasi menghapus data pengeluaran.
     */
    public function destroy($id)
    {
        return redirect()->back()->with('success', 'Data pengeluaran berhasil dihapus');
    }

    /**
     * Simulasi ekspor data ke Excel.
     */
    public function export()
    {
        // Di sini nantinya logika Excel
        return response()->json(['message' => 'Fungsi ekspor akan segera hadir']);
    }
}