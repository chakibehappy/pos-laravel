<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles; // Tambahkan ini
use Maatwebsite\Excel\Concerns\ShouldAutoSize; // Tambahkan ini
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

    /**
     * Styling Excel (Header Biru, Border, dan Font)
     */
    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // Set tinggi header 2x lipat
        $sheet->getRowDimension(1)->setRowHeight(30);

        return [
            // 1. Style Header (Baris 1)
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

            // 2. Kolom ID (A) & Jumlah Stok (C) -> Center & Middle Align
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

            // 3. Kolom Tanggal Update (D) -> Right Align
            'D2:D' . $highestRow => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],

            // 4. Border untuk seluruh tabel
            'A1:D' . $highestRow => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ],
        ];
    }

    public function headings(): array
    {
        return ['ID', 'Nama Produk', 'Jumlah Stok', 'Tanggal Update'];
    }

    public function map($storeProduct): array
    {
        return [
            $storeProduct->id,
            $storeProduct->product->name ?? 'N/A',
            $storeProduct->stock,
            $storeProduct->updated_at->format('d/m/Y H:i'),
        ];
    }
}