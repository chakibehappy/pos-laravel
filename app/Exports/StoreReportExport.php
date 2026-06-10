<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Carbon\Carbon;

class StoreReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithCustomStartCell
{
    protected $reportData;
    protected $categories;
    protected $wallets;
    protected $params; 

    protected $startCellRow = 5; 
    protected $dataStartRow = 8; 

    public function __construct($reportData, $categories, $wallets, $params = [])
    {
        $this->reportData = $reportData;
        $this->categories = $categories;
        $this->wallets = $wallets;
        $this->params = $params;
    }

    public function startCell(): string
    {
        return 'A' . $this->startCellRow;
    }

    private function getTotalColumnsCount(): int
    {
        return 2 + (count($this->categories) * 2) + (count($this->wallets) * 2) + 2 + 3;
    }

    public function collection()
    {
        $rows = $this->reportData->map(function ($item) {
            $row = [
                $item->nama_cabang,
                $item->qty,
            ];

            // FIX: Tambahkan prefix 'cat_' agar sesuai dengan ReportStoreController
            foreach ($this->categories as $cat) {
                $cleanKey = strtolower(str_replace(' ', '_', $cat->name));
                $keyBeli = 'cat_' . $cleanKey . '_beli';
                $keyJual = 'cat_' . $cleanKey . '_jual';
                
                $row[] = $item->$keyBeli ?? 0;
                $row[] = $item->$keyJual ?? 0;
            }

            // FIX: Tambahkan prefix 'wallet_' agar sesuai dengan ReportStoreController
            foreach ($this->wallets as $wallet) {
                $cleanKey = strtolower(str_replace(' ', '_', $wallet->name));
                $keyBeli = 'wallet_' . $cleanKey . '_beli';
                $keyJual = 'wallet_' . $cleanKey . '_jual';
                
                $row[] = $item->$keyBeli ?? 0;
                $row[] = $item->$keyJual ?? 0;
            }

            $row[] = $item->tarik_tunai_beli;
            $row[] = $item->tarik_tunai_jual;

            $row[] = $item->total; 
            $row[] = $item->operasional;
            $row[] = $item->laba_cabang;

            return $row;
        });

        if ($rows->count() > 0) {
            $numCols = $this->getTotalColumnsCount();
            
            // 1. Baris TOTAL
            $totalRow = ['TOTAL'];
            for ($i = 1; $i < $numCols; $i++) {
                $totalRow[$i] = $rows->sum($i);
            }
            $rows->push($totalRow);

            // 2. Baris PENGELUARAN GLOBAL
            $globalExpense = (float)($this->params['global_expense'] ?? 0);
            $globalRow = array_fill(0, $numCols, '');
            $globalRow[0] = 'PENGELUARAN GLOBAL';
            $globalRow[$numCols - 1] = $globalExpense; 
            $rows->push($globalRow);

            // 3. Baris LABA BERSIH (Akhir)
            $netProfitRow = array_fill(0, $numCols, '');
            $netProfitRow[0] = 'LABA BERSIH';
            $netProfitRow[$numCols - 1] = $totalRow[$numCols - 1] - $globalExpense;
            $rows->push($netProfitRow);
        }

        return $rows;
    }

    public function headings(): array
    {
        $detailColsCount = (count($this->categories) * 2) + (count($this->wallets) * 2) + 2;

        $header1 = ['NAMA CABANG', 'QTY', 'RINCIAN PENJUALAN'];
        for ($i = 1; $i < $detailColsCount; $i++) { $header1[] = ''; }
        $header1[] = 'LABA KOTOR';
        $header1[] = 'BIAYA OPERASIONAL';
        $header1[] = 'LABA BERSIH';

        $header2 = ['', ''];
        foreach ($this->categories as $cat) {
            $header2[] = strtoupper($cat->name);
            $header2[] = ''; 
        }
        foreach ($this->wallets as $wallet) {
            $header2[] = strtoupper($wallet->name);
            $header2[] = '';
        }
        $header2[] = 'TARIK TUNAI';
        $header2[] = '';
        $header2[] = ''; $header2[] = ''; $header2[] = '';

        $header3 = ['', '']; 
        $totalGroups = count($this->categories) + count($this->wallets) + 1;
        for ($i = 0; $i < $totalGroups; $i++) {
            $header3[] = 'PEMBELIAN';
            $header3[] = 'PENJUALAN';
        }
        $header3[] = ''; $header3[] = ''; $header3[] = '';

        return [$header1, $header2, $header3];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColNum = $this->getTotalColumnsCount();
                $lastCol = Coordinate::stringFromColumnIndex($lastColNum);

                // --- JUDUL DAN INFO ---
                $sheet->setCellValue('A1', 'Laporan Transaksi Maar Company');
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $startDate = !empty($this->params['start_date']) ? Carbon::parse($this->params['start_date'])->format('d-m-Y') : '-';
                $endDate = !empty($this->params['end_date']) ? Carbon::parse($this->params['end_date'])->format('d-m-Y') : '-';
                $sheet->setCellValue('A2', 'Periode:'); $sheet->setCellValue('B2', $startDate);
                $sheet->setCellValue('C2', 'Hingga :'); $sheet->setCellValue('D2', $endDate);
                $sheet->setCellValue('A3', 'Jenis Usaha:'); $sheet->setCellValue('B3', strtoupper($this->params['store_type_name'] ?? 'SEMUA'));
                $sheet->setCellValue('C3', 'Nama Toko:'); $sheet->setCellValue('D3', strtoupper($this->params['store_name'] ?? 'SELURUH TOKO'));
                $sheet->getStyle('A2:A3')->getFont()->setBold(true);
                $sheet->getStyle('C2:C3')->getFont()->setBold(true);

                // --- HEADER TABEL ---
                $hStart = $this->startCellRow; 
                $hEnd = $this->startCellRow + 2; 
                $sheet->mergeCells("A{$hStart}:A{$hEnd}"); 
                $sheet->mergeCells("B{$hStart}:B{$hEnd}"); 
                $detailColsCount = (count($this->categories) * 2) + (count($this->wallets) * 2) + 2;
                $sheet->mergeCells("C{$hStart}:" . Coordinate::stringFromColumnIndex(2 + $detailColsCount) . "{$hStart}");

                $currentCol = 3;
                $totalGroups = count($this->categories) + count($this->wallets) + 1;
                for ($i = 0; $i < $totalGroups; $i++) {
                    $sheet->mergeCells(Coordinate::stringFromColumnIndex($currentCol) . ($hStart+1) . ':' . Coordinate::stringFromColumnIndex($currentCol + 1) . ($hStart+1));
                    $currentCol += 2;
                }
                for ($j = 0; $j < 3; $j++) {
                    $col = Coordinate::stringFromColumnIndex($currentCol + $j);
                    $sheet->mergeCells("{$col}{$hStart}:{$col}{$hEnd}");
                }

                $sheet->getStyle("A{$hStart}:{$lastCol}{$hEnd}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92D050']],
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // --- STYLING BARIS FOOTER ---
                
                // Baris TOTAL (Merah)
                $sheet->getStyle("A".($lastRow-2).":{$lastCol}".($lastRow-2))->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF0000']],
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                ]);

                // Baris PENGELUARAN GLOBAL (Teks Bold)
                $sheet->getStyle("A".($lastRow-1).":{$lastCol}".($lastRow-1))->getFont()->setBold(true);

                // Baris LABA BERSIH (Kuning)
                $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                    'font' => ['bold' => true],
                ]);

                // Border & Format Angka
                $sheet->getStyle("A{$hStart}:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);
                
                $sheet->getStyle("B{$this->dataStartRow}:{$lastCol}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("C{$hEnd}:{$lastCol}{$hEnd}")->getFont()->setSize(8);
            },
        ];
    }
}s