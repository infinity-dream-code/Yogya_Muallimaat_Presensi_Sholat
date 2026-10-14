<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CatatanKepribadianExport implements FromArray, WithEvents
{
    public function __construct(
        private array $items,
        private array $meta = []
    ) {}

    public function array(): array
    {
        $pad = fn (array $row) => array_pad($row, 10, '');
        $rows = [];
        $rows[] = $pad(['Laporan Catatan Kepribadian Siswi']);
        $rows[] = $pad([
            'Tahun ajaran',
            trim((string) ($this->meta['bta'] ?? '')) ?: 'Semua',
            'Semester',
            trim((string) ($this->meta['semester_label'] ?? 'Semua')),
            'Cari',
            trim((string) ($this->meta['q'] ?? '')) ?: '-',
        ]);
        $rows[] = $pad(['Jumlah', count($this->items).' catatan']);
        $rows[] = $pad([]);
        $rows[] = ['No', 'NIS', 'Nama', 'Kelas', 'Sekolah', 'Tahun ajaran', 'Semester', 'Jenis pelanggaran', 'Bentuk pembinaan', 'Skor'];

        $no = 1;
        foreach ($this->items as $item) {
            $semester = (string) ($item['semester'] ?? '');
            $rows[] = [
                $no++,
                trim((string) ($item['nocust'] ?? '')),
                trim((string) ($item['nmcust'] ?? '')),
                trim((string) ($item['kelas'] ?? '')),
                trim((string) ($item['sekolah'] ?? '')),
                trim((string) ($item['bta'] ?? '')),
                $semester === '2' ? 'Genap' : ($semester === '1' ? 'Ganjil' : $semester),
                trim((string) ($item['jenis_pelanggaran'] ?? '')),
                trim((string) ($item['bentuk_pembinaan'] ?? '')),
                (float) ($item['skor'] ?? 0),
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = 'J';
                $headerRow = 5;
                $lastRow = max($headerRow, (int) $sheet->getHighestRow());
                $sheet->mergeCells('A1:'.$lastCol.'1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2:A3')->getFont()->setBold(true);
                $sheet->getStyle('C2')->getFont()->setBold(true);
                $sheet->getStyle('E2')->getFont()->setBold(true);
                $sheet->getStyle('A'.$headerRow.':'.$lastCol.$headerRow)->getFont()->setBold(true);
                $sheet->getStyle('A'.$headerRow.':'.$lastCol.$headerRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E4EFE6');
                $sheet->getStyle('A'.$headerRow.':'.$lastCol.$lastRow)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setRGB('D5CFC0');
                $sheet->getStyle('A1:'.$lastCol.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A'.$headerRow.':'.$lastCol.$lastRow)->getAlignment()->setWrapText(true);
                $sheet->freezePane('A'.($headerRow + 1));
                $sheet->setAutoFilter('A'.$headerRow.':'.$lastCol.$lastRow);
                $widths = [6, 14, 28, 16, 18, 14, 12, 28, 28, 10];
                foreach ($widths as $i => $w) {
                    $sheet->getColumnDimensionByColumn($i + 1)->setWidth($w);
                }
            },
        ];
    }
}
