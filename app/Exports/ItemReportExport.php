<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ItemReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $itemsData;
    protected $params;

    public function __construct($itemsData, $params)
    {
        $this->itemsData = $itemsData;
        $this->params = $params;
    }

    public function collection()
    {
        return $this->itemsData;
    }

    public function map($item): array
    {
        return [
            $item->product_name,
            $item->category_name,
            $item->total_qty,
            $item->total_modal,
            $item->total_omzet,
            $item->laba,
        ];
    }

    public function headings(): array
    {
        $startDate = $this->params['start_date'] ?? 'Awal';
        $endDate = $this->params['end_date'] ?? 'Akhir';

        return [
            ['LAPORAN PENJUALAN PER ITEM'],
            ['Cabang: ' . $this->params['store_name']],
            ['Kasir: ' . $this->params['staff_name']],
            ['Pencarian Item: ' . strtoupper($this->params['item_name'])],
            ['Periode: ' . $startDate . ' s/d ' . $endDate],
            [], // Baris kosong sebagai pemisah
            [
                'Nama Item',
                'Kategori',
                'Qty Terjual',
                'Total Modal (Beli)',
                'Total Omzet (Jual)',
                'Laba Kotor'
            ]
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style judul utama
            1 => ['font' => ['bold' => true, 'size' => 14]],
            // Style info filter
            2 => ['font' => ['italic' => true]],
            3 => ['font' => ['italic' => true]],
            4 => ['font' => ['italic' => true]],
            5 => ['font' => ['italic' => true]],
            // Style header tabel (baris ke-7 karena ada baris kosong di indeks 6)
            7 => [
                'font' => ['bold' => true],
                'borders' => [
                    'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK],
                ]
            ],
        ];
    }
}