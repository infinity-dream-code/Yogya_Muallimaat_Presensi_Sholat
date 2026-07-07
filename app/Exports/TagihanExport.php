<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TagihanExport implements FromArray, WithDrawings, WithEvents
{
    private const LAST_COL = 'M';

    /** Baris header kolom tabel (No, NIS, ...) — tanpa baris kosong di atasnya */
    private const HEADER_ROW = 6;

    private const DATA_START_ROW = 7;

    public function __construct(
        protected array $rows,
        protected array $filters,
        protected ?float $totalJumlah = null
    ) {}

    public function array(): array
    {
        $rows = [];
        $rows[] = ['', 'Data Tagihan Siswa'];
        $rows[] = ['', 'Tahun Akademik (BTA)', $this->filters['bta'] ?? 'Semua'];
        $rows[] = ['', 'Kelas', $this->filters['kelas'] ?? 'Semua'];
        $rows[] = ['', 'Status Bayar', $this->filters['status'] ?? 'Semua'];
        $rows[] = ['', 'Pencarian', $this->filters['search'] ?? '-'];
        $rows[] = [
            'No', 'NIS', 'Nama', 'Kelas', 'Kelompok', 'Kode Tagihan', 'Nama Tagihan', 'Urutan',
            'Jumlah', 'BTA', 'Status Bayar', 'Tanggal Tagihan', 'Tanggal Bayar',
        ];

        $no = 1;
        foreach ($this->rows as $row) {
            $paidst = (int) ($row['status_bayar'] ?? 0);
            $rows[] = [
                $no++,
                (string) ($row['nis'] ?? ''),
                (string) ($row['nama'] ?? ''),
                (string) ($row['kelas'] ?? ''),
                (string) ($row['kelompok'] ?? ''),
                (string) ($row['kode_tagihan'] ?? ''),
                (string) ($row['nama_tagihan'] ?? ''),
                (string) ($row['furutan'] ?? ''),
                (float) ($row['jumlah'] ?? 0),
                (string) ($row['tahun_akademik'] ?? ''),
                $paidst === 1 ? 'Lunas' : 'Belum Lunas',
                (string) ($row['tanggal_tagihan'] ?? ''),
                (string) ($row['tanggal_bayar'] ?? ''),
            ];
        }

        if ($this->totalJumlah !== null) {
            $rows[] = ['', '', '', '', '', '', '', 'Total Tagihan', $this->totalJumlah];
        }

        return $rows;
    }

    public function drawings(): array
    {
        $logoPath = public_path('logo.png');
        if (! file_exists($logoPath)) {
            return [];
        }

        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setPath($logoPath);
        $drawing->setHeight(40);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(10);
        $drawing->setOffsetY(8);

        return [$drawing];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastCol = self::LAST_COL;
                $headerRow = self::HEADER_ROW;
                $dataStartRow = self::DATA_START_ROW;
                $hasTotal = $this->totalJumlah !== null;
                $dataEndRow = $hasTotal ? $lastRow - 1 : $lastRow;
                $totalRow = $hasTotal ? $lastRow : null;

                $sheet->mergeCells('B1:' . $lastCol . '1');

                $sheet->getColumnDimension('A')->setWidth(12);
                $sheet->getColumnDimension('B')->setWidth(14);
                $sheet->getColumnDimension('C')->setWidth(30);
                $sheet->getColumnDimension('D')->setWidth(12);
                $sheet->getColumnDimension('E')->setWidth(12);
                $sheet->getColumnDimension('F')->setWidth(16);
                $sheet->getColumnDimension('G')->setWidth(22);
                $sheet->getColumnDimension('H')->setWidth(10);
                $sheet->getColumnDimension('I')->setWidth(14);
                $sheet->getColumnDimension('J')->setWidth(12);
                $sheet->getColumnDimension('K')->setWidth(14);
                $sheet->getColumnDimension('L')->setWidth(18);
                $sheet->getColumnDimension('M')->setWidth(18);

                $sheet->getRowDimension(1)->setRowHeight(46);
                foreach ([2, 3, 4, 5] as $r) {
                    $sheet->getRowDimension($r)->setRowHeight(20);
                }
                $sheet->getRowDimension($headerRow)->setRowHeight(28);

                // Judul laporan
                $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
                    'font'      => ['bold' => true, 'size'  => 14],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'indent'     => 1,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF3F4F6'],
                    ],
                ]);

                // Info filter
                $sheet->getStyle('B2:B5')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                ]);
                $sheet->getStyle('C2:C5')->applyFromArray([
                    'font'      => ['bold' => false, 'size' => 10],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                ]);

                // Header kolom tabel
                $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'indent'     => 1,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF6D28D9'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                // Data — font normal, bukan bold
                if ($dataEndRow >= $dataStartRow) {
                    $sheet->getStyle('A' . $dataStartRow . ':' . $lastCol . $dataEndRow)->applyFromArray([
                        'font'      => ['bold' => false, 'size' => 10],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                            'indent'     => 1,
                        ],
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFFFFFFF'],
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);

                    $sheet->getStyle('A' . $dataStartRow . ':' . $lastCol . $dataEndRow)
                        ->getFont()->setBold(false);

                    $sheet->getStyle('C' . $dataStartRow . ':C' . $dataEndRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle('G' . $dataStartRow . ':G' . $dataEndRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                    $sheet->getStyle('B' . $dataStartRow . ':B' . $dataEndRow)
                        ->getNumberFormat()->setFormatCode('@');

                    $sheet->getStyle('I' . $dataStartRow . ':I' . $dataEndRow)
                        ->getNumberFormat()->setFormatCode('#,##0');
                }

                // Baris total
                if ($totalRow !== null) {
                    $sheet->getStyle('A' . $totalRow . ':' . $lastCol . $totalRow)->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 10],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_RIGHT,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                            'indent'     => 1,
                        ],
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFEDE9FE'],
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);
                        $sheet->getStyle('I' . $totalRow)
                            ->getNumberFormat()->setFormatCode('#,##0');
                }
            },
        ];
    }
}
