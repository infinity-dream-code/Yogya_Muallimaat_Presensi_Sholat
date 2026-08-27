<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApprovalAdminController extends Controller
{
    public function index()
    {
        $guard = $this->guardSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        [$users, $sekolah, $error] = $this->fetchUsers();
        $ctx = $this->adminContext();

        return view('approval_admin', [
            'users' => $users,
            'sekolah' => $sekolah,
            'errorMessage' => $error,
            'isSuperadmin' => true,
            'navActive' => 'admin',
            'scopeCode01' => '',
            'scopeSekolah' => '',
            'adminStoreRoute' => $ctx['store'],
            'adminUpdateRoute' => $ctx['update'],
            'adminDeleteRoute' => $ctx['delete'],
        ]);
    }

    public function store(Request $request)
    {
        $guard = $this->guardSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:4', 'max:128'],
            'nama' => ['required', 'string', 'max:50'],
            'role' => ['required', 'in:Musrifah,superadmin'],
            'code01' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validated['role'] === 'Musrifah' && trim((string) ($validated['code01'] ?? '')) === '') {
            return back()->withInput()->with('error', 'Sekolah wajib diisi untuk Musrifah.');
        }

        return $this->sendAction('create', [
            'username' => $validated['username'],
            'password' => $validated['password'],
            'nama' => $validated['nama'],
            'role' => $validated['role'],
            'code01' => trim((string) ($validated['code01'] ?? '')),
        ]);
    }

    public function update(Request $request)
    {
        $guard = $this->guardSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $validated = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
            'username' => ['required', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:4', 'max:128'],
            'nama' => ['required', 'string', 'max:50'],
            'role' => ['required', 'in:Musrifah,superadmin'],
            'code01' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validated['role'] === 'Musrifah' && trim((string) ($validated['code01'] ?? '')) === '') {
            return back()->withInput()->with('error', 'Sekolah wajib diisi untuk Musrifah.');
        }

        $payload = [
            'id' => (int) $validated['id'],
            'username' => $validated['username'],
            'nama' => $validated['nama'],
            'role' => $validated['role'],
            'code01' => trim((string) ($validated['code01'] ?? '')),
        ];
        if (trim((string) ($validated['password'] ?? '')) !== '') {
            $payload['password'] = $validated['password'];
        }

        return $this->sendAction('update', $payload);
    }

    public function destroy(Request $request)
    {
        $guard = $this->guardSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $validated = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ]);

        return $this->sendAction('delete', [
            'id' => (int) $validated['id'],
        ]);
    }

    private function adminContext(): array
    {
        if (session('user.app') === 'catatan-kepribadian') {
            return [
                'app' => 'catatan-kepribadian',
                'home' => 'catatan.kepribadian.index',
                'index' => 'catatan.admin.index',
                'store' => 'catatan.admin.store',
                'update' => 'catatan.admin.update',
                'delete' => 'catatan.admin.delete',
            ];
        }

        if (session('user.app') === 'tahfid') {
            return [
                'app' => 'tahfid',
                'home' => 'tahfid.jadwal.index',
                'index' => 'tahfid.admin.index',
                'store' => 'tahfid.admin.store',
                'update' => 'tahfid.admin.update',
                'delete' => 'tahfid.admin.delete',
            ];
        }

        return [
            'app' => 'approval-prestasi',
            'home' => 'approval.prestasi.index',
            'index' => 'approval.admin.index',
            'store' => 'approval.admin.store',
            'update' => 'approval.admin.update',
            'delete' => 'approval.admin.delete',
        ];
    }

    private function guardSuperadmin()
    {
        $app = (string) session('user.app', '');
        if (! in_array($app, ['approval-prestasi', 'catatan-kepribadian', 'tahfid'], true)) {
            return redirect()->route('login.form');
        }
        if (strtolower(trim((string) session('user.role', ''))) !== 'superadmin') {
            return redirect()->route($this->adminContext()['home'])->with('error', 'Menu kelola admin hanya untuk superadmin.');
        }

        return null;
    }

    private function fetchUsers(): array
    {
        $payload = $this->callWs(['action' => 'list']);
        if ($payload['error'] !== null) {
            return [[], [], $payload['error']];
        }

        $data = $payload['data'];
        $users = [];
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $row) {
                if (is_array($row)) {
                    $users[] = $row;
                }
            }
        }
        $sekolah = [];
        if (isset($data['sekolah']) && is_array($data['sekolah'])) {
            foreach ($data['sekolah'] as $row) {
                if (is_array($row)) {
                    $sekolah[] = $row;
                }
            }
        }

        return [$users, $sekolah, null];
    }

    private function sendAction(string $action, array $fields)
    {
        $payload = $this->callWs(array_merge(['action' => $action], $fields));
        if ($payload['error'] !== null) {
            return back()->withInput()->with('error', $payload['error']);
        }

        $message = (string) (($payload['data']['message'] ?? null) ?: 'Aksi berhasil.');

        return redirect()->route($this->adminContext()['index'])->with('success', $message);
    }

    private function callWs(array $fields): array
    {
        $token = trim((string) session('user.approval_token', ''));
        if ($token === '') {
            return ['error' => 'Sesi approval berakhir. Silakan login ulang.', 'data' => []];
        }

        $wsUrl = rtrim((string) env('APPROVAL_WS_URL', 'http://103.23.103.43/ws_client/mualimat_reward/index.php'), '/');
        $request = array_merge([
            'method' => 'manageUsers',
            'token' => $token,
        ], $fields);

        $logRequest = $request;
        unset($logRequest['password'], $logRequest['token']);
        Log::info('Approval WS manageUsers request', [
            'url' => $wsUrl,
            'request' => $logRequest,
            'username' => session('user.username'),
        ]);

        try {
            $response = Http::timeout(20)->post($wsUrl, $request);
        } catch (\Throwable $e) {
            Log::error('Approval WS manageUsers exception', [
                'url' => $wsUrl,
                'request' => $logRequest,
                'message' => $e->getMessage(),
            ]);

            return ['error' => 'Tidak dapat terhubung ke server approval.', 'data' => []];
        }

        $json = $response->json();
        Log::info('Approval WS manageUsers response', [
            'url' => $wsUrl,
            'status' => $response->status(),
            'json' => is_array($json) ? array_diff_key($json, ['data' => true]) : $json,
            'request' => $logRequest,
            'username' => session('user.username'),
        ]);

        if (! $response->ok() || ! is_array($json) || (int) ($json['status'] ?? 500) !== 200) {
            $message = is_array($json) ? (string) ($json['message'] ?? 'Gagal memproses data admin.') : 'Gagal memproses data admin.';

            return ['error' => $message, 'data' => []];
        }

        $data = isset($json['data']) && is_array($json['data']) ? $json['data'] : [];

        return ['error' => null, 'data' => $data];
    }
}
