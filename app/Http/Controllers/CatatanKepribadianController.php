<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CatatanKepribadianController extends Controller
{
    public function index(Request $request)
    {
        $guard = $this->guardApp();
        if ($guard !== null) {
            return $guard;
        }

        $q = trim((string) $request->query('q', ''));
        $bta = trim((string) $request->query('bta', ''));
        $semester = trim((string) $request->query('semester', ''));
        if (! in_array($semester, ['', '1', '2'], true)) {
            $semester = '';
        }

        [$items, $tahunAkademik, $error, $scopeSekolah] = $this->fetchList($q, $bta, $semester);
        $isSuperadmin = strtolower(trim((string) session('user.role', ''))) === 'superadmin';

        return view('catatan_kepribadian', [
            'items' => $items,
            'tahunAkademik' => $tahunAkademik,
            'q' => $q,
            'bta' => $bta,
            'semester' => $semester,
            'errorMessage' => $error,
            'isSuperadmin' => $isSuperadmin,
            'navActive' => 'catatan',
            'scopeCode01' => $isSuperadmin ? '' : trim((string) session('user.code01', '')),
            'scopeSekolah' => $scopeSekolah,
        ]);
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
        return $this->save($request, false);
    }

    public function update(Request $request)
    {
        return $this->save($request, true);
    }

    public function destroy(Request $request)
    {
        $guard = $this->guardApp();
        if ($guard !== null) {
            return $guard;
        }

        $validated = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $payload = $this->callWs('manageCatatanKepribadian', [
            'action' => 'delete',
            'id' => (int) $validated['id'],
        ]);
        if ($payload['error'] !== null) {
            return back()->with('error', $payload['error']);
        }

        $message = (string) (($payload['data']['message'] ?? null) ?: 'Catatan dihapus.');

        return redirect()->route('catatan.kepribadian.index')->with('success', $message);
    }

    public function searchSiswa(Request $request)
    {
        $guard = $this->guardApp();
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

        $items = [];
        if (isset($payload['data']['items']) && is_array($payload['data']['items'])) {
            foreach ($payload['data']['items'] as $row) {
                if (is_array($row)) {
                    $items[] = $row;
                }
            }
        }

        return response()->json(['data' => $items]);
    }

    private function formView(?int $id)
    {
        $guard = $this->guardApp();
        if ($guard !== null) {
            return $guard;
        }

        $item = null;
        $tahunAkademik = [];
        $error = null;
        $scopeSekolah = '';

        if ($id !== null) {
            $payload = $this->callWs('manageCatatanKepribadian', [
                'action' => 'get',
                'id' => $id,
            ]);
            if ($payload['error'] !== null) {
                return redirect()->route('catatan.kepribadian.index')->with('error', $payload['error']);
            }
            $item = is_array($payload['data']['item'] ?? null) ? $payload['data']['item'] : null;
            if ($item === null) {
                return redirect()->route('catatan.kepribadian.index')->with('error', 'Catatan tidak ditemukan.');
            }
            if (isset($payload['data']['tahun_akademik']) && is_array($payload['data']['tahun_akademik'])) {
                foreach ($payload['data']['tahun_akademik'] as $row) {
                    $val = is_string($row) ? trim($row) : '';
                    if ($val !== '') {
                        $tahunAkademik[] = $val;
                    }
                }
            }
            $scopeSekolah = trim((string) ($payload['data']['scope_sekolah'] ?? ''));
        } else {
            [, $tahunAkademik, $error, $scopeSekolah] = $this->fetchList('', '', '');
        }

        $isSuperadmin = strtolower(trim((string) session('user.role', ''))) === 'superadmin';

        return view('catatan_kepribadian_form', [
            'item' => $item,
            'tahunAkademik' => $tahunAkademik,
            'errorMessage' => $error,
            'isSuperadmin' => $isSuperadmin,
            'navActive' => $item ? 'catatan' : 'catatan-tambah',
            'scopeCode01' => $isSuperadmin ? '' : trim((string) session('user.code01', '')),
            'scopeSekolah' => $scopeSekolah,
        ]);
    }

    private function save(Request $request, bool $needId)
    {
        $guard = $this->guardApp();
        if ($guard !== null) {
            return $guard;
        }

        $rules = [
            'nis' => ['required', 'string', 'max:50'],
            'tahun_akademik' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:1,2'],
            'jenis_pelanggaran' => ['required', 'string', 'min:3', 'max:500'],
            'bentuk_pembinaan' => ['required', 'string', 'min:3', 'max:500'],
            'skor' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ];
        if ($needId) {
            $rules['id'] = ['required', 'integer', 'min:1'];
        }
        $validated = $request->validate($rules);

        $fields = [
            'action' => $needId ? 'update' : 'create',
            'nis' => trim($validated['nis']),
            'tahun_akademik' => trim($validated['tahun_akademik']),
            'semester' => (string) $validated['semester'],
            'jenis_pelanggaran' => trim($validated['jenis_pelanggaran']),
            'bentuk_pembinaan' => trim($validated['bentuk_pembinaan']),
            'skor' => (string) $validated['skor'],
        ];
        if ($needId) {
            $fields['id'] = (int) $validated['id'];
        }

        $payload = $this->callWs('manageCatatanKepribadian', $fields);
        if ($payload['error'] !== null) {
            return back()->withInput()->with('error', $payload['error']);
        }

        $message = (string) (($payload['data']['message'] ?? null) ?: 'Catatan tersimpan.');

        return redirect()->route('catatan.kepribadian.index')->with('success', $message);
    }

    private function fetchList(string $q, string $bta, string $semester): array
    {
        $payload = $this->callWs('manageCatatanKepribadian', array_filter([
            'action' => 'list',
            'q' => $q,
            'bta' => $bta,
            'semester' => $semester,
        ], fn ($v) => $v !== null && $v !== ''));

        if ($payload['error'] !== null) {
            return [[], [], $payload['error'], ''];
        }

        $items = [];
        if (isset($payload['data']['items']) && is_array($payload['data']['items'])) {
            foreach ($payload['data']['items'] as $row) {
                if (is_array($row)) {
                    $items[] = $row;
                }
            }
        }
        $tahun = [];
        if (isset($payload['data']['tahun_akademik']) && is_array($payload['data']['tahun_akademik'])) {
            foreach ($payload['data']['tahun_akademik'] as $row) {
                $val = is_string($row) ? trim($row) : '';
                if ($val !== '') {
                    $tahun[] = $val;
                }
            }
        }

        return [
            $items,
            $tahun,
            null,
            trim((string) ($payload['data']['scope_sekolah'] ?? '')),
        ];
    }

    private function guardApp()
    {
        if (session('user.app') !== 'catatan-kepribadian') {
            return redirect()->route('login.form');
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
        Log::info('Catatan kepribadian WS request', [
            'url' => $wsUrl,
            'request' => $logRequest,
            'username' => session('user.username'),
        ]);

        try {
            $response = Http::timeout(20)->post($wsUrl, $request);
        } catch (\Throwable $e) {
            Log::error('Catatan kepribadian WS exception', [
                'url' => $wsUrl,
                'request' => $logRequest,
                'message' => $e->getMessage(),
            ]);

            return ['error' => 'Tidak dapat terhubung ke server.', 'data' => []];
        }

        $json = $response->json();
        Log::info('Catatan kepribadian WS response', [
            'url' => $wsUrl,
            'status' => $response->status(),
            'json' => is_array($json) ? array_diff_key($json, ['data' => true]) : $json,
            'request' => $logRequest,
        ]);

        if (! $response->ok() || ! is_array($json) || (int) ($json['status'] ?? 500) !== 200) {
            $message = is_array($json) ? (string) ($json['message'] ?? 'Gagal memproses catatan.') : 'Gagal memproses catatan.';

            return ['error' => $message, 'data' => []];
        }

        $data = isset($json['data']) && is_array($json['data']) ? $json['data'] : [];

        return ['error' => null, 'data' => $data];
    }
}
