<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ApprovalPrestasiExport implements FromArray, WithEvents
{
    public function __construct(
        private array $items,
        private array $meta = []
    ) {}

    public function array(): array
    {
        $pad = fn (array $row) => array_pad($row, 17, '');
        $rows = [];
        $rows[] = $pad(['Laporan Approval Prestasi']);
        $rows[] = $pad([
            'Status',
            trim((string) ($this->meta['status_label'] ?? 'Semua')),
            'Cari',
            trim((string) ($this->meta['q'] ?? '')) ?: '-',
            'Periode',
            trim((string) ($this->meta['periode'] ?? 'Semua')),
        ]);
        $rows[] = $pad(['Jumlah', count($this->items).' data']);
        $rows[] = $pad([]);
        $rows[] = [
            'No', 'NIS', 'Nama', 'Kelas', 'Sekolah', 'Kategori', 'Tingkat', 'Capaian',
            'Poin', 'Tahun ajaran', 'Semester', 'Tgl. pelaksanaan', 'Diunggah', 'Status', 'Disetujui/ditolak oleh', 'Catatan admin', 'Bukti',
        ];

        $no = 1;
        foreach ($this->items as $item) {
            $raw = strtolower(trim((string) ($item['isapproved'] ?? 'pending')));
            $status = match ($raw) {
                '1', 'approve', 'approved' => 'Disetujui',
                'canceled', 'cancelled', 'tolak', 'rejected' => 'Ditolak',
                default => 'Pending',
            };
            $kategori = trim((string) ($item['kategori_nama'] ?? ''));
            $tingkat = trim((string) ($item['tingkat_nama'] ?? ''));
            $capaian = trim((string) ($item['poin_nama'] ?? ''));
            if ($kategori === '') {
                $parts = array_map('trim', explode(' — ', (string) ($item['jenis_prestasi'] ?? '')));
                $kategori = $parts[0] ?? '';
                $tingkat = $tingkat !== '' ? $tingkat : ($parts[1] ?? '');
                $capaian = $capaian !== '' ? $capaian : ($parts[2] ?? '');
            }
            $semester = (string) ($item['semester'] ?? '');
            $semesterLabel = $semester === '1' ? 'Ganjil' : ($semester === '2' ? 'Genap' : ($semester !== '' ? 'S'.$semester : ''));
            $created = trim((string) ($item['created_at'] ?? ''));
            $tglLomba = trim((string) ($item['tanggal_pelaksanaan'] ?? ''));
            $rows[] = [
                $no++,
                trim((string) ($item['nocust'] ?? '')),
                trim((string) ($item['nmcust'] ?? '')),
                trim((string) ($item['kelas'] ?? '')),
                trim((string) ($item['sekolah'] ?? '')),
                $kategori,
                $tingkat,
                $capaian,
                (float) ($item['nilai_penghargaan'] ?? 0),
                trim((string) ($item['bta'] ?? '')),
                $semesterLabel,
                $tglLomba,
                $created,
                $status,
                trim((string) ($item['approvedby'] ?? '')),
                trim((string) ($item['catatan_admin'] ?? '')),
                trim((string) ($item['url'] ?? '')),
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = 'Q';
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
                $widths = [6, 14, 28, 14, 18, 22, 16, 22, 10, 14, 12, 14, 18, 12, 18, 28, 28];
                foreach ($widths as $i => $w) {
                    $sheet->getColumnDimensionByColumn($i + 1)->setWidth($w);
                }
                for ($r = $headerRow + 1; $r <= $lastRow; $r++) {
                    $val = strtolower(trim((string) $sheet->getCell('N'.$r)->getValue()));
                    $rgb = match ($val) {
                        'disetujui' => 'DCEEE1',
                        'pending' => 'F3E6C8',
                        'ditolak' => 'F3D9D6',
                        default => null,
                    };
                    if ($rgb) {
                        $sheet->getStyle('N'.$r)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
                    }
                }
            },
        ];
    }
}
