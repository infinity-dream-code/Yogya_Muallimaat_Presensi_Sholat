<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TahfidLaporanExport implements WithMultipleSheets
{
    public function __construct(
        private array $items,
        private array $ringkasan,
        private array $meta = []
    ) {}

    public function sheets(): array
    {
        return [
            new TahfidLaporanRekapSheet($this->items, $this->ringkasan, $this->meta),
            new TahfidLaporanSuratSheet($this->items, $this->meta),
        ];
    }
}

class TahfidLaporanRekapSheet implements FromArray, WithTitle, WithEvents
{
    public function __construct(
        private array $items,
        private array $ringkasan,
        private array $meta
    ) {}

    public function title(): string
    {
        return 'Rekap Siswi';
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = ['Laporan Rekap Setoran Tahfid', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''];
        $rows[] = ['Unit', trim((string) ($this->meta['sekolah'] ?? '')) ?: '-', 'Tingkat', trim((string) ($this->meta['tingkat'] ?? '')) ?: 'Semua', 'Kelas', trim((string) ($this->meta['kelas'] ?? '')) ?: 'Semua', '', '', '', '', '', '', '', '', '', ''];
        $rows[] = ['Filter', trim((string) ($this->meta['filter_label'] ?? 'Keseluruhan')), 'Cari', trim((string) ($this->meta['q'] ?? '')) ?: '-', '', '', '', '', '', '', '', '', '', '', '', ''];
        $rows[] = [
            'Ringkasan',
            ((int) ($this->ringkasan['jumlah_siswi'] ?? 0)).' siswi berjadwal · '.
            ((int) ($this->ringkasan['lunas_penuh'] ?? 0)).' lunas penuh · '.
            ((int) ($this->ringkasan['masih_proses'] ?? 0)).' proses · '.
            ((int) ($this->ringkasan['belum_mulai'] ?? 0)).' belum mulai',
            '', '', '', '', '', '', '', '', '', '', '', '', '',
        ];
        $rows[] = ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''];
        $rows[] = [
            'No', 'NIS', 'Nama', 'Kelas', 'Jml surat', 'Lunas', 'Proses', 'Belum',
            'Persen', 'Status', 'Percepatan', 'Jadwal surat', 'Sudah setor', 'Masih proses', 'Belum setor', 'Catatan terakhir',
        ];

        $no = 1;
        foreach ($this->items as $item) {
            if ((int) ($item['target'] ?? 0) < 1) {
                continue;
            }
            $groups = tahfidLaporanGroupSurat($item);
            $notes = [];
            foreach (is_array($item['details'] ?? null) ? $item['details'] : [] as $row) {
                $note = trim((string) ($row['catatan'] ?? ''));
                if ($note === '') {
                    continue;
                }
                $nama = trim((string) ($row['surah_nama'] ?? ''));
                if ($nama === '') {
                    $nama = 'Surat '.(int) ($row['surah_nomor'] ?? 0);
                }
                $notes[] = $nama.': '.$note;
            }
            $rows[] = [
                $no++,
                trim((string) ($item['nocust'] ?? '')),
                trim((string) ($item['nmcust'] ?? '')),
                trim((string) ($item['kelas'] ?? '')),
                (int) ($item['target'] ?? 0),
                (int) ($item['lunas'] ?? 0),
                (int) ($item['proses'] ?? 0),
                (int) ($item['belum'] ?? 0),
                ((int) ($item['persen'] ?? 0)).'%',
                tahfidLaporanStatusLabel((string) ($item['badge'] ?? 'belum')),
                ! empty($item['percepatan']) ? 'Ya' : 'Tidak',
                implode(', ', $groups['jadwal']),
                implode(', ', $groups['lunas']),
                implode(', ', $groups['proses']),
                implode(', ', $groups['belum']),
                implode(' | ', $notes),
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                tahfidLaporanStyleSheet($event, 6, 16, [
                    1 => 6, 2 => 14, 3 => 28, 4 => 16, 5 => 10, 6 => 9, 7 => 9, 8 => 9,
                    9 => 9, 10 => 11, 11 => 12, 12 => 36, 13 => 32, 14 => 28, 15 => 32, 16 => 36,
                ], [10], [11]);
            },
        ];
    }
}

class TahfidLaporanSuratSheet implements FromArray, WithTitle, WithEvents
{
    public function __construct(
        private array $items,
        private array $meta
    ) {}

    public function title(): string
    {
        return 'Detail Surat';
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = ['Detail Jadwal dan Setoran Surat', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''];
        $rows[] = ['Unit', trim((string) ($this->meta['sekolah'] ?? '')) ?: '-', 'Tingkat', trim((string) ($this->meta['tingkat'] ?? '')) ?: 'Semua', 'Kelas', trim((string) ($this->meta['kelas'] ?? '')) ?: 'Semua', '', '', '', '', '', '', '', '', '', ''];
        $rows[] = ['Filter', trim((string) ($this->meta['filter_label'] ?? 'Keseluruhan')), 'Cari', trim((string) ($this->meta['q'] ?? '')) ?: '-', '', '', '', '', '', '', '', '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''];
        $rows[] = ['No', 'NIS', 'Nama', 'Kelas', 'Urut', 'No surat', 'Nama surat', 'Ayat dari', 'Ayat sampai', 'Rentang ayat', 'Catatan', 'Waktu setor', 'Asal jadwal', 'Kelas sumber', 'Status', 'Sudah setor'];

        $no = 1;
        foreach ($this->items as $item) {
            $details = is_array($item['details'] ?? null) ? $item['details'] : [];
            if (count($details) === 0) {
                continue;
            }
            foreach ($details as $i => $row) {
                $status = strtolower(trim((string) ($row['status'] ?? 'belum')));
                $sumber = trim((string) ($row['sumber'] ?? 'kelas'));
                $ayatDari = (int) ($row['ayat_dari'] ?? 0);
                $ayatSampai = (int) ($row['ayat_sampai'] ?? 0);
                $ayatLabel = trim((string) ($row['ayat_label'] ?? ''));
                if ($ayatLabel === '' && $ayatDari > 0 && $ayatSampai > 0) {
                    $ayatLabel = $ayatDari === $ayatSampai ? ('Ayat '.$ayatDari) : ('Ayat '.$ayatDari.'–'.$ayatSampai);
                } elseif ($ayatLabel === '' && $status === 'lunas') {
                    $ayatLabel = 'Lengkap';
                }
                $rows[] = [
                    $no++,
                    trim((string) ($item['nocust'] ?? '')),
                    trim((string) ($item['nmcust'] ?? '')),
                    trim((string) ($item['kelas'] ?? '')),
                    $i + 1,
                    (int) ($row['surah_nomor'] ?? 0),
                    trim((string) ($row['surah_nama'] ?? '')) ?: ('Surat '.(int) ($row['surah_nomor'] ?? 0)),
                    $ayatDari > 0 ? $ayatDari : '',
                    $ayatSampai > 0 ? $ayatSampai : '',
                    $ayatLabel,
                    trim((string) ($row['catatan'] ?? '')),
                    trim((string) ($row['setor_at'] ?? '')),
                    $sumber === 'percepatan' ? 'Percepatan' : 'Jadwal kelas',
                    trim((string) ($row['kelas'] ?? $item['kelas'] ?? '')),
                    tahfidLaporanStatusLabel($status),
                    $status === 'lunas' ? 'Ya' : 'Tidak',
                ];
            }
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                tahfidLaporanStyleSheet($event, 5, 16, [
                    1 => 6, 2 => 14, 3 => 28, 4 => 16, 5 => 8, 6 => 10,
                    7 => 22, 8 => 12, 9 => 12, 10 => 16, 11 => 32, 12 => 18,
                    13 => 14, 14 => 16, 15 => 12, 16 => 12,
                ], [15], [16]);
            },
        ];
    }
}

function tahfidLaporanGroupSurat(array $item): array
{
    $groups = ['jadwal' => [], 'lunas' => [], 'proses' => [], 'belum' => []];
    foreach (is_array($item['details'] ?? null) ? $item['details'] : [] as $row) {
        $nama = trim((string) ($row['surah_nama'] ?? ''));
        if ($nama === '') {
            $nama = 'Surat '.(int) ($row['surah_nomor'] ?? 0);
        }
        $groups['jadwal'][] = $nama;
        $status = strtolower(trim((string) ($row['status'] ?? 'belum')));
        if ($status === 'lunas') {
            $groups['lunas'][] = $nama;
        } elseif ($status === 'proses') {
            $groups['proses'][] = $nama;
        } else {
            $groups['belum'][] = $nama;
        }
    }

    return $groups;
}

function tahfidLaporanStatusLabel(string $status): string
{
    $status = strtolower(trim($status));

    return match ($status) {
        'lunas' => 'Lunas',
        'proses' => 'Proses',
        default => 'Belum',
    };
}

function tahfidLaporanStyleSheet(
    AfterSheet $event,
    int $headerRow,
    int $lastColIndex,
    array $widths = [],
    array $statusCols = [],
    array $yesNoCols = []
): void {
    $sheet = $event->sheet->getDelegate();
    $lastCol = Coordinate::stringFromColumnIndex($lastColIndex);
    $lastRow = max($headerRow, (int) $sheet->getHighestRow());

    $sheet->mergeCells('A1:'.$lastCol.'1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A2:A4')->getFont()->setBold(true);
    $sheet->getStyle('C2:C3')->getFont()->setBold(true);
    $sheet->getStyle('E2')->getFont()->setBold(true);
    $sheet->getStyle('A'.$headerRow.':'.$lastCol.$headerRow)->getFont()->setBold(true);
    $sheet->getStyle('A'.$headerRow.':'.$lastCol.$headerRow)->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setRGB('E4EFE6');
    if ($lastRow >= $headerRow) {
        $sheet->getStyle('A'.$headerRow.':'.$lastCol.$lastRow)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB('D5CFC0');
        $sheet->setAutoFilter('A'.$headerRow.':'.$lastCol.$lastRow);
    }
    $sheet->getStyle('A1:'.$lastCol.$lastRow)->getAlignment()
        ->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('A'.$headerRow.':'.$lastCol.$lastRow)->getAlignment()
        ->setWrapText(true);
    $sheet->getStyle('A'.$headerRow.':'.$lastCol.$headerRow)->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->freezePane('A'.($headerRow + 1));
    $sheet->getRowDimension(1)->setRowHeight(22);
    $sheet->getRowDimension($headerRow)->setRowHeight(22);

    foreach ($widths as $col => $width) {
        $sheet->getColumnDimensionByColumn($col)->setWidth($width);
        $sheet->getColumnDimensionByColumn($col)->setAutoSize(false);
    }

    for ($r = $headerRow + 1; $r <= $lastRow; $r++) {
        $sheet->getRowDimension($r)->setRowHeight(-1);
        foreach ($statusCols as $col) {
            tahfidLaporanFillByValue($sheet, Coordinate::stringFromColumnIndex($col).$r);
        }
        foreach ($yesNoCols as $col) {
            tahfidLaporanFillByValue($sheet, Coordinate::stringFromColumnIndex($col).$r);
        }
    }
}

function tahfidLaporanFillByValue($sheet, string $cell): void
{
    $val = strtolower(trim((string) $sheet->getCell($cell)->getValue()));
    $rgb = match ($val) {
        'lunas', 'ya' => 'DCEEE1',
        'proses' => 'F3E6C8',
        'belum', 'tidak' => 'EEEAE0',
        default => null,
    };
    if ($rgb === null) {
        return;
    }
    $sheet->getStyle($cell)->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setRGB($rgb);
}
