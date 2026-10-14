<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;

class KelolaMusyrifahController extends Controller
{
    private const SUPERADMIN_ROLES = ['admin', 'superadmin', 'super admin'];

    private const UNIT_SQL = "COALESCE(NULLIF(TRIM(sc.CODE02), ''), TRIM(ms.DESC01), TRIM(sc.CODE01))";

    public function musyrifah()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }

        $jumlahSiswa = DB::table('scctcust')
            ->whereRaw("TRIM(STCUST) = '1'")
            ->whereRaw("TRIM(COALESCE(musrifah, '')) <> ''")
            ->selectRaw('LOWER(TRIM(musrifah)) as musrifah, COUNT(*) as total')
            ->groupByRaw('LOWER(TRIM(musrifah))')
            ->pluck('total', 'musrifah');

        $entries = DB::table('sholat_user')
            ->select('idincrement', 'username', 'nama', 'role')
            ->orderByRaw("CASE WHEN LOWER(TRIM(role)) IN ('admin', 'superadmin') THEN 0 ELSE 1 END")
            ->orderBy('nama')
            ->get()
            ->map(function ($row) use ($jumlahSiswa) {
                $row->jumlah_siswa = (int) ($jumlahSiswa[mb_strtolower(trim((string) $row->username))] ?? 0);
                return $row;
            });

        return view('kelola_musyrifah.musyrifah', ['entries' => $entries]);
    }

    public function siswa(Request $request)
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }

        $search = trim((string) $request->query('search', ''));
        $musyrifahFilter = (string) $request->query('musyrifah', '');
        $unitFilter = trim((string) $request->query('unit', ''));

        $query = DB::table('scctcust as sc')
            ->leftJoin('mst_sekolah as ms', 'ms.CODE01', '=', 'sc.CODE01')
            ->whereRaw("TRIM(sc.STCUST) = '1'");

        if ($search !== '') {
            $like = '%' . mb_strtolower($search) . '%';
            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(sc.NMCUST) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(sc.NOCUST) LIKE ?', [$like]);
            });
        }
        if ($musyrifahFilter === '__none__') {
            $query->whereRaw("TRIM(COALESCE(sc.musrifah, '')) = ''");
        } elseif ($musyrifahFilter !== '') {
            $query->whereRaw('LOWER(TRIM(sc.musrifah)) = ?', [mb_strtolower($musyrifahFilter)]);
        }
        if ($unitFilter !== '') {
            $query->whereRaw(self::UNIT_SQL . ' = ?', [$unitFilter]);
        }

        $entries = $query
            ->orderBy('sc.NMCUST')
            ->selectRaw('
                sc.CUSTID as custid,
                TRIM(sc.NOCUST) as nocust,
                TRIM(sc.NMCUST) as nama,
                ' . self::UNIT_SQL . ' as unit,
                TRIM(sc.DESC02) as kelas,
                TRIM(sc.DESC03) as kelompok,
                TRIM(sc.musrifah) as musrifah
            ')
            ->paginate(50)
            ->withQueryString();

        $units = DB::table('scctcust as sc')
            ->leftJoin('mst_sekolah as ms', 'ms.CODE01', '=', 'sc.CODE01')
            ->whereRaw("TRIM(sc.STCUST) = '1'")
            ->selectRaw(self::UNIT_SQL . ' as unit')
            ->distinct()
            ->orderBy('unit')
            ->pluck('unit')
            ->filter()
            ->values();

        return view('kelola_musyrifah.siswa', [
            'entries' => $entries,
            'musyrifahList' => $this->musyrifahOptions(),
            'units' => $units,
            'search' => $search,
            'musyrifahFilter' => $musyrifahFilter,
            'unitFilter' => $unitFilter,
        ]);
    }

    public function updateSiswa(Request $request)
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }

        $validated = $request->validate([
            'custid' => ['required', 'integer'],
            'musrifah' => ['nullable', 'string', 'max:50'],
        ]);

        $musrifah = trim((string) ($validated['musrifah'] ?? ''));
        if ($musrifah !== '') {
            $username = DB::table('sholat_user')
                ->whereRaw('LOWER(TRIM(username)) = ?', [mb_strtolower($musrifah)])
                ->value('username');
            if ($username === null) {
                return back()->with('error', 'Musyrifah tidak ditemukan di data user.');
            }
            $musrifah = trim((string) $username);
        }

        $siswa = DB::table('scctcust')->where('CUSTID', $validated['custid'])->first(['NMCUST']);
        if (! $siswa) {
            return back()->with('error', 'Siswa tidak ditemukan.');
        }

        DB::table('scctcust')
            ->where('CUSTID', $validated['custid'])
            ->update(['musrifah' => $musrifah !== '' ? $musrifah : null]);

        Log::info('Kelola Musyrifah update siswa', [
            'by' => session('user.username'),
            'custid' => $validated['custid'],
            'musrifah' => $musrifah,
        ]);

        return back()->with('success', 'Musyrifah untuk ' . trim((string) $siswa->NMCUST) . ' berhasil disimpan.');
    }

    public function upload()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }

        return view('kelola_musyrifah.upload');
    }

    public function import(Request $request)
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            $sheets = Excel::toArray(new class {}, $request->file('file'));
        } catch (\Throwable $e) {
            Log::error('Kelola Musyrifah import read failed', ['message' => $e->getMessage()]);
            return back()->with('error', 'File Excel tidak dapat dibaca. Pastikan formatnya benar.');
        }

        $rows = $sheets[0] ?? [];
        [$headerIndex, $cols] = $this->detectColumns($rows);
        $dataRows = array_slice($rows, $headerIndex + 1);

        $byUsername = [];
        $byNama = [];
        foreach (DB::table('sholat_user')->get(['username', 'nama']) as $u) {
            $username = trim((string) $u->username);
            $byUsername[mb_strtolower($username)] = $username;
            $namaKey = mb_strtolower(trim((string) $u->nama));
            if ($namaKey !== '') {
                $byNama[$namaKey][] = $username;
            }
        }

        $result = ['updated' => 0, 'unchanged' => 0, 'errors' => []];

        DB::transaction(function () use ($dataRows, $cols, $headerIndex, $byUsername, $byNama, &$result) {
            foreach ($dataRows as $i => $row) {
                $line = $headerIndex + $i + 2;
                $nis = trim((string) ($row[$cols['nis']] ?? ''));
                $nama = trim((string) ($row[$cols['nama']] ?? ''));
                $musyrifahName = trim((string) ($row[$cols['musyrifah']] ?? ''));

                if ($nis === '' && $nama === '' && $musyrifahName === '') {
                    continue;
                }
                if ($nis === '') {
                    $result['errors'][] = "Baris {$line}: NIS kosong.";
                    continue;
                }
                if ($musyrifahName === '') {
                    $result['errors'][] = "Baris {$line} (NIS {$nis}): nama musyrifah kosong.";
                    continue;
                }

                $key = mb_strtolower($musyrifahName);
                $candidates = $byNama[$key] ?? [];
                if (count($candidates) > 1) {
                    $result['errors'][] = "Baris {$line} (NIS {$nis}): nama musyrifah \"{$musyrifahName}\" dipakai lebih dari satu user, isi dengan username.";
                    continue;
                }
                $username = $candidates[0] ?? ($byUsername[$key] ?? null);
                if ($username === null) {
                    $result['errors'][] = "Baris {$line} (NIS {$nis}): musyrifah \"{$musyrifahName}\" tidak ditemukan.";
                    continue;
                }

                $siswa = DB::table('scctcust')->whereRaw('TRIM(NOCUST) = ?', [$nis])->first(['CUSTID', 'musrifah']);
                if (! $siswa) {
                    $result['errors'][] = "Baris {$line}: NIS {$nis}" . ($nama !== '' ? " ({$nama})" : '') . ' tidak ditemukan.';
                    continue;
                }

                if (trim((string) $siswa->musrifah) === $username) {
                    $result['unchanged']++;
                    continue;
                }

                DB::table('scctcust')->whereRaw('TRIM(NOCUST) = ?', [$nis])->update(['musrifah' => $username]);
                $result['updated']++;
            }
        });

        Log::info('Kelola Musyrifah import', [
            'by' => session('user.username'),
            'updated' => $result['updated'],
            'unchanged' => $result['unchanged'],
            'errors' => count($result['errors']),
        ]);

        return back()->with('import_result', $result);
    }

    public function template()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }

        $export = new class implements FromArray {
            public function array(): array
            {
                return [
                    ['NIS', 'NAMA', 'NAMA MUSYRIFAH'],
                    ['1301154344', 'Farrelep Muallimaat', 'Musrifah'],
                ];
            }
        };

        return Excel::download($export, 'template_update_musyrifah.xlsx');
    }

    private function guard()
    {
        $role = strtolower(trim((string) session('user.role', '')));
        if (session('user.app') !== 'kelola-musyrifah' || ! in_array($role, self::SUPERADMIN_ROLES, true)) {
            return redirect()->route('admin.index');
        }

        return null;
    }

    private function musyrifahOptions()
    {
        return DB::table('sholat_user')
            ->whereRaw('LOWER(TRIM(role)) NOT IN (?, ?, ?)', self::SUPERADMIN_ROLES)
            ->orderBy('nama')
            ->get(['username', 'nama']);
    }

    /**
     * @return array{0: int, 1: array{nis: int, nama: int, musyrifah: int}}
     */
    private function detectColumns(array $rows): array
    {
        foreach (array_slice($rows, 0, 10, true) as $index => $row) {
            $cols = [];
            foreach ((array) $row as $c => $cell) {
                $label = strtoupper(preg_replace('/\s+/', ' ', trim((string) $cell)));
                if (in_array($label, ['NIS', 'NOCUST', 'NIS/NOCUST'], true)) {
                    $cols['nis'] = $c;
                } elseif (str_contains($label, 'MUSYRIFAH') || str_contains($label, 'MUSRIFAH')) {
                    $cols['musyrifah'] = $c;
                } elseif (in_array($label, ['NAMA', 'NAMA SISWA'], true)) {
                    $cols['nama'] = $c;
                }
            }
            if (isset($cols['nis'], $cols['musyrifah'])) {
                return [$index, $cols + ['nama' => 1]];
            }
        }

        return [-1, ['nis' => 0, 'nama' => 1, 'musyrifah' => 2]];
    }
}
