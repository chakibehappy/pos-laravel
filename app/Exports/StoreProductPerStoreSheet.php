<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Eloquent\Builder;

class StoreProductPerStoreSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $query;
    protected $storeName;
    protected $categoryName;

    public function __construct(Builder $query, $storeName, $categoryName)
    {
        $this->query = $query;
        $this->storeName = $storeName;
        $this->categoryName = $categoryName;
    }

    public function query()
    {
        return $this->query->with(['product']);
    }

    public function title(): string
    {
        return substr("{$this->storeName} ({$this->categoryName})", 0, 31);
    }

    public function headings(): array
    {
        // Penambahan Kolom Harga Beli dan Harga Jual setelah Jumlah Stok
        return [
            'ID', 
            'Nama Produk', 
            'Jumlah Stok', 
            'Harga Modal',     // Kolom ke-4
            'Harga Jual',      // Kolom ke-5
            'Tanggal Update'   // Kolom ke-6
        ];
    }

    public function map($storeProduct): array
    {
        // Pastikan data relasi produk ada sebelum memanggil propertinya
        return [
            $storeProduct->id,
            $storeProduct->product->name ?? 'N/A',
            $storeProduct->stock,
            $storeProduct->product->buying_price ?? 0,
            $storeProduct->product->selling_price ?? 0,
            $storeProduct->updated_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // Set tinggi header 30px
        $sheet->getRowDimension(1)->setRowHeight(30);

        return [
            // 1. Style Header
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4F81BD']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],

            // 2. Kolom ID (A) & Jumlah Stok (C) -> Center
            'A2:A' . $highestRow => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
            'C2:C' . $highestRow => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],

            // 3. Kolom Harga Beli (D) & Harga Jual (E) -> Right Align dan format Rupiah (Opsional)
            'D2:E' . $highestRow => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],

            // 4. Kolom Tanggal Update (F) -> Right Align
            'F2:F' . $highestRow => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],

            // 5. Border untuk seluruh tabel yang diperluas hingga kolom F
            'A1:F' . $highestRow => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ],
        ];
    }
}