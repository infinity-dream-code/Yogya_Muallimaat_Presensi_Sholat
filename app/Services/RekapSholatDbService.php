<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RekapSholatDbService
{
    public function getCountsByBulan(string $bulan): array
    {
        [$tahun, $bulanNum] = explode('-', $bulan);

        $rows = DB::table('sholat_presensi_siswa')
            ->selectRaw("
                TRIM(NOCUST) as nocust,

                SUM(CASE WHEN isSHOLAT1 IN ('S','SH','SHOLAT','OK') THEN 1 ELSE 0 END) as subuh_sholat,
                SUM(CASE WHEN isSHOLAT2 IN ('S','SH','SHOLAT','OK') THEN 1 ELSE 0 END) as dzuhur_sholat,
                SUM(CASE WHEN isSHOLAT3 IN ('S','SH','SHOLAT','OK') THEN 1 ELSE 0 END) as ashar_sholat,
                SUM(CASE WHEN isSHOLAT4 IN ('S','SH','SHOLAT','OK') THEN 1 ELSE 0 END) as maghrib_sholat,
                SUM(CASE WHEN isSHOLAT5 IN ('S','SH','SHOLAT','OK') THEN 1 ELSE 0 END) as isya_sholat,

                SUM(CASE WHEN isSHOLAT1 IN ('S','SH','SHOLAT','OK') THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT2 IN ('S','SH','SHOLAT','OK') THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT3 IN ('S','SH','SHOLAT','OK') THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT4 IN ('S','SH','SHOLAT','OK') THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT5 IN ('S','SH','SHOLAT','OK') THEN 1 ELSE 0 END) as sholat,

                SUM(CASE WHEN isSHOLAT1 = 'I' THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT2 = 'I' THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT3 = 'I' THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT4 = 'I' THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT5 = 'I' THEN 1 ELSE 0 END) as izin,

                SUM(CASE WHEN isSHOLAT1 = 'A' THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT2 = 'A' THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT3 = 'A' THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT4 = 'A' THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT5 = 'A' THEN 1 ELSE 0 END) as alpa,

                SUM(CASE WHEN isSHOLAT1 = 'H' THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT2 = 'H' THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT3 = 'H' THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT4 = 'H' THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT5 = 'H' THEN 1 ELSE 0 END) as haid,

                SUM(CASE WHEN isSHOLAT1 = 'SK' THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT2 = 'SK' THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT3 = 'SK' THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT4 = 'SK' THEN 1 ELSE 0 END)
              + SUM(CASE WHEN isSHOLAT5 = 'SK' THEN 1 ELSE 0 END) as sakit
            ")
            ->whereYear('TRXDATE', (int) $tahun)
            ->whereMonth('TRXDATE', (int) $bulanNum)
            ->groupBy(DB::raw('TRIM(NOCUST)'))
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $key = trim((string) $row->nocust);

            $result[$key] = [
                'subuh_sholat'   => (int) $row->subuh_sholat,
                'dzuhur_sholat'  => (int) $row->dzuhur_sholat,
                'ashar_sholat'   => (int) $row->ashar_sholat,
                'maghrib_sholat' => (int) $row->maghrib_sholat,
                'isya_sholat'    => (int) $row->isya_sholat,
                'sholat'         => (int) $row->sholat,
                'izin'           => (int) $row->izin,
                'alpa'           => (int) $row->alpa,
                'haid'           => (int) $row->haid,
                'sakit'          => (int) $row->sakit,
            ];
        }

        return $result;
    }
}
