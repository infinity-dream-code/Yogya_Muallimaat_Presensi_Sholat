<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApprovalPrestasiController extends Controller
{
    public function index(Request $request)
    {
        if (session('user.app') !== 'approval-prestasi') {
            return redirect()->route('dashboard.presensi-sholat');
        }

        $status = $request->query('status', 'pending');
        [$items, $error] = $this->fetchItems($status);

        return view('approval_prestasi', [
            'items' => $items,
            'status' => $status,
            'scopeCode01' => trim((string) session('user.code01', '')),
            'errorMessage' => $error,
        ]);
    }

    public function action(Request $request)
    {
        if (session('user.app') !== 'approval-prestasi') {
            return back()->with('error', 'Akses ditolak.');
        }

        $validated = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
            'action' => ['required', 'in:approve,tolak'],
            'status' => ['nullable', 'string', 'in:all,pending,approved'],
        ]);

        $token = trim((string) session('user.approval_token', ''));
        if ($token === '') {
            return redirect()->route('login.form')->with('login_error', 'Sesi approval berakhir. Silakan login ulang.');
        }
        $wsUrl = rtrim((string) env('APPROVAL_WS_URL', 'http://103.23.103.43/ws_client/mualimat_reward/index.php'), '/');

        try {
            $response = Http::timeout(20)->post($wsUrl, [
                'method' => 'approval',
                'token' => $token,
                'action' => $validated['action'],
                'id' => (int) $validated['id'],
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Tidak dapat terhubung ke server approval.');
        }

        $payload = $response->json();
        if (! $response->ok() || ! is_array($payload) || (int) ($payload['status'] ?? 500) !== 200) {
            $message = is_array($payload) ? (string) ($payload['message'] ?? 'Aksi approval gagal.') : 'Aksi approval gagal.';
            return back()->with('error', $message);
        }

        $message = (string) (($payload['data']['message'] ?? null) ?: 'Aksi berhasil.');

        return redirect()
            ->route('approval.prestasi.index', ['status' => $validated['status'] ?? 'pending'])
            ->with('success', $message);
    }

    private function fetchItems(string $status): array
    {
        $token = trim((string) session('user.approval_token', ''));
        if ($token === '') {
            return [[], 'Sesi approval berakhir. Silakan login ulang.'];
        }
        $wsUrl = rtrim((string) env('APPROVAL_WS_URL', 'http://103.23.103.43/ws_client/mualimat_reward/index.php'), '/');

        $isapproved = '';
        if ($status === 'pending') {
            $isapproved = '0';
        } elseif ($status === 'approved') {
            $isapproved = '1';
        }

        try {
            $response = Http::timeout(20)->post($wsUrl, [
                'method' => 'approval',
                'token' => $token,
                'action' => 'list',
                'isapproved' => $isapproved,
            ]);
        } catch (\Throwable $e) {
            return [[], 'Tidak dapat terhubung ke server approval.'];
        }

        $payload = $response->json();
        if (! $response->ok() || ! is_array($payload) || (int) ($payload['status'] ?? 500) !== 200) {
            $message = is_array($payload) ? (string) ($payload['message'] ?? 'Gagal memuat data approval.') : 'Gagal memuat data approval.';
            return [[], $message];
        }

        $items = [];
        if (isset($payload['data']['items']) && is_array($payload['data']['items'])) {
            foreach ($payload['data']['items'] as $row) {
                if (is_array($row)) {
                    $items[] = $row;
                }
            }
        }

        return [$items, null];
    }
}

