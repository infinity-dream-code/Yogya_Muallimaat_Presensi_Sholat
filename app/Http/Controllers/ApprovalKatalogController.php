<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApprovalKatalogController extends Controller
{
    public function index()
    {
        $guard = $this->guardSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        [$kategori, $tingkat, $poin, $sekolah, $error] = $this->fetchKatalog();

        return view('approval_katalog', [
            'kategori' => $kategori,
            'tingkat' => $tingkat,
            'poin' => $poin,
            'sekolah' => $sekolah,
            'errorMessage' => $error,
            'isSuperadmin' => true,
            'navActive' => 'katalog',
            'scopeCode01' => '',
            'scopeSekolah' => '',
        ]);
    }

    public function store(Request $request)
    {
        $guard = $this->guardSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        return $this->sendAction('create', $this->validatedFields($request, false));
    }

    public function update(Request $request)
    {
        $guard = $this->guardSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        return $this->sendAction('update', $this->validatedFields($request, true));
    }

    public function destroy(Request $request)
    {
        $guard = $this->guardSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $validated = $request->validate([
            'entity' => ['required', 'in:kategori,tingkat,poin'],
            'id' => ['required', 'integer', 'min:1'],
        ]);

        return $this->sendAction('delete', [
            'entity' => $validated['entity'],
            'id' => (int) $validated['id'],
        ]);
    }

    private function validatedFields(Request $request, bool $needId): array
    {
        $entity = $request->input('entity');
        $rules = [
            'entity' => ['required', 'in:kategori,tingkat,poin'],
            'nama' => ['required', 'string', 'max:120'],
            'urut' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
        if ($needId) {
            $rules['id'] = ['required', 'integer', 'min:1'];
        }
        if ($entity === 'kategori') {
            $rules['kode'] = ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'];
            $rules['code01'] = ['nullable', 'string', 'max:20'];
        } elseif ($entity === 'tingkat') {
            $rules['kategori_id'] = ['required', 'integer', 'min:1'];
        } else {
            $rules['tingkat_id'] = ['required', 'integer', 'min:1'];
            $rules['nilai'] = ['nullable', 'numeric', 'min:0', 'max:9999999999999'];
        }

        $validated = $request->validate($rules);
        $payload = [
            'entity' => $validated['entity'],
            'nama' => $validated['nama'],
        ];
        if (isset($validated['id'])) {
            $payload['id'] = (int) $validated['id'];
        }
        if (array_key_exists('urut', $validated) && $validated['urut'] !== null && $validated['urut'] !== '') {
            $payload['urut'] = (int) $validated['urut'];
        }
        if ($validated['entity'] === 'kategori') {
            $payload['kode'] = strtoupper(trim((string) $validated['kode']));
            $payload['code01'] = trim((string) ($validated['code01'] ?? ''));
        } elseif ($validated['entity'] === 'tingkat') {
            $payload['kategori_id'] = (int) $validated['kategori_id'];
        } else {
            $payload['tingkat_id'] = (int) $validated['tingkat_id'];
            $payload['nilai'] = (string) ($validated['nilai'] ?? '0');
        }

        return $payload;
    }

    private function guardSuperadmin()
    {
        if (session('user.app') !== 'approval-prestasi') {
            return redirect()->route('dashboard.presensi-sholat');
        }
        if (strtolower(trim((string) session('user.role', ''))) !== 'superadmin') {
            return redirect()->route('approval.prestasi.index')->with('error', 'Pengaturan katalog hanya untuk superadmin.');
        }

        return null;
    }

    private function fetchKatalog(): array
    {
        $payload = $this->callWs(['action' => 'list']);
        if ($payload['error'] !== null) {
            return [[], [], [], [], $payload['error']];
        }

        $data = $payload['data'];
        $pick = static function (array $rows): array {
            $out = [];
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $out[] = $row;
                }
            }

            return $out;
        };

        $sekolah = [];
        if (isset($data['sekolah']) && is_array($data['sekolah'])) {
            $sekolah = $pick($data['sekolah']);
        }

        return [
            $pick($data['kategori'] ?? []),
            $pick($data['tingkat'] ?? []),
            $pick($data['poin'] ?? []),
            $sekolah,
            null,
        ];
    }

    private function sendAction(string $action, array $fields)
    {
        $payload = $this->callWs(array_merge(['action' => $action], $fields));
        if ($payload['error'] !== null) {
            return back()->withInput()->with('error', $payload['error']);
        }

        $message = (string) (($payload['data']['message'] ?? null) ?: 'Aksi berhasil.');

        return redirect()->route('approval.katalog.index')->with('success', $message);
    }

    private function callWs(array $fields): array
    {
        $token = trim((string) session('user.approval_token', ''));
        if ($token === '') {
            return ['error' => 'Sesi approval berakhir. Silakan login ulang.', 'data' => []];
        }

        $wsUrl = rtrim((string) env('APPROVAL_WS_URL', 'http://103.23.103.43/ws_client/mualimat_reward/index.php'), '/');
        $request = array_merge([
            'method' => 'manageKatalog',
            'token' => $token,
        ], $fields);

        $logRequest = $request;
        unset($logRequest['token']);
        Log::info('Approval WS manageKatalog request', [
            'url' => $wsUrl,
            'request' => $logRequest,
            'username' => session('user.username'),
        ]);

        try {
            $response = Http::timeout(20)->post($wsUrl, $request);
        } catch (\Throwable $e) {
            Log::error('Approval WS manageKatalog exception', [
                'url' => $wsUrl,
                'request' => $logRequest,
                'message' => $e->getMessage(),
            ]);

            return ['error' => 'Tidak dapat terhubung ke server approval.', 'data' => []];
        }

        $json = $response->json();
        Log::info('Approval WS manageKatalog response', [
            'url' => $wsUrl,
            'status' => $response->status(),
            'json' => is_array($json) ? array_diff_key($json, ['data' => true]) : $json,
            'request' => $logRequest,
            'username' => session('user.username'),
        ]);

        if (! $response->ok() || ! is_array($json) || (int) ($json['status'] ?? 500) !== 200) {
            $message = is_array($json) ? (string) ($json['message'] ?? 'Gagal memproses katalog.') : 'Gagal memproses katalog.';

            return ['error' => $message, 'data' => []];
        }

        $data = isset($json['data']) && is_array($json['data']) ? $json['data'] : [];

        return ['error' => null, 'data' => $data];
    }
}
