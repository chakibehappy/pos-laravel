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
use Carbon\Carbon;

class StoreReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithCustomStartCell
{
    protected $reportData;
    protected $categories;
    protected $wallets;
    protected $params; // Untuk menyimpan info Periode, Nama Toko, Jenis Usaha

    public function __construct($reportData, $categories, $wallets, $params = [])
    {
        $this->reportData = $reportData;
        $this->categories = $categories;
        $this->wallets = $wallets;
        $this->params = $params;
    }

    // Tentukan tabel mulai dari baris ke-5 untuk memberi ruang Judul
    public function startCell(): string
    {
        return 'A5';
    }

    public function collection()
    {
        $rows = $this->reportData->map(function ($item) {
            $row = [
                $item->nama_cabang,
                $item->qty,
            ];

            foreach ($this->categories as $cat) {
                $keyJual = strtolower($cat->name) . '_jual';
                $keyBeli = strtolower($cat->name) . '_beli';
                $row[] = $item->$keyBeli;
                $row[] = $item->$keyJual;
            }

            foreach ($this->wallets as $wallet) {
                $cleanKey = strtolower(str_replace(' ', '_', $wallet->name));
                $row[] = $item->{$cleanKey . '_beli'};
                $row[] = $item->{$cleanKey . '_jual'};
            }

            $row[] = $item->tarik_tunai_beli;
            $row[] = $item->tarik_tunai_jual;

            $row[] = $item->total; 
            $row[] = $item->operasional;
            $row[] = $item->laba_bersih;

            return $row;
        });

        // --- LOGIKA PENJUMLAHAN TOTAL DI BAWAH ---
        if ($rows->count() > 0) {
            $totalRow = ['TOTAL']; // Kolom A
            
            // Hitung total untuk setiap kolom mulai dari QTY (index 1) sampai akhir
            $numCols = count($rows[0]);
            for ($i = 1; $i < $numCols; $i++) {
                $totalRow[$i] = $rows->sum($i);
            }
            
            $rows->push($totalRow);
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
                $lastColNum = 2 + (count($this->categories) * 2) + (count($this->wallets) * 2) + 2 + 3;
                $lastCol = $this->getColumnLetter($lastColNum);

                // --- BAGIAN 1: JUDUL DAN INFO (Baris 1-3) ---
                $sheet->setCellValue('A1', 'Laporan Transaksi Maar Company');
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $startDate = $this->params['start_date'] ? Carbon::parse($this->params['start_date'])->format('d-m-Y') : '-';
                $endDate = $this->params['end_date'] ? Carbon::parse($this->params['end_date'])->format('d-m-Y') : '-';
                
                $storeTypeName = strtoupper($this->params['store_type_name'] ?? 'SEMUA JENIS USAHA');
                $storeName = strtoupper($this->params['store_name'] ?? 'SELURUH TOKO');

                $sheet->setCellValue('A2', 'Periode:');
                $sheet->setCellValue('B2', $startDate);
                $sheet->setCellValue('C2', 'Hingga :');
                $sheet->setCellValue('D2', $endDate);
                
                $sheet->setCellValue('A3', 'Jenis Usaha:');
                $sheet->setCellValue('B3', $storeTypeName);
                $sheet->setCellValue('C3', 'Nama Toko:');
                $sheet->setCellValue('D3', $storeName);

                $sheet->getStyle('A2:A3')->getFont()->setBold(true);
                $sheet->getStyle('C2:C3')->getFont()->setBold(true);

                // --- BAGIAN 2: HEADER TABEL (Mulai di Baris 5) ---
                $hStart = 5; $hEnd = 7;
                
                $sheet->mergeCells("A{$hStart}:A{$hEnd}"); 
                $sheet->mergeCells("B{$hStart}:B{$hEnd}"); 

                $detailColsCount = (count($this->categories) * 2) + (count($this->wallets) * 2) + 2;
                $sheet->mergeCells("C{$hStart}:" . $this->getColumnLetter(2 + $detailColsCount) . "{$hStart}");

                $currentCol = 3;
                $totalGroups = count($this->categories) + count($this->wallets) + 1;
                for ($i = 0; $i < $totalGroups; $i++) {
                    $sheet->mergeCells($this->getColumnLetter($currentCol) . ($hStart+1) . ':' . $this->getColumnLetter($currentCol + 1) . ($hStart+1));
                    $currentCol += 2;
                }

                for ($j = 0; $j < 3; $j++) {
                    $col = $this->getColumnLetter($currentCol + $j);
                    $sheet->mergeCells("{$col}{$hStart}:{$col}{$hEnd}");
                }

                // --- BAGIAN 3: STYLING HEADER ---
                $headerRange = "A{$hStart}:{$lastCol}{$hEnd}";
                $sheet->getStyle($headerRange)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92D050']],
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // --- BAGIAN 4: STYLING BARIS TOTAL (BARIS TERAKHIR) ---
                $totalRange = "A{$lastRow}:{$lastCol}{$lastRow}";
                $sheet->getStyle($totalRange)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF0000']], // Warna Merah
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], // Teks Putih
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Border untuk seluruh tabel
                $sheet->getStyle("A{$hStart}:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);

                $sheet->getStyle("C{$hEnd}:{$lastCol}{$hEnd}")->getFont()->setSize(8);
            },
        ];
    }

    private function getColumnLetter($columnNumber)
    {
        $letter = '';
        while ($columnNumber > 0) {
            $temp = ($columnNumber - 1) % 26;
            $letter = chr($temp + 65) . $letter;
            $columnNumber = ($columnNumber - $temp - 1) / 26;
        }
        return $letter;
    }
}