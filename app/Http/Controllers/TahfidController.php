<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TahfidController extends Controller
{
    public function index(Request $request)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return $guard;
        }

        $isSuperadmin = $this->isSuperadmin();
        $code01 = $isSuperadmin
            ? trim((string) $request->query('code01', ''))
            : trim((string) session('user.code01', ''));
        $tingkat = strtoupper(trim((string) $request->query('tingkat', '')));
        $kelas = trim((string) $request->query('kelas', ''));
        $q = trim((string) $request->query('q', ''));

        $payload = $this->callWs('manageTahfid', array_filter([
            'action' => 'list',
            'code01' => $code01,
        ], fn ($v) => $v !== null && $v !== ''));

        if ($payload['error'] !== null) {
            return view('tahfid_jadwal', $this->staffViewData([
                'items' => [],
                'groups' => [],
                'sekolah' => [],
                'code01' => $code01,
                'tingkat' => $tingkat,
                'kelas' => $kelas,
                'q' => $q,
                'tingkatOptions' => [],
                'kelasOptions' => [],
                'errorMessage' => $payload['error'],
                'navActive' => 'jadwal',
            ]));
        }

        $items = $this->arrayItems($payload['data']['items'] ?? []);
        $tingkatOptions = $this->jadwalTingkatOptions($items);
        $kelasOptions = $this->jadwalKelasOptions($items, $tingkat);
        $filtered = $this->filterJadwalItems($items, $tingkat, $kelas, $q);

        return view('tahfid_jadwal', $this->staffViewData([
            'items' => $filtered,
            'groups' => $this->groupJadwalItems($filtered),
            'sekolah' => $this->arrayItems($payload['data']['sekolah'] ?? []),
            'code01' => $code01,
            'tingkat' => $tingkat,
            'kelas' => $kelas,
            'q' => $q,
            'tingkatOptions' => $tingkatOptions,
            'kelasOptions' => $kelasOptions,
            'errorMessage' => null,
            'navActive' => 'jadwal',
            'scopeSekolah' => trim((string) ($payload['data']['scope_sekolah'] ?? '')),
        ]));
    }

    public function create()
    {
        return $this->formView(null);
    }

    public function edit(int $id)
    {
        return $this->formView($id);
    }

    public function store(Request $request)
    {
        return $this->saveJadwal($request, false);
    }

    public function update(Request $request)
    {
        return $this->saveJadwal($request, true);
    }

    public function destroy(Request $request)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return $guard;
        }

        $validated = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $payload = $this->callWs('manageTahfid', [
            'action' => 'delete',
            'id' => (int) $validated['id'],
        ]);
        if ($payload['error'] !== null) {
            return back()->with('error', $payload['error']);
        }

        return redirect()->route('tahfid.jadwal.index')->with('success', (string) ($payload['data']['message'] ?? 'Jadwal dihapus.'));
    }

    public function kelas(Request $request)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return response()->json(['data' => [], 'error' => 'Akses ditolak'], 403);
        }

        $code01 = $this->isSuperadmin()
            ? trim((string) $request->query('code01', ''))
            : trim((string) session('user.code01', ''));
        if ($code01 === '') {
            return response()->json(['data' => []]);
        }

        $payload = $this->callWs('manageTahfid', [
            'action' => 'listkelas',
            'code01' => $code01,
        ]);
        if ($payload['error'] !== null) {
            return response()->json(['data' => [], 'error' => $payload['error']], 422);
        }

        $kelas = $this->mapKelasRows($payload['data']['kelas'] ?? []);

        return response()->json(['data' => $kelas]);
    }

    public function jadwalKelas(Request $request)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return response()->json(['data' => [], 'error' => 'Akses ditolak'], 403);
        }

        $code01 = $this->isSuperadmin()
            ? trim((string) $request->query('code01', ''))
            : trim((string) session('user.code01', ''));
        if ($code01 === '') {
            return response()->json(['data' => []]);
        }

        $payload = $this->callWs('manageTahfid', [
            'action' => 'list',
            'code01' => $code01,
        ]);
        if ($payload['error'] !== null) {
            return response()->json(['data' => [], 'error' => $payload['error']], 422);
        }

        $kelas = [];
        foreach ($this->arrayItems($payload['data']['items'] ?? []) as $row) {
            $val = trim((string) ($row['kelas'] ?? ''));
            if ($val !== '' && ! in_array($val, $kelas, true)) {
                $kelas[] = $val;
            }
        }

        return response()->json(['data' => $kelas]);
    }

    public function surat()
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return response()->json(['data' => [], 'error' => 'Akses ditolak'], 403);
        }

        return response()->json(['data' => $this->quranSuratList()]);
    }

    public function searchSiswa(Request $request)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return response()->json(['data' => [], 'error' => 'Akses ditolak'], 403);
        }

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $payload = $this->callWs('searchSiswa', ['q' => $q]);
        if ($payload['error'] !== null) {
            return response()->json(['data' => [], 'error' => $payload['error']], 422);
        }

        return response()->json(['data' => $this->arrayItems($payload['data']['items'] ?? [])]);
    }

    public function percepatan(Request $request)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return $guard;
        }

        $isSuperadmin = $this->isSuperadmin();
        $code01 = $isSuperadmin
            ? trim((string) $request->query('code01', ''))
            : trim((string) session('user.code01', ''));
        $q = trim((string) $request->query('q', ''));
        $kelas = trim((string) $request->query('kelas', ''));

        $payload = $this->callWs('manageTahfid', array_filter([
            'action' => 'listpercepatan',
            'code01' => $code01,
        ], fn ($v) => $v !== null && $v !== ''));

        if ($payload['error'] !== null) {
            return view('tahfid_percepatan', $this->staffViewData([
                'items' => [],
                'groups' => [],
                'sekolah' => [],
                'jadwalKelas' => [],
                'kelasOptions' => [],
                'code01' => $code01,
                'q' => $q,
                'kelas' => $kelas,
                'errorMessage' => $payload['error'],
                'navActive' => 'percepatan',
            ]));
        }

        $jadwalKelas = [];
        if (isset($payload['data']['jadwal_kelas']) && is_array($payload['data']['jadwal_kelas'])) {
            foreach ($payload['data']['jadwal_kelas'] as $row) {
                $val = is_string($row) ? trim($row) : '';
                if ($val !== '') {
                    $jadwalKelas[] = $val;
                }
            }
        }

        $items = $this->arrayItems($payload['data']['items'] ?? []);
        $kelasOptions = [];
        foreach ($items as $item) {
            $tujuan = trim((string) ($item['kelas_tujuan'] ?? ''));
            if ($tujuan !== '') {
                $kelasOptions[$tujuan] = $tujuan;
            }
        }
        natcasesort($kelasOptions);
        $kelasOptions = array_values($kelasOptions);

        $filtered = [];
        $qLower = mb_strtolower($q);
        foreach ($items as $item) {
            $tujuan = trim((string) ($item['kelas_tujuan'] ?? ''));
            if ($kelas !== '' && $tujuan !== $kelas) {
                continue;
            }
            if ($qLower !== '') {
                $hay = mb_strtolower(trim((string) ($item['nmcust'] ?? '')).' '.trim((string) ($item['nocust'] ?? '')));
                if (! str_contains($hay, $qLower)) {
                    continue;
                }
            }
            $filtered[] = $item;
        }

        return view('tahfid_percepatan', $this->staffViewData([
            'items' => $filtered,
            'groups' => $this->groupPercepatanItems($filtered),
            'sekolah' => $this->arrayItems($payload['data']['sekolah'] ?? []),
            'jadwalKelas' => $jadwalKelas,
            'kelasOptions' => $kelasOptions,
            'code01' => $code01,
            'q' => $q,
            'kelas' => $kelas,
            'errorMessage' => null,
            'navActive' => 'percepatan',
            'scopeSekolah' => trim((string) ($payload['data']['scope_sekolah'] ?? '')),
        ]));
    }

    public function storePercepatan(Request $request)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return $guard;
        }

        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:50'],
            'kelas_tujuan' => ['required', 'string', 'max:80'],
        ]);

        $payload = $this->callWs('manageTahfid', [
            'action' => 'savepercepatan',
            'nis' => trim($validated['nis']),
            'kelas_tujuan' => trim($validated['kelas_tujuan']),
        ]);
        if ($payload['error'] !== null) {
            return back()->withInput()->with('error', $payload['error']);
        }

        return redirect()->route('tahfid.percepatan.index')->with('success', (string) ($payload['data']['message'] ?? 'Percepatan tersimpan.'));
    }

    public function destroyPercepatan(Request $request)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return $guard;
        }

        $validated = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $payload = $this->callWs('manageTahfid', [
            'action' => 'deletepercepatan',
            'id' => (int) $validated['id'],
        ]);
        if ($payload['error'] !== null) {
            return back()->with('error', $payload['error']);
        }

        return redirect()->route('tahfid.percepatan.index')->with('success', (string) ($payload['data']['message'] ?? 'Percepatan dihapus.'));
    }

    public function siswa()
    {
        $guard = $this->guardSiswa();
        if ($guard !== null) {
            return $guard;
        }

        $payload = $this->callWs('manageTahfid', ['action' => 'myjadwal']);
        $siswa = is_array($payload['data']['siswa'] ?? null) ? $payload['data']['siswa'] : [];
        $groups = $this->arrayItems($payload['data']['groups'] ?? []);

        return view('tahfid_siswa', [
            'siswa' => $siswa,
            'groups' => $groups,
            'errorMessage' => $payload['error'],
            'isSuperadmin' => false,
            'navActive' => 'siswa',
            'scopeCode01' => trim((string) ($siswa['code01'] ?? session('user.code01', ''))),
            'scopeSekolah' => trim((string) ($siswa['sekolah'] ?? '')),
        ]);
    }

    public function setor(Request $request)
    {
        $guard = $this->guardSiswa();
        if ($guard !== null) {
            return $guard;
        }

        $validated = $request->validate([
            'jadwal_detail_id' => ['required', 'integer', 'min:1'],
        ]);

        $payload = $this->callWs('manageTahfid', [
            'action' => 'submitsetoran',
            'jadwal_detail_id' => (int) $validated['jadwal_detail_id'],
        ]);
        if ($payload['error'] !== null) {
            return back()->with('error', $payload['error']);
        }

        return redirect()->route('tahfid.siswa.index')->with('success', (string) ($payload['data']['message'] ?? 'Setoran tercatat.'));
    }

    public function progress(Request $request)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return $guard;
        }

        $nis = trim((string) $request->query('nis', ''));
        $siswa = null;
        $groups = [];
        $ringkasan = ['jumlah_surat' => 0, 'jumlah_lunas' => 0];
        $errorMessage = null;

        if ($nis !== '') {
            $payload = $this->callWs('manageTahfid', [
                'action' => 'siswaprogress',
                'nis' => $nis,
            ]);
            if ($payload['error'] !== null) {
                $errorMessage = $payload['error'];
            } else {
                $siswa = is_array($payload['data']['siswa'] ?? null) ? $payload['data']['siswa'] : null;
                $groups = $this->arrayItems($payload['data']['groups'] ?? []);
                $ringkasan = is_array($payload['data']['ringkasan'] ?? null) ? $payload['data']['ringkasan'] : $ringkasan;
            }
        }

        return view('tahfid_progress', $this->staffViewData([
            'nis' => $nis,
            'siswa' => $siswa,
            'groups' => $groups,
            'ringkasan' => $ringkasan,
            'errorMessage' => $errorMessage,
            'navActive' => 'progress',
        ]));
    }

    public function saveProgress(Request $request)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return $guard;
        }

        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:50'],
            'jadwal_detail_id' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:proses,lunas'],
            'ayat_dari' => ['nullable', 'integer', 'min:1'],
            'ayat_sampai' => ['nullable', 'integer', 'min:1'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $fields = [
            'action' => 'saveprogress',
            'nis' => trim($validated['nis']),
            'jadwal_detail_id' => (int) $validated['jadwal_detail_id'],
            'status' => $validated['status'],
            'catatan' => trim((string) ($validated['catatan'] ?? '')),
        ];
        if ($validated['status'] === 'proses') {
            $dari = (int) ($validated['ayat_dari'] ?? 0);
            $sampai = (int) ($validated['ayat_sampai'] ?? 0);
            if ($dari < 1 || $sampai < $dari) {
                return back()->withInput()->with('error', 'Isi rentang ayat yang dicatat.');
            }
            $fields['ayat_dari'] = $dari;
            $fields['ayat_sampai'] = $sampai;
        } else {
            $fields['is_lengkap'] = 1;
        }

        $payload = $this->callWs('manageTahfid', $fields);
        if ($payload['error'] !== null) {
            return back()->withInput()->with('error', $payload['error']);
        }

        return redirect()
            ->route('tahfid.progress.index', [
                'nis' => trim($validated['nis']),
                'open' => (int) $validated['jadwal_detail_id'],
            ])
            ->with('success', (string) ($payload['data']['message'] ?? 'Progress tercatat.'));
    }

    private function formView(?int $id)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return $guard;
        }

        $item = null;
        $sekolah = [];
        $kelas = [];
        $scopeSekolah = '';

        if ($id !== null) {
            $payload = $this->callWs('manageTahfid', [
                'action' => 'get',
                'id' => $id,
            ]);
            if ($payload['error'] !== null) {
                return redirect()->route('tahfid.jadwal.index')->with('error', $payload['error']);
            }
            $item = is_array($payload['data']['item'] ?? null) ? $payload['data']['item'] : null;
            if ($item === null) {
                return redirect()->route('tahfid.jadwal.index')->with('error', 'Jadwal tidak ditemukan.');
            }
            $sekolah = $this->arrayItems($payload['data']['sekolah'] ?? []);
            $kelas = $this->mapKelasRows($payload['data']['kelas'] ?? []);
            $scopeSekolah = trim((string) ($payload['data']['scope_sekolah'] ?? ''));
        } else {
            $payload = $this->callWs('manageTahfid', ['action' => 'list']);
            if ($payload['error'] !== null) {
                return redirect()->route('tahfid.jadwal.index')->with('error', $payload['error']);
            }
            $sekolah = $this->arrayItems($payload['data']['sekolah'] ?? []);
            $scopeSekolah = trim((string) ($payload['data']['scope_sekolah'] ?? ''));
        }

        return view('tahfid_jadwal_form', $this->staffViewData([
            'item' => $item,
            'sekolah' => $sekolah,
            'kelasOptions' => $kelas,
            'surat' => $this->quranSuratList(),
            'navActive' => $item ? 'jadwal' : 'jadwal-tambah',
            'scopeSekolah' => $scopeSekolah,
            'errorMessage' => null,
        ]));
    }

    private function saveJadwal(Request $request, bool $needId)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return $guard;
        }

        $rules = [
            'kelas_id' => ['required', 'array', 'min:1'],
            'kelas_id.*' => ['required', 'integer', 'min:1'],
            'details_json' => ['required', 'string'],
        ];
        if ($this->isSuperadmin()) {
            $rules['code01'] = ['required', 'string', 'max:20'];
        }
        $validated = $request->validate($rules);

        $decoded = json_decode((string) $validated['details_json'], true);
        if (! is_array($decoded) || count($decoded) === 0) {
            return back()->withInput()->with('error', 'Tambahkan minimal satu surat pada jadwal.');
        }

        $kelasId = [];
        foreach ($validated['kelas_id'] as $row) {
            $id = (int) $row;
            if ($id > 0) {
                $kelasId[$id] = $id;
            }
        }
        $kelasId = array_values($kelasId);
        if (count($kelasId) === 0) {
            return back()->withInput()->with('error', 'Pilih minimal satu kelas.');
        }

        $fields = [
            'action' => 'save',
            'kelas_id' => $kelasId,
            'details' => json_encode(array_values($decoded), JSON_UNESCAPED_UNICODE),
        ];
        if ($this->isSuperadmin()) {
            $fields['code01'] = trim($validated['code01']);
        }

        $payload = $this->callWs('manageTahfid', $fields);
        if ($payload['error'] !== null) {
            return back()->withInput()->with('error', $payload['error']);
        }

        return redirect()->route('tahfid.jadwal.index')->with('success', (string) ($payload['data']['message'] ?? 'Jadwal tersimpan.'));
    }

    private function builtInSurat(): array
    {
        static $rows = null;
        if ($rows === null) {
            $loaded = require app_path('Data/quran_surat.php');
            $rows = is_array($loaded) ? $loaded : [];
        }

        return $rows;
    }

    private function quranSuratList(): array
    {
        return $this->normalizeSuratList($this->builtInSurat());
    }

    private function normalizeSuratList(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $nomor = (int) ($row['nomor'] ?? 0);
            if ($nomor < 1) {
                continue;
            }
            $items[] = [
                'nomor' => $nomor,
                'nama' => trim((string) ($row['nama'] ?? '')),
                'namaLatin' => trim((string) ($row['namaLatin'] ?? $row['nama_latin'] ?? '')),
                'jumlahAyat' => (int) ($row['jumlahAyat'] ?? $row['jumlah_ayat'] ?? 0),
                'arti' => trim((string) ($row['arti'] ?? '')),
            ];
        }
        usort($items, fn ($a, $b) => $a['nomor'] <=> $b['nomor']);

        return $items;
    }

    private function mapKelasRows(mixed $raw): array
    {
        $items = [];
        if (! is_array($raw)) {
            return $items;
        }
        foreach ($raw as $row) {
            if (is_string($row)) {
                $nama = trim($row);
                if ($nama !== '') {
                    $items[] = ['id' => 0, 'code03' => '', 'kelas' => $nama, 'rombel' => ''];
                }
                continue;
            }
            if (! is_array($row)) {
                continue;
            }
            $id = (int) ($row['id'] ?? 0);
            $code03 = trim((string) ($row['code03'] ?? ''));
            if ($code03 === '' && $id > 0) {
                $code03 = (string) $id;
            }
            $nama = trim((string) ($row['kelas'] ?? ''));
            if ($nama === '') {
                continue;
            }
            $items[] = [
                'id' => $id,
                'code03' => $code03,
                'kelas' => $nama,
                'rombel' => trim((string) ($row['rombel'] ?? '')),
            ];
        }

        return $items;
    }

    private function tingkatOf(string $kelas): string
    {
        $kelas = trim($kelas);
        if ($kelas === '') {
            return 'Lainnya';
        }
        if (preg_match('/^(XII|XI|IX|X|VIII|VII|VI|IV|V|III|II|I)(?=\s|$|[^A-Za-z])/i', $kelas, $m)) {
            return strtoupper($m[1]);
        }

        return 'Lainnya';
    }

    private function tingkatRank(string $tingkat): int
    {
        $order = [
            'I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6,
            'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10, 'XI' => 11, 'XII' => 12,
        ];

        return $order[$tingkat] ?? 90;
    }

    private function jadwalTingkatOptions(array $items): array
    {
        $map = [];
        foreach ($items as $item) {
            $tingkat = $this->tingkatOf((string) ($item['kelas'] ?? ''));
            $map[$tingkat] = ($map[$tingkat] ?? 0) + 1;
        }
        uksort($map, fn ($a, $b) => $this->tingkatRank((string) $a) <=> $this->tingkatRank((string) $b) ?: strcmp((string) $a, (string) $b));

        return $map;
    }

    private function jadwalKelasOptions(array $items, string $tingkat): array
    {
        $names = [];
        foreach ($items as $item) {
            $kelas = trim((string) ($item['kelas'] ?? ''));
            if ($kelas === '') {
                continue;
            }
            if ($tingkat !== '' && $this->tingkatOf($kelas) !== $tingkat) {
                continue;
            }
            $names[$kelas] = $kelas;
        }
        natcasesort($names);

        return array_values($names);
    }

    private function filterJadwalItems(array $items, string $tingkat, string $kelas, string $q): array
    {
        $qLower = mb_strtolower($q);
        $filtered = [];
        foreach ($items as $item) {
            $namaKelas = trim((string) ($item['kelas'] ?? ''));
            $itemTingkat = $this->tingkatOf($namaKelas);
            if ($tingkat !== '' && $itemTingkat !== $tingkat) {
                continue;
            }
            if ($kelas !== '' && $namaKelas !== $kelas) {
                continue;
            }
            if ($qLower !== '') {
                $hay = mb_strtolower($namaKelas.' '.trim((string) ($item['sekolah'] ?? '')));
                foreach (is_array($item['details'] ?? null) ? $item['details'] : [] as $detail) {
                    $hay .= ' '.mb_strtolower((string) ($detail['surah_nama'] ?? ''));
                }
                if (! str_contains($hay, $qLower)) {
                    continue;
                }
            }
            $item['_tingkat'] = $itemTingkat;
            $filtered[] = $item;
        }

        return $filtered;
    }

    private function groupJadwalItems(array $items): array
    {
        $units = [];
        foreach ($items as $item) {
            $code01 = trim((string) ($item['code01'] ?? ''));
            $key = $code01 !== '' ? $code01 : '_';
            if (! isset($units[$key])) {
                $units[$key] = [
                    'code01' => $code01,
                    'sekolah' => trim((string) ($item['sekolah'] ?? $code01)),
                    'count' => 0,
                    'tingkat' => [],
                ];
            }
            $tingkat = (string) ($item['_tingkat'] ?? $this->tingkatOf((string) ($item['kelas'] ?? '')));
            if (! isset($units[$key]['tingkat'][$tingkat])) {
                $units[$key]['tingkat'][$tingkat] = [
                    'tingkat' => $tingkat,
                    'count' => 0,
                    'items' => [],
                ];
            }
            $units[$key]['count']++;
            $units[$key]['tingkat'][$tingkat]['count']++;
            $units[$key]['tingkat'][$tingkat]['items'][] = $item;
        }

        foreach ($units as &$unit) {
            uksort($unit['tingkat'], fn ($a, $b) => $this->tingkatRank((string) $a) <=> $this->tingkatRank((string) $b) ?: strcmp((string) $a, (string) $b));
            $unit['tingkat'] = array_values($unit['tingkat']);
        }
        unset($unit);

        return array_values($units);
    }

    private function groupPercepatanItems(array $items): array
    {
        $units = [];
        foreach ($items as $item) {
            $code01 = trim((string) ($item['code01'] ?? ''));
            $key = $code01 !== '' ? $code01 : '_';
            if (! isset($units[$key])) {
                $units[$key] = [
                    'code01' => $code01,
                    'sekolah' => trim((string) ($item['sekolah'] ?? $code01)),
                    'count' => 0,
                    'items' => [],
                ];
            }
            $units[$key]['count']++;
            $units[$key]['items'][] = $item;
        }

        return array_values($units);
    }

    private function staffViewData(array $extra): array
    {
        $isSuperadmin = $this->isSuperadmin();

        return array_merge([
            'isSuperadmin' => $isSuperadmin,
            'scopeCode01' => $isSuperadmin ? '' : trim((string) session('user.code01', '')),
            'scopeSekolah' => '',
        ], $extra);
    }

    private function arrayItems(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $items = [];
        foreach ($raw as $row) {
            if (is_array($row)) {
                $items[] = $row;
            }
        }

        return $items;
    }

    private function isSuperadmin(): bool
    {
        return strtolower(trim((string) session('user.role', ''))) === 'superadmin';
    }

    private function guardStaff()
    {
        if (session('user.app') !== 'tahfid') {
            return redirect()->route('login.form');
        }
        if (strtolower((string) session('user.role', '')) === 'siswa') {
            return redirect()->route('tahfid.siswa.index');
        }

        return null;
    }

    private function guardSiswa()
    {
        if (session('user.app') !== 'tahfid') {
            return redirect()->route('login.form');
        }
        if (strtolower((string) session('user.role', '')) !== 'siswa') {
            return redirect()->route('tahfid.jadwal.index');
        }

        return null;
    }

    private function callWs(string $method, array $fields): array
    {
        $token = trim((string) session('user.approval_token', ''));
        if ($token === '') {
            return ['error' => 'Sesi berakhir. Silakan login ulang.', 'data' => []];
        }

        $wsUrl = rtrim((string) env('APPROVAL_WS_URL', 'http://103.23.103.43/ws_client/mualimat_reward/index.php'), '/');
        $request = array_merge([
            'method' => $method,
            'token' => $token,
        ], $fields);

        $logRequest = $request;
        unset($logRequest['token']);
        Log::info('Tahfid WS request', [
            'url' => $wsUrl,
            'request' => $logRequest,
            'username' => session('user.username'),
        ]);

        try {
            $response = Http::timeout(25)->post($wsUrl, $request);
        } catch (\Throwable $e) {
            Log::error('Tahfid WS exception', [
                'url' => $wsUrl,
                'message' => $e->getMessage(),
            ]);

            return ['error' => 'Tidak dapat terhubung ke server.', 'data' => []];
        }

        $json = $response->json();
        if (! $response->ok() || ! is_array($json) || (int) ($json['status'] ?? 500) !== 200) {
            $message = is_array($json) ? (string) ($json['message'] ?? 'Gagal memproses tahfid.') : 'Gagal memproses tahfid.';

            return ['error' => $message, 'data' => []];
        }

        $data = isset($json['data']) && is_array($json['data']) ? $json['data'] : [];

        return ['error' => null, 'data' => $data];
    }
}
