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

class LogPresensiExport implements FromArray, WithDrawings, WithEvents
{
    public function __construct(
        protected array $entries,
        protected string $tanggal
    ) {}

    public function array(): array
    {
        $rows = [];
        $rows[] = ['', 'Log Presensi Siswa'];
        $rows[] = ['', 'Tanggal', $this->tanggal];
        $rows[] = ['No', 'Nama', 'NIS', 'Unit', 'Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'];

        $no = 1;
        foreach ($this->entries as $row) {
            $nama = $row['NamaCust'] ?? $row['NAMA'] ?? $row['NAMASISWA'] ?? 'Siswa';
            $nis  = $row['NOCUST'] ?? $row['nocust'] ?? $row['NIS'] ?? $row['NOKARTU'] ?? $row['nis'] ?? '';
            $unit = $row['UNIT'] ?? $row['Unit'] ?? '';
            $cells = [$no++, $nama, (string) $nis, $unit];
            for ($i = 1; $i <= 5; $i++) {
                $status = $row['JADWAL_' . $i] ?? null;
                if ($status === null || $status === '') {
                    $cells[] = '';
                } else {
                    $s = strtoupper((string) $status);
                    if (in_array($s, ['SHOLAT'])) {
                        $cells[] = 'Sholat';
                    } elseif ($s === 'IZIN' || $s === 'TIDAK HADIR') {
                        $cells[] = 'Izin';
                    } elseif ($s === 'ALPA') {
                        $cells[] = 'Alpa';
                    } elseif ($s === 'HAID') {
                        $cells[] = 'Haid';
                    } elseif ($s === 'SAKIT') {
                        $cells[] = 'Sakit';
                    } elseif ($s === 'BELUM PRESENSI') {
                        $cells[] = 'Belum';
                    } else {
                        $cells[] = $status;
                    }
                }
            }
            $rows[] = $cells;
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
        $drawing->setHeight(50);
        $drawing->setCoordinates('A1');
        return [$drawing];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastCol = 'I';
                $headerRow = 3;
                $dataStartRow = 4;

                // Merge header title B1:I1 (logo in A1)
                $sheet->mergeCells('B1:' . $lastCol . '1');

                // Column widths
                $sheet->getColumnDimension('A')->setWidth(10);
                $sheet->getColumnDimension('B')->setWidth(25);
                $sheet->getColumnDimension('C')->setWidth(16);
                $sheet->getColumnDimension('D')->setWidth(18);
                foreach (['E', 'F', 'G', 'H', 'I'] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(12);
                }

                // NIS as text (column C) - avoid scientific notation
                $dataStartRow = 4;
                if ($lastRow >= $dataStartRow) {
                    $sheet->getStyle('C' . $dataStartRow . ':C' . $lastRow)->getNumberFormat()->setFormatCode('@');
                }

                // Header title row 1 - bold, center, gray bg
                $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
                ]);

                // Row 2 - Tanggal
                $sheet->getStyle('A2:' . $lastCol . '2')->applyFromArray([
                    'font'      => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                // Table header row 3 (No, Nama, NIS, Unit, Subuh, ...) - ungu
                $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF6D28D9']],
                    'borders'   => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                // Data rows (row 4+) - tanpa background ungu
                if ($lastRow >= $dataStartRow) {
                    $sheet->getStyle('A' . $dataStartRow . ':' . $lastCol . $lastRow)->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
                        'borders'   => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);
                    // Nama left align
                    $sheet->getStyle('B' . $dataStartRow . ':B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                // Set row height
                $sheet->getRowDimension(1)->setRowHeight(40);
                $sheet->getRowDimension($headerRow)->setRowHeight(24);
            },
        ];
    }
}
