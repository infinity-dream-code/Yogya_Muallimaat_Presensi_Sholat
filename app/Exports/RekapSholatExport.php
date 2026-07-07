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

class RekapSholatExport implements FromArray, WithDrawings, WithEvents
{
    protected array $waktus = [
        'subuh'   => 'Subuh',
        'dhuhur'  => 'Dzuhur',
        'ashar'   => 'Ashar',
        'maghrib' => 'Maghrib',
        'isya'    => 'Isya',
    ];

    protected array $statusKeys = ['sholat', 'sakit', 'izin', 'alpa', 'haid', 'belum_presensi'];

    protected array $statusMap = [
        'sholat'         => 'SHOLAT',
        'izin'           => 'IZIN',
        'alpa'           => 'ALPA',
        'haid'           => 'HAID',
        'sakit'          => 'SAKIT',
        'belum_presensi' => 'BELUM PRESENSI',
    ];

    protected array $jadwalIndex = [
        'subuh'   => 1,
        'dhuhur'  => 2,
        'ashar'   => 3,
        'maghrib' => 4,
        'isya'    => 5,
    ];

    public function __construct(
        protected array $entries,
        protected string $bulan
    ) {}

    protected function flattenTransactions(array $entries): array
    {
        $flat = [];
        foreach ($entries as $e) {
            if (!is_array($e)) continue;
            if (isset($e['datas']) && is_array($e['datas'])) {
                foreach ($e['datas'] as $s) {
                    if (is_array($s)) $flat[] = $s;
                }
            } elseif (isset($e['siswa']) && is_array($e['siswa'])) {
                foreach ($e['siswa'] as $s) {
                    if (is_array($s)) $flat[] = $s;
                }
            } elseif (isset($e['NamaCust']) || isset($e['NAMA']) || isset($e['NAMASISWA'])) {
                $flat[] = $e;
            }
        }
        return $flat;
    }

    protected function aggregateByStudent(array $transactions): array
    {
        $students = [];

        foreach ($transactions as $tx) {
            $nama = $tx['NamaCust'] ?? $tx['NAMA'] ?? $tx['NAMASISWA'] ?? $tx['Nama'] ?? null;
            $nis  = (string)($tx['NOCUST'] ?? $tx['nocust'] ?? $tx['NIS'] ?? $tx['NOKARTU'] ?? '');
            $unit = $tx['UNIT'] ?? $tx['Unit'] ?? $tx['unit'] ?? '';

            if ($nama === null) continue;

            $key = $nis !== '' ? $nis : $nama;

            if (!isset($students[$key])) {
                $students[$key] = ['nama' => $nama, 'nis' => $nis, 'unit' => $unit];
                foreach (array_keys($this->waktus) as $w) {
                    foreach ($this->statusKeys as $s) {
                        $students[$key][$w . '_' . $s] = 0;
                    }
                }
            }

            foreach ($this->jadwalIndex as $waktu => $idx) {
                $jadwal = strtoupper(trim($tx['JADWAL_' . $idx] ?? ''));
                foreach ($this->statusKeys as $status) {
                    if ($jadwal === $this->statusMap[$status]) {
                        $students[$key][$waktu . '_' . $status]++;
                    }
                }
            }
        }

        return array_values($students);
    }

    protected function buildHeader(): array
    {
        $header = [
            'No',
            'NIS',
            'Nama',
            'Unit',
            'Total Sholat',
            'Total Sakit',
            'Total Izin',
            'Total Alpa',
            'Total Haid',
            'Total Belum Presensi',
        ];

        $statusLabels = [
            'sholat'         => 'Sholat',
            'sakit'          => 'Sakit',
            'izin'           => 'Izin',
            'alpa'           => 'Alpa',
            'haid'           => 'Haid',
            'belum_presensi' => 'Belum Presensi',
        ];

        foreach ($this->waktus as $waktuLabel) {
            foreach ($this->statusKeys as $key) {
                $header[] = $waktuLabel . ' ' . $statusLabels[$key];
            }
        }

        return $header;
    }

    public function array(): array
    {
        $bulanText = $this->bulan;
        if (str_contains($this->bulan, '-')) {
            [$y, $m] = explode('-', $this->bulan);
            $bulanText = $m . '/' . $y;
        }

        // Data yang masuk dari controller sudah berupa rekap per-siswa per-bulan
        $students = $this->entries;

        $rows   = [];
        $rows[] = ['', 'Rekap Sholat Siswa - Bulan ' . $bulanText];
        $rows[] = $this->buildHeader();

        $no = 1;
        foreach ($students as $e) {
            if (!is_array($e)) {
                continue;
            }

            $nama = $e['NamaCust'] ?? $e['NAMA'] ?? $e['NAMASISWA'] ?? $e['Nama'] ?? '-';
            $nis  = (string)($e['NOCUST'] ?? $e['nocust'] ?? $e['NIS'] ?? $e['NOKARTU'] ?? '');
            $unit = $e['UNIT'] ?? $e['Unit'] ?? $e['unit'] ?? '';

            $row = [$no++, $nis, $nama, $unit];

            // Summary total_*: gunakan field total_xxx jika ada, fallback menjumlahkan per-waktu
            $summaryStatuses = ['sholat', 'sakit', 'izin', 'alpa', 'haid', 'belum_presensi'];
            foreach ($summaryStatuses as $status) {
                $totalKey1 = 'total_' . $status;
                $totalKey2 = strtoupper('total_' . $status);
                $val = null;
                if (array_key_exists($totalKey1, $e) && $e[$totalKey1] !== '' && $e[$totalKey1] !== null) {
                    $val = (int)$e[$totalKey1];
                } elseif (array_key_exists($totalKey2, $e) && $e[$totalKey2] !== '' && $e[$totalKey2] !== null) {
                    $val = (int)$e[$totalKey2];
                }

                if ($val === null) {
                    $val = 0;
                    foreach (array_keys($this->waktus) as $w) {
                        $k1 = $w . '_' . $status;
                        $k2 = strtoupper($w) . '_' . strtoupper(str_replace('_', '', $status));
                        if (array_key_exists($k1, $e) && is_numeric($e[$k1])) {
                            $val += (int)$e[$k1];
                        } elseif (array_key_exists($k2, $e) && is_numeric($e[$k2])) {
                            $val += (int)$e[$k2];
                        }
                    }
                }

                $row[] = $val;
            }

            // Detail per waktu (Subuh..Isya x statusKeys)
            foreach (array_keys($this->waktus) as $w) {
                foreach ($this->statusKeys as $s) {
                    $k1 = $w . '_' . $s;                           // subuh_alpa
                    $k2 = strtoupper($w) . '_' . strtoupper($s);  // SUBUH_ALPA
                    $val = 0;
                    if (array_key_exists($k1, $e) && is_numeric($e[$k1])) {
                        $val = (int)$e[$k1];
                    } elseif (array_key_exists($k2, $e) && is_numeric($e[$k2])) {
                        $val = (int)$e[$k2];
                    }
                    $row[] = $val;
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function drawings(): array
    {
        $logoPath = public_path('logo.png');
        if (!file_exists($logoPath)) return [];

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
                $sheet        = $event->sheet->getDelegate();
                $lastRow      = $sheet->getHighestRow();
                $lastCol      = $sheet->getHighestColumn();
                $headerRow    = 2;
                $dataStartRow = 3;

                $sheet->mergeCells('B1:' . $lastCol . '1');
                $bulanLabel = $this->bulan;
                if (str_contains($this->bulan, '-')) {
                    [$y, $m] = explode('-', $this->bulan);
                    $bulanLabel = $m . '/' . $y;
                }
                $sheet->setCellValue('B1', 'Rekap Sholat Siswa - Bulan ' . $bulanLabel);

                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(14);
                $sheet->getColumnDimension('C')->setWidth(26);
                $sheet->getColumnDimension('D')->setWidth(14);
                foreach (range('E', $lastCol) as $c) {
                    $sheet->getColumnDimension($c)->setWidth(8);
                }

                if ($lastRow >= $dataStartRow) {
                    $sheet->getStyle('B' . $dataStartRow . ':B' . $lastRow)
                        ->getNumberFormat()->setFormatCode('@');
                }

                $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
                ]);

                $headers  = $this->buildHeader();
                $colIndex = 1;
                foreach ($headers as $text) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->setCellValue($colLetter . $headerRow, $text);
                    $colIndex++;
                }

                $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF6D28D9']],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                if ($lastRow >= $dataStartRow) {
                    $sheet->getStyle('A' . $dataStartRow . ':' . $lastCol . $lastRow)->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
                        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    ]);
                    $sheet->getStyle('C' . $dataStartRow . ':C' . $lastRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                $sheet->getRowDimension(1)->setRowHeight(40);
                $sheet->getRowDimension($headerRow)->setRowHeight(24);
            },
        ];
    }
}
