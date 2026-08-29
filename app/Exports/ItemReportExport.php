<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Carbon\Carbon;

class ItemReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithCustomStartCell
{
    protected $itemsData;
    protected $params;
    protected $startCellRow = 7; // Mulai tabel utama di baris ke-7

    public function __construct($itemsData, $params)
    {
        $this->itemsData = $itemsData;
        $this->params = $params;
    }

    public function startCell(): string
    {
        return 'A' . $this->startCellRow;
    }

    public function collection()
    {
        // 1. Mapping baris data dari collection
        $rows = $this->itemsData->map(function ($item) {
            return [
                $item->product_name,
                $item->category_name,
                $item->total_qty,
                $item->total_modal,
                $item->total_omzet,
                $item->laba,
            ];
        });

        // 2. Tambahkan Baris TOTAL di paling bawah
        if ($rows->count() > 0) {
            $totalRow = [
                'TOTAL KESELURUHAN', // Kolom A
                '',                  // Kolom B (Dikosongkan untuk di-merge nanti)
                $this->itemsData->sum('total_qty'),
                $this->itemsData->sum('total_modal'),
                $this->itemsData->sum('total_omzet'),
                $this->itemsData->sum('laba'),
            ];
            $rows->push($totalRow);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            [
                'NAMA ITEM',
                'KATEGORI',
                'QTY TERJUAL',
                'TOTAL MODAL (BELI)',
                'TOTAL OMZET (JUAL)',
                'LABA KOTOR'
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastCol = 'F'; // Karena ada 6 kolom (A sampai F)

                // --- 1. JUDUL DAN INFO FILTER ---
                $sheet->setCellValue('A1', 'LAPORAN PENJUALAN PER ITEM');
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Setup format tanggal
                $startDate = !empty($this->params['start_date']) ? Carbon::parse($this->params['start_date'])->format('d-m-Y') : '-';
                $endDate = !empty($this->params['end_date']) ? Carbon::parse($this->params['end_date'])->format('d-m-Y') : '-';
                
                // Menulis Filter
                $sheet->setCellValue('A2', 'Periode'); $sheet->setCellValue('B2', ': ' . $startDate . ' s/d ' . $endDate);
                $sheet->setCellValue('A3', 'Cabang'); $sheet->setCellValue('B3', ': ' . strtoupper($this->params['store_name'] ?? 'SEMUA'));
                $sheet->setCellValue('A4', 'Kasir'); $sheet->setCellValue('B4', ': ' . strtoupper($this->params['staff_name'] ?? 'SEMUA'));
                $sheet->setCellValue('A5', 'Nama Item'); $sheet->setCellValue('B5', ': ' . strtoupper($this->params['item_name'] ?? 'SEMUA'));
                
                // Bold untuk label filter
                $sheet->getStyle('A2:A5')->getFont()->setBold(true);

                // --- 2. HEADER TABEL STYLING ---
                $hStart = $this->startCellRow; 
                
                $sheet->getStyle("A{$hStart}:{$lastCol}{$hStart}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID, 
                        'startColor' => ['rgb' => 'FF4F81BD'] // Warna Biru mengikuti tabel StoreProductExport
                    ],
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], // Teks putih bold
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER, 
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);
                
                // Set tinggi baris header (30px)
                $sheet->getRowDimension($hStart)->setRowHeight(30);

                // --- 3. STYLING BARIS FOOTER (TOTAL) ---
                if ($lastRow > $hStart) {
                    $sheet->mergeCells("A{$lastRow}:B{$lastRow}"); // Merge kolom A & B untuk label total
                    
                    $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID, 
                            'startColor' => ['rgb' => 'FF0000'] // Background Merah mengikuti StoreReportExport
                        ],
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], // Teks putih
                    ]);
                    
                    // Rata kanan untuk tulisan 'TOTAL KESELURUHAN'
                    $sheet->getStyle("A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // --- 4. BORDERS & NUMBER FORMATS ---
                // Border semua sel data
                $sheet->getStyle("A{$hStart}:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN, 
                            'color' => ['rgb' => '000000']
                        ]
                    ],
                ]);
                
                // Format angka (#,##0) untuk Qty Terjual, Modal, Omzet, Laba (Kolom C sampai F)
                $sheet->getStyle("C".($hStart+1).":{$lastCol}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                
                // Alignment Data (Kolom ID dan Qty rata tengah, nominal rata kanan)
                $sheet->getStyle("C".($hStart+1).":C".($lastRow-1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D".($hStart+1).":F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}