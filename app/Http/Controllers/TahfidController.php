<?php

namespace App\Http\Controllers;

use App\Exports\TahfidLaporanExport;
use App\Exports\TahfidProgressTemplateExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

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
        $isSuperadmin = $this->isSuperadmin();
        $code01 = $isSuperadmin
            ? trim((string) $request->query('code01', ''))
            : trim((string) session('user.code01', ''));
        $kelasTemplate = trim((string) $request->query('kelas', ''));
        $sekolah = [];
        $kelasOptions = [];

        if ($code01 !== '') {
            $kelasPayload = $this->callWs('manageTahfid', [
                'action' => 'listkelas',
                'code01' => $code01,
            ]);
            if ($kelasPayload['error'] === null) {
                foreach ($this->mapKelasRows($kelasPayload['data']['kelas'] ?? []) as $row) {
                    $nama = trim((string) ($row['kelas'] ?? ''));
                    if ($nama !== '') {
                        $kelasOptions[$nama] = $nama;
                    }
                }
                natcasesort($kelasOptions);
                $kelasOptions = array_values($kelasOptions);
            }
        }
        if ($isSuperadmin) {
            $listPayload = $this->callWs('manageTahfid', ['action' => 'list']);
            if ($listPayload['error'] === null) {
                $sekolah = $this->arrayItems($listPayload['data']['sekolah'] ?? []);
            }
        }

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
            'code01' => $code01,
            'kelasTemplate' => $kelasTemplate,
            'sekolah' => $sekolah,
            'kelasOptions' => $kelasOptions,
            'importPreview' => session('tahfid_import_preview'),
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
            'status' => ['required', 'in:proses,lunas,tuntas'],
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

    public function exportProgressTemplate(Request $request)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return $guard;
        }

        $nis = trim((string) $request->query('nis', ''));
        $kelas = trim((string) $request->query('kelas', ''));
        $code01 = $this->isSuperadmin()
            ? trim((string) $request->query('code01', ''))
            : trim((string) session('user.code01', ''));

        $fields = array_filter([
            'action' => 'templateprogress',
            'nis' => $nis,
            'kelas' => $kelas,
            'code01' => $code01,
        ], fn ($v) => $v !== null && $v !== '');
        $payload = $this->callWs('manageTahfid', $fields);
        if ($payload['error'] !== null) {
            return redirect()->route('tahfid.progress.index', array_filter(['nis' => $nis]))
                ->with('error', $payload['error']);
        }

        $rows = $this->arrayItems($payload['data']['rows'] ?? []);
        if (count($rows) === 0) {
            return redirect()->route('tahfid.progress.index', array_filter(['nis' => $nis]))
                ->with('error', 'Tidak ada jadwal untuk template ini.');
        }

        $mode = trim((string) ($payload['data']['mode'] ?? ($nis !== '' ? 'siswi' : 'kelas')));
        $label = $mode === 'siswi'
            ? trim((string) (($payload['data']['siswa']['nmcust'] ?? '').' / '.($payload['data']['siswa']['nocust'] ?? $nis)))
            : trim((string) ($payload['data']['kelas'] ?? $kelas));
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', $label) ?: $mode;
        $filename = 'template_setoran_tahfid_'.$safe.'_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new TahfidProgressTemplateExport($rows, [
            'mode' => $mode,
            'label' => $label,
        ]), $filename);
    }

    public function importProgress(Request $request)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return $guard;
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
            'nis' => ['nullable', 'string', 'max:50'],
        ]);
        $nis = trim((string) ($validated['nis'] ?? ''));

        try {
            $sheets = Excel::toArray([], $request->file('file'));
        } catch (\Throwable $e) {
            return redirect()->route('tahfid.progress.index', array_filter(['nis' => $nis]))
                ->with('error', 'File Excel tidak dapat dibaca.');
        }

        $table = [];
        foreach ($sheets as $sheet) {
            if (! is_array($sheet) || count($sheet) < 2) {
                continue;
            }
            $headerIndex = $this->findProgressTemplateHeader($sheet);
            if ($headerIndex === null) {
                continue;
            }
            $map = $this->mapProgressTemplateHeader($sheet[$headerIndex]);
            if (! isset($map['nis'], $map['jadwal_detail_id'])) {
                continue;
            }
            for ($i = $headerIndex + 1; $i < count($sheet); $i++) {
                $line = $sheet[$i] ?? [];
                if (! is_array($line)) {
                    continue;
                }
                $rowNis = trim((string) ($line[$map['nis']] ?? ''));
                $detailId = (int) ($line[$map['jadwal_detail_id']] ?? 0);
                if ($rowNis === '' && $detailId < 1) {
                    continue;
                }
                $table[] = [
                    'nis' => $rowNis,
                    'nama' => trim((string) ($line[$map['nama'] ?? -1] ?? '')),
                    'surah_nama' => trim((string) ($line[$map['surah_nama'] ?? -1] ?? '')),
                    'jadwal_detail_id' => $detailId,
                    'kelas' => trim((string) ($line[$map['kelas'] ?? -1] ?? '')),
                    'status_saat_ini' => strtolower(trim((string) ($line[$map['status_saat_ini'] ?? -1] ?? ''))),
                    'status' => strtolower(trim((string) ($line[$map['status_baru'] ?? -1] ?? ''))),
                    'ayat_dari' => (int) ($line[$map['ayat_dari'] ?? -1] ?? 0),
                    'ayat_sampai' => (int) ($line[$map['ayat_sampai'] ?? -1] ?? 0),
                    'catatan' => trim((string) ($line[$map['catatan'] ?? -1] ?? '')),
                ];
            }
            break;
        }

        if (count($table) === 0) {
            return redirect()->route('tahfid.progress.index', array_filter(['nis' => $nis]))
                ->with('error', 'Template tidak dikenali atau tidak ada baris data.');
        }

        $preview = $this->buildProgressImportPreview($table);
        session([
            'tahfid_import_preview' => [
                'token' => bin2hex(random_bytes(8)),
                'nis' => $nis,
                'filename' => $request->file('file')->getClientOriginalName(),
                'rows' => $table,
                'updates' => $preview['updates'],
                'skipped' => $preview['skipped'],
                'invalid' => $preview['invalid'],
            ],
        ]);

        return redirect()->route('tahfid.progress.index', array_filter(['nis' => $nis]));
    }

    public function confirmImportProgress(Request $request)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return $guard;
        }

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'nis' => ['nullable', 'string', 'max:50'],
        ]);
        $nis = trim((string) ($validated['nis'] ?? ''));
        $preview = session('tahfid_import_preview');
        if (! is_array($preview) || ($preview['token'] ?? '') !== $validated['token']) {
            return redirect()->route('tahfid.progress.index', array_filter(['nis' => $nis]))
                ->with('error', 'Pratinjau sudah kedaluwarsa. Unggah ulang file Excel.');
        }

        $table = $this->arrayItems($preview['rows'] ?? []);
        session()->forget('tahfid_import_preview');
        if (count($table) === 0) {
            return redirect()->route('tahfid.progress.index', array_filter(['nis' => $nis]))
                ->with('error', 'Tidak ada baris untuk diproses.');
        }

        $chunks = array_chunk($table, 500);
        $ok = 0;
        $skip = 0;
        $fail = 0;
        $errors = [];
        foreach ($chunks as $chunk) {
            $payload = $this->callWs('manageTahfid', [
                'action' => 'importprogress',
                'rows' => $chunk,
            ], 60);
            if ($payload['error'] !== null) {
                return redirect()->route('tahfid.progress.index', array_filter(['nis' => $nis]))
                    ->with('error', $payload['error']);
            }
            $ok += (int) ($payload['data']['ok'] ?? 0);
            $skip += (int) ($payload['data']['skip'] ?? 0);
            $fail += (int) ($payload['data']['fail'] ?? 0);
            foreach ($this->arrayItems($payload['data']['errors'] ?? []) as $err) {
                $errors[] = $err;
            }
        }

        $msg = $ok.' tersimpan, '.$skip.' dilewati, '.$fail.' gagal.';
        if (count($errors) > 0) {
            $first = [];
            foreach (array_slice($errors, 0, 5) as $err) {
                $alasan = trim((string) ($err['alasan'] ?? ''));
                $baris = (int) ($err['baris'] ?? 0);
                $first[] = ($baris > 0 ? 'Baris '.$baris.': ' : '').$alasan;
            }
            $msg .= ' '.implode(' ', $first);
        }

        return redirect()
            ->route('tahfid.progress.index', array_filter(['nis' => $nis]))
            ->with($fail > 0 && $ok === 0 ? 'error' : 'success', $msg);
    }

    public function cancelImportProgress(Request $request)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return $guard;
        }

        $nis = trim((string) $request->input('nis', ''));
        session()->forget('tahfid_import_preview');

        return redirect()->route('tahfid.progress.index', array_filter(['nis' => $nis]));
    }

    public function laporan(Request $request)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return $guard;
        }

        $data = $this->buildLaporan($request);
        $perPage = 20;
        $total = count($data['items']);
        $page = max(1, (int) $request->query('page', 1));
        $lastPage = max(1, (int) ceil(max($total, 1) / $perPage));
        if ($total === 0) {
            $lastPage = 1;
        }
        if ($page > $lastPage) {
            $page = $lastPage;
        }
        $pageItems = $total > 0
            ? array_slice($data['items'], ($page - 1) * $perPage, $perPage)
            : [];
        $data['groups'] = $this->groupLaporanItems($pageItems);
        $data['paginator'] = [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'from' => $total === 0 ? 0 : (($page - 1) * $perPage) + 1,
            'to' => min($total, $page * $perPage),
        ];

        return view('tahfid_laporan', $this->staffViewData($data));
    }

    public function exportLaporan(Request $request)
    {
        $guard = $this->guardStaff();
        if ($guard !== null) {
            return $guard;
        }

        $data = $this->buildLaporan($request);
        $query = array_filter([
            'code01' => $data['code01'] ?? '',
            'tingkat' => $data['tingkat'] ?? '',
            'kelas' => $data['kelas'] ?? '',
            'q' => $data['q'] ?? '',
            'status' => $data['status'] ?? '',
        ], fn ($v) => $v !== null && $v !== '');

        if (! empty($data['errorMessage'])) {
            return redirect()->route('tahfid.laporan.index', $query)->with('error', $data['errorMessage']);
        }
        if (! empty($data['needUnit'])) {
            return redirect()->route('tahfid.laporan.index')->with('error', 'Pilih unit terlebih dahulu untuk mengunduh laporan.');
        }
        if (count($data['items'] ?? []) === 0) {
            return redirect()->route('tahfid.laporan.index', $query)->with('error', 'Tidak ada data untuk diunduh.');
        }

        $statusLabel = match ((string) ($data['status'] ?? '')) {
            'lunas' => 'Tuntas semua',
            'proses' => 'Masih proses',
            'belum_selesai' => 'Belum selesai',
            default => 'Keseluruhan',
        };
        $qLabel = trim((string) ($data['q'] ?? ''));
        $kelasPart = preg_replace('/[^A-Za-z0-9_-]/', '_', trim((string) ($data['kelas'] ?? '')));
        $statusPart = preg_replace('/[^A-Za-z0-9_-]/', '_', strtolower($statusLabel));
        $filename = 'laporan_tahfid_'.$statusPart.'_'.now()->format('Ymd_His');
        if ($kelasPart !== '') {
            $filename .= '_'.$kelasPart;
        }

        $sekolahName = trim((string) ($data['scopeSekolah'] ?? ''));
        if ($sekolahName === '') {
            foreach ($data['items'] as $item) {
                $sekolahName = trim((string) ($item['sekolah'] ?? ''));
                if ($sekolahName !== '') {
                    break;
                }
            }
        }
        if ($sekolahName === '') {
            foreach ($this->arrayItems($data['sekolah'] ?? []) as $sch) {
                if (trim((string) ($sch['code01'] ?? '')) === trim((string) ($data['code01'] ?? ''))) {
                    $sekolahName = trim((string) ($sch['sekolah'] ?? ''));
                    break;
                }
            }
        }

        return Excel::download(
            new TahfidLaporanExport(
                $data['items'],
                $data['ringkasan'],
                [
                    'sekolah' => $sekolahName,
                    'kelas' => trim((string) ($data['kelas'] ?? '')),
                    'tingkat' => trim((string) ($data['tingkat'] ?? '')),
                    'filter_label' => $statusLabel,
                    'q' => $qLabel,
                ]
            ),
            $filename.'.xlsx'
        );
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

    private function buildLaporan(Request $request): array
    {
        $isSuperadmin = $this->isSuperadmin();
        $code01 = $isSuperadmin
            ? trim((string) $request->query('code01', ''))
            : trim((string) session('user.code01', ''));
        $tingkat = strtoupper(trim((string) $request->query('tingkat', '')));
        $kelas = trim((string) $request->query('kelas', ''));
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        if (! in_array($status, ['belum_selesai', 'proses', 'lunas'], true)) {
            $status = '';
        }

        $empty = [
            'items' => [],
            'groups' => [],
            'sekolah' => [],
            'code01' => $code01,
            'tingkat' => $tingkat,
            'kelas' => $kelas,
            'q' => $q,
            'status' => $status,
            'tingkatOptions' => [],
            'kelasOptions' => [],
            'ringkasan' => [
                'jumlah_siswi' => 0,
                'lunas_penuh' => 0,
                'masih_proses' => 0,
                'belum_mulai' => 0,
            ],
            'needUnit' => $isSuperadmin && $code01 === '',
            'errorMessage' => null,
            'navActive' => 'laporan',
            'scopeSekolah' => '',
        ];

        $payload = $this->callWs('manageTahfid', array_filter([
            'action' => 'laporan',
            'code01' => $code01,
            'kelas' => $kelas,
            'q' => $q,
        ], fn ($v) => $v !== null && $v !== ''));

        if ($payload['error'] !== null) {
            $empty['errorMessage'] = $payload['error'];

            return $empty;
        }

        $items = $this->arrayItems($payload['data']['items'] ?? []);
        $kelasMaster = [];
        if (isset($payload['data']['kelas']) && is_array($payload['data']['kelas'])) {
            foreach ($payload['data']['kelas'] as $row) {
                $val = is_string($row) ? trim($row) : trim((string) ($row['kelas'] ?? ''));
                if ($val !== '') {
                    $kelasMaster[$val] = $val;
                }
            }
        }
        if (count($kelasMaster) === 0) {
            foreach ($items as $item) {
                $val = trim((string) ($item['kelas'] ?? ''));
                if ($val !== '') {
                    $kelasMaster[$val] = $val;
                }
            }
        }

        $tingkatOptions = $this->jadwalTingkatOptions(array_map(fn ($nama) => ['kelas' => $nama], array_values($kelasMaster)));
        $kelasOptions = [];
        foreach ($kelasMaster as $nama) {
            if ($tingkat !== '' && $this->tingkatOf($nama) !== $tingkat) {
                continue;
            }
            $kelasOptions[] = $nama;
        }
        natcasesort($kelasOptions);
        $kelasOptions = array_values($kelasOptions);

        $filtered = $this->filterLaporanItems($items, $tingkat, $status);

        return [
            'items' => $filtered,
            'groups' => $this->groupLaporanItems($filtered),
            'sekolah' => $this->arrayItems($payload['data']['sekolah'] ?? []),
            'code01' => $code01,
            'tingkat' => $tingkat,
            'kelas' => $kelas,
            'q' => $q,
            'status' => $status,
            'tingkatOptions' => $tingkatOptions,
            'kelasOptions' => $kelasOptions,
            'ringkasan' => $this->laporanRingkasan($filtered),
            'needUnit' => (bool) ($payload['data']['need_unit'] ?? false),
            'errorMessage' => null,
            'navActive' => 'laporan',
            'scopeSekolah' => trim((string) ($payload['data']['scope_sekolah'] ?? '')),
        ];
    }

    private function filterLaporanItems(array $items, string $tingkat, string $status): array
    {
        $filtered = [];
        foreach ($items as $item) {
            $kelas = trim((string) ($item['kelas'] ?? ''));
            $itemTingkat = $this->tingkatOf($kelas);
            if ($tingkat !== '' && $itemTingkat !== $tingkat) {
                continue;
            }
            if ((int) ($item['target'] ?? 0) < 1) {
                continue;
            }
            $badge = trim((string) ($item['badge'] ?? 'belum'));
            if ($status === 'lunas' && $badge !== 'lunas') {
                continue;
            }
            if ($status === 'proses' && $badge !== 'proses') {
                continue;
            }
            if ($status === 'belum_selesai' && $badge === 'lunas') {
                continue;
            }
            $item['_tingkat'] = $itemTingkat;
            $filtered[] = $item;
        }

        return $filtered;
    }

    private function laporanRingkasan(array $items): array
    {
        $ringkasan = [
            'jumlah_siswi' => 0,
            'lunas_penuh' => 0,
            'masih_proses' => 0,
            'belum_mulai' => 0,
        ];
        foreach ($items as $item) {
            $ringkasan['jumlah_siswi']++;
            $badge = trim((string) ($item['badge'] ?? 'belum'));
            if ($badge === 'lunas') {
                $ringkasan['lunas_penuh']++;
            } elseif ($badge === 'proses') {
                $ringkasan['masih_proses']++;
            } else {
                $ringkasan['belum_mulai']++;
            }
        }

        return $ringkasan;
    }

    private function groupLaporanItems(array $items): array
    {
        $groups = [];
        foreach ($items as $item) {
            $kelas = trim((string) ($item['kelas'] ?? ''));
            $key = $kelas !== '' ? $kelas : 'Tanpa kelas';
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'kelas' => $key,
                    'tingkat' => $this->tingkatOf($kelas),
                    'count' => 0,
                    'lunas_penuh' => 0,
                    'items' => [],
                ];
            }
            $groups[$key]['count']++;
            if (trim((string) ($item['badge'] ?? '')) === 'lunas') {
                $groups[$key]['lunas_penuh']++;
            }
            $groups[$key]['items'][] = $item;
        }
        uasort($groups, function ($a, $b) {
            $rank = $this->tingkatRank((string) $a['tingkat']) <=> $this->tingkatRank((string) $b['tingkat']);
            if ($rank !== 0) {
                return $rank;
            }

            return strnatcasecmp((string) $a['kelas'], (string) $b['kelas']);
        });

        return array_values($groups);
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
            return redirect()->route('admin.index');
        }
        if (strtolower((string) session('user.role', '')) === 'siswa') {
            return redirect()->route('tahfid.siswa.index');
        }

        return null;
    }

    private function guardSiswa()
    {
        if (session('user.app') !== 'tahfid') {
            return redirect()->route('admin.index');
        }
        if (strtolower((string) session('user.role', '')) !== 'siswa') {
            return redirect()->route('tahfid.jadwal.index');
        }

        return null;
    }

    private function findProgressTemplateHeader(array $sheet): ?int
    {
        foreach ($sheet as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $joined = mb_strtolower(implode(' ', array_map(fn ($v) => (string) $v, $row)));
            if (str_contains($joined, 'jadwal_detail_id') && str_contains($joined, 'nis')) {
                return (int) $i;
            }
        }

        return null;
    }

    private function mapProgressTemplateHeader(array $header): array
    {
        $map = [];
        foreach ($header as $i => $col) {
            $key = mb_strtolower(trim((string) $col));
            if ($key === 'nis') {
                $map['nis'] = (int) $i;
            } elseif (in_array($key, ['jadwal_detail_id', 'id jadwal', 'id_detail'], true)) {
                $map['jadwal_detail_id'] = (int) $i;
            } elseif (in_array($key, ['status baru', 'status_baru'], true)) {
                $map['status_baru'] = (int) $i;
            } elseif (in_array($key, ['ayat dari', 'ayat_dari'], true)) {
                $map['ayat_dari'] = (int) $i;
            } elseif (in_array($key, ['ayat sampai', 'ayat_sampai'], true)) {
                $map['ayat_sampai'] = (int) $i;
            } elseif ($key === 'catatan') {
                $map['catatan'] = (int) $i;
            } elseif ($key === 'nama') {
                $map['nama'] = (int) $i;
            } elseif ($key === 'kelas') {
                $map['kelas'] = (int) $i;
            } elseif (in_array($key, ['nama surat', 'surah_nama'], true)) {
                $map['surah_nama'] = (int) $i;
            } elseif (in_array($key, ['status saat ini', 'status_saat_ini'], true)) {
                $map['status_saat_ini'] = (int) $i;
            }
        }

        return $map;
    }

    private function buildProgressImportPreview(array $table): array
    {
        $updates = [];
        $skipped = 0;
        $invalid = [];
        foreach ($table as $idx => $row) {
            $status = strtolower(trim((string) ($row['status'] ?? '')));
            $nis = trim((string) ($row['nis'] ?? ''));
            $detailId = (int) ($row['jadwal_detail_id'] ?? 0);
            $nama = trim((string) ($row['nama'] ?? ''));
            $surat = trim((string) ($row['surah_nama'] ?? ''));
            $statusNow = strtolower(trim((string) ($row['status_saat_ini'] ?? '')));
            if (in_array($statusNow, ['lunas', 'selesai', 'lengkap', 'tuntas'], true)) {
                $statusNow = 'tuntas';
            }
            if ($status === '') {
                $skipped++;
                continue;
            }
            $item = [
                'baris' => (int) $idx + 1,
                'nis' => $nis,
                'nama' => $nama,
                'kelas' => trim((string) ($row['kelas'] ?? '')),
                'surat' => $surat !== '' ? $surat : ('ID '.$detailId),
                'status_saat_ini' => $statusNow,
                'status' => $status,
                'ayat_dari' => (int) ($row['ayat_dari'] ?? 0),
                'ayat_sampai' => (int) ($row['ayat_sampai'] ?? 0),
                'catatan' => trim((string) ($row['catatan'] ?? '')),
            ];
            if (! in_array($status, ['proses', 'lunas', 'tuntas', 'selesai', 'lengkap'], true)) {
                $item['alasan'] = 'Status baru harus proses atau tuntas';
                $invalid[] = $item;
                continue;
            }
            if ($nis === '' || $detailId < 1) {
                $item['alasan'] = 'NIS atau jadwal_detail_id kosong';
                $invalid[] = $item;
                continue;
            }
            if ($status === 'proses' && ($item['ayat_dari'] < 1 || $item['ayat_sampai'] < $item['ayat_dari'])) {
                $item['alasan'] = 'Isi rentang ayat untuk status proses';
                $invalid[] = $item;
                continue;
            }
            $updates[] = $item;
        }

        return [
            'updates' => $updates,
            'skipped' => $skipped,
            'invalid' => $invalid,
        ];
    }

    private function callWs(string $method, array $fields, int $timeout = 25): array
    {
        $token = trim((string) session('user.approval_token', ''));
        if ($token === '') {
            return ['error' => 'Token tidak tersedia. Muat ulang halaman, lalu coba lagi.', 'data' => []];
        }

        $wsUrl = rtrim((string) env('APPROVAL_WS_URL', 'http://103.23.103.43/ws_client/mualimat_reward/index.php'), '/');
        $request = array_merge([
            'method' => $method,
            'token' => $token,
        ], $fields);

        $logRequest = $request;
        unset($logRequest['token'], $logRequest['rows']);
        Log::info('Tahfid WS request', [
            'url' => $wsUrl,
            'request' => $logRequest,
            'username' => session('user.username'),
            'rows' => isset($fields['rows']) && is_array($fields['rows']) ? count($fields['rows']) : null,
        ]);

        try {
            $response = Http::timeout($timeout)->post($wsUrl, $request);
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
