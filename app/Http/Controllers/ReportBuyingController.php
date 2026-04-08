<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportBuyingController extends Controller
{
    /**
     * Menampilkan halaman laporan pembelian.
     */
    public function index(Request $request)
    {
        // Data dummy yang disesuaikan dengan tampilan gambar
        $purchases = [
            'data' => [
                [
                    'id' => 1,
                    'tanggal' => '2023-12-05',
                    'nomor_faktur' => 'INV-0012',
                    'pemasok' => 'CV. MEKAR TANI',
                    'deskripsi' => 'Pupuk Organik A',
                    'kuantitas' => 500,
                    'harga_satuan' => 150000,
                    'total' => 75000000,
                    'status' => 'Lunas'
                ],
                [
                    'id' => 2,
                    'tanggal' => '2023-12-12',
                    'nomor_faktur' => 'INV-0015',
                    'pemasok' => 'PT. AGRO MAKMUR',
                    'deskripsi' => 'Bibit Padi Unggul',
                    'kuantitas' => 200,
                    'harga_satuan' => 75000,
                    'total' => 15000000,
                    'status' => 'Lunas'
                ]
            ],
            // Simulasi pagination sederhana jika diperlukan oleh DataTable component Anda
            'links' => [], 
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 10,
                'to' => 2,
                'total' => 2,
            ]
        ];

        return Inertia::render('ReportBuying/Index', [
            'purchases' => $purchases,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Fitur ekspor sementara (placeholder).
     */
    public function export(Request $request)
    {
        // Di sini nantinya logic Maatwebsite/Excel atau sejenisnya
        return response()->json(['message' => 'Fitur ekspor akan segera tersedia.']);
    }
}