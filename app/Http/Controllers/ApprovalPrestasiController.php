<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApprovalPrestasiController extends Controller
{
    public function index(Request $request)
    {
        if (session('user.app') !== 'approval-prestasi') {
            return redirect()->route('dashboard.presensi-sholat');
        }

        $status = $request->query('status', 'pending');
        if (! in_array($status, ['all', 'pending', 'approved'], true)) {
            $status = 'pending';
        }

        $q = trim((string) $request->query('q', ''));
        $tanggal = trim((string) $request->query('tanggal', ''));
        if ($tanggal !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            $tanggal = '';
        }

        [$items, $error, $scopeSekolah] = $this->fetchItems($status, $q, $tanggal);

        return view('approval_prestasi', [
            'items' => $items,
            'status' => $status,
            'q' => $q,
            'tanggal' => $tanggal,
            'scopeCode01' => trim((string) session('user.code01', '')),
            'scopeSekolah' => $scopeSekolah,
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
            'q' => ['nullable', 'string', 'max:100'],
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $token = trim((string) session('user.approval_token', ''));
        if ($token === '') {
            return redirect()->route('login.form')->with('login_error', 'Sesi approval berakhir. Silakan login ulang.');
        }
        $wsUrl = rtrim((string) env('APPROVAL_WS_URL', 'http://103.23.103.43/ws_client/mualimat_reward/index.php'), '/');
        $wsRequest = [
            'method' => 'approval',
            'action' => $validated['action'],
            'id' => (int) $validated['id'],
            'status_filter' => $validated['status'] ?? 'pending',
        ];
        Log::info('Approval WS action request', [
            'url' => $wsUrl,
            'request' => $wsRequest,
            'username' => session('user.username'),
        ]);

        try {
            $response = Http::timeout(20)->post($wsUrl, [
                'method' => 'approval',
                'token' => $token,
                'action' => $validated['action'],
                'id' => (int) $validated['id'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Approval WS action exception', [
                'url' => $wsUrl,
                'request' => $wsRequest,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Tidak dapat terhubung ke server approval.');
        }

        $payload = $response->json();
        Log::info('Approval WS action response', [
            'url' => $wsUrl,
            'status' => $response->status(),
            'json' => $payload,
            'request' => $wsRequest,
            'username' => session('user.username'),
        ]);
        if (! $response->ok() || ! is_array($payload) || (int) ($payload['status'] ?? 500) !== 200) {
            $message = is_array($payload) ? (string) ($payload['message'] ?? 'Aksi approval gagal.') : 'Aksi approval gagal.';

            return back()->with('error', $message);
        }

        $message = (string) (($payload['data']['message'] ?? null) ?: 'Aksi berhasil.');

        return redirect()
            ->route('approval.prestasi.index', array_filter([
                'status' => $validated['status'] ?? 'pending',
                'q' => $validated['q'] ?? null,
                'tanggal' => $validated['tanggal'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''))
            ->with('success', $message);
    }

    private function fetchItems(string $status, string $q = '', string $tanggal = ''): array
    {
        $token = trim((string) session('user.approval_token', ''));
        if ($token === '') {
            return [[], 'Sesi approval berakhir. Silakan login ulang.', ''];
        }
        $wsUrl = rtrim((string) env('APPROVAL_WS_URL', 'http://103.23.103.43/ws_client/mualimat_reward/index.php'), '/');

        $isapproved = '';
        if ($status === 'pending') {
            $isapproved = '0';
        } elseif ($status === 'approved') {
            $isapproved = '1';
        }

        $wsRequest = [
            'method' => 'approval',
            'action' => 'list',
            'isapproved' => $isapproved,
            'q' => $q,
            'tanggal' => $tanggal,
        ];
        Log::info('Approval WS list request', [
            'url' => $wsUrl,
            'request' => $wsRequest,
            'username' => session('user.username'),
            'scope_code01' => session('user.code01'),
        ]);

        try {
            $response = Http::timeout(20)->post($wsUrl, [
                'method' => 'approval',
                'token' => $token,
                'action' => 'list',
                'isapproved' => $isapproved,
                'q' => $q,
                'tanggal' => $tanggal,
            ]);
        } catch (\Throwable $e) {
            Log::error('Approval WS list exception', [
                'url' => $wsUrl,
                'request' => $wsRequest,
                'message' => $e->getMessage(),
            ]);

            return [[], 'Tidak dapat terhubung ke server approval.', ''];
        }

        $payload = $response->json();
        Log::info('Approval WS list response', [
            'url' => $wsUrl,
            'status' => $response->status(),
            'json' => $payload,
            'request' => $wsRequest,
            'username' => session('user.username'),
        ]);
        if (! $response->ok() || ! is_array($payload) || (int) ($payload['status'] ?? 500) !== 200) {
            $message = is_array($payload) ? (string) ($payload['message'] ?? 'Gagal memuat data approval.') : 'Gagal memuat data approval.';

            return [[], $message, ''];
        }

        $items = [];
        if (isset($payload['data']['items']) && is_array($payload['data']['items'])) {
            foreach ($payload['data']['items'] as $row) {
                if (is_array($row)) {
                    $items[] = $row;
                }
            }
        }

        // Fallback filter jika WS production belum support q/tanggal
        $items = $this->applyLocalFilters($items, $q, $tanggal);

        $scopeSekolah = trim((string) ($payload['data']['scope_sekolah'] ?? ''));
        if ($scopeSekolah === '' && $items !== []) {
            $scopeSekolah = trim((string) ($items[0]['sekolah'] ?? ''));
        }

        return [$items, null, $scopeSekolah];
    }

    private function applyLocalFilters(array $items, string $q, string $tanggal): array
    {
        if ($q === '' && $tanggal === '') {
            return $items;
        }

        $qLower = mb_strtolower($q);

        return array_values(array_filter($items, function (array $item) use ($qLower, $tanggal) {
            if ($qLower !== '') {
                $nocust = mb_strtolower((string) ($item['nocust'] ?? ''));
                $nmcust = mb_strtolower((string) ($item['nmcust'] ?? ''));
                if (! str_contains($nocust, $qLower) && ! str_contains($nmcust, $qLower)) {
                    return false;
                }
            }

            if ($tanggal !== '') {
                $created = (string) ($item['created_at'] ?? '');
                $datePart = $created !== '' ? substr($created, 0, 10) : '';
                if ($datePart !== $tanggal) {
                    return false;
                }
            }

            return true;
        }));
    }
}
