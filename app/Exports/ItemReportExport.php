<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ItemReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Nama Item',
            'Tipe Item',
            'Nama Toko',
            'Total Terjual (Qty)',
            'Total Omset (Rp)',
            'Transaksi Terakhir',
        ];
    }

    public function map($row): array
    {
        return [
            $row->item_name,
            strtoupper($row->item_type),
            $row->store_name,
            $row->total_qty,
            $row->total_sales,
            $row->last_transaction_at,
        ];
    }
}