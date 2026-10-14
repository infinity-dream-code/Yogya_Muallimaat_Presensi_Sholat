<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TahfidProgressTemplateExport implements WithMultipleSheets
{
    public function __construct(
        private array $rows,
        private array $meta = []
    ) {}

    public function sheets(): array
    {
        return [
            new TahfidProgressTemplateDataSheet($this->rows, $this->meta),
            new TahfidProgressTemplateHintSheet(),
        ];
    }
}

class TahfidProgressTemplateDataSheet implements FromArray, WithTitle, WithEvents
{
    public function __construct(
        private array $rows,
        private array $meta
    ) {}

    public function title(): string
    {
        return 'Setoran';
    }

    public function array(): array
    {
        $out = [];
        $out[] = ['Template Setoran Tahfid', '', '', '', '', '', '', '', '', '', '', ''];
        $out[] = [
            'Mode',
            trim((string) ($this->meta['mode'] ?? 'siswi')),
            'Kelas / Siswi',
            trim((string) ($this->meta['label'] ?? '-')),
            '', '', '', '', '', '', '', '',
        ];
        $out[] = ['Isi kolom Status baru (proses/lunas). Kosong = dilewati. Jangan ubah kolom jadwal_detail_id.', '', '', '', '', '', '', '', '', '', '', ''];
        $out[] = ['', '', '', '', '', '', '', '', '', '', '', ''];
        $out[] = [
            'NIS', 'Nama', 'Kelas', 'No surat', 'Nama surat', 'Asal',
            'Status saat ini', 'Status baru', 'Ayat dari', 'Ayat sampai', 'Catatan', 'jadwal_detail_id',
        ];
        foreach ($this->rows as $row) {
            $out[] = [
                trim((string) ($row['nis'] ?? '')),
                trim((string) ($row['nama'] ?? '')),
                trim((string) ($row['kelas'] ?? '')),
                (int) ($row['surah_nomor'] ?? 0),
                trim((string) ($row['surah_nama'] ?? '')),
                trim((string) ($row['asal'] ?? 'Kelas')),
                trim((string) ($row['status_saat_ini'] ?? 'belum')),
                '',
                '',
                '',
                '',
                (int) ($row['jadwal_detail_id'] ?? 0),
            ];
        }

        return $out;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headerRow = 5;
                $lastRow = max($headerRow, (int) $sheet->getHighestRow());
                $sheet->mergeCells('A1:L1');
                $sheet->mergeCells('A3:L3');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A5:L5')->getFont()->setBold(true);
                $sheet->getStyle('A5:L5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E4EFE6');
                $sheet->getStyle('A5:L'.$lastRow)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D5CFC0');
                $sheet->freezePane('A6');
                $sheet->setAutoFilter('A5:L'.$lastRow);
                $widths = [14, 28, 14, 10, 22, 14, 16, 14, 12, 12, 32, 16];
                foreach ($widths as $i => $w) {
                    $sheet->getColumnDimensionByColumn($i + 1)->setWidth($w);
                }
                $sheet->getColumnDimension('L')->setVisible(true);
                if ($lastRow > $headerRow) {
                    $validation = $sheet->getCell('H6')->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('"proses,lunas"');
                    $sheet->setDataValidation('H6:H'.$lastRow, $validation);
                }
                $sheet->getStyle('A1:L'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}

class TahfidProgressTemplateHintSheet implements FromArray, WithTitle
{
    public function title(): string
    {
        return 'Petunjuk';
    }

    public function array(): array
    {
        return [
            ['Cara isi template setoran tahfid'],
            [''],
            ['1', 'Jangan ubah kolom NIS, Nama, Kelas, surat, Status saat ini, dan jadwal_detail_id.'],
            ['2', 'Isi Status baru: proses atau lunas. Kosongkan jika baris itu tidak diubah.'],
            ['3', 'Jika proses, isi Ayat dari dan Ayat sampai sesuai target jadwal surat.'],
            ['4', 'Jika lunas, ayat boleh dikosongkan (dianggap lengkap).'],
            ['5', 'Catatan opsional, maksimal 500 karakter.'],
            ['6', 'Unggah file ini di halaman Progress siswi. Maksimal 500 baris per unggah.'],
            [''],
            ['Contoh', 'Status baru = proses, Ayat dari = 1, Ayat sampai = 10, Catatan = setoran halaman 1'],
        ];
    }
}
