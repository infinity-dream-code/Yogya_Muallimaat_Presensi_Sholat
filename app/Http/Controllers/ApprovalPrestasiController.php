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
        if (! in_array($status, ['all', 'pending', 'approved', 'canceled'], true)) {
            $status = 'pending';
        }

        $q = trim((string) $request->query('q', ''));
        $tanggalDari = trim((string) $request->query('tanggal_dari', ''));
        $tanggalSampai = trim((string) $request->query('tanggal_sampai', ''));
        if ($tanggalDari !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalDari)) {
            $tanggalDari = '';
        }
        if ($tanggalSampai !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalSampai)) {
            $tanggalSampai = '';
        }

        [$items, $error, $scopeSekolah] = $this->fetchItems($status, $q, $tanggalDari, $tanggalSampai);
        $isSuperadmin = strtolower(trim((string) session('user.role', ''))) === 'superadmin';

        return view('approval_prestasi', [
            'items' => $items,
            'status' => $status,
            'q' => $q,
            'tanggalDari' => $tanggalDari,
            'tanggalSampai' => $tanggalSampai,
            'isSuperadmin' => $isSuperadmin,
            'navActive' => 'approval',
            'scopeCode01' => $isSuperadmin ? '' : trim((string) session('user.code01', '')),
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
            'status' => ['nullable', 'string', 'in:all,pending,approved,canceled'],
            'q' => ['nullable', 'string', 'max:100'],
            'tanggal_dari' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_sampai' => ['nullable', 'date_format:Y-m-d'],
            'catatan_admin' => ['required_if:action,tolak', 'nullable', 'string', 'min:3', 'max:500'],
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
        if ($validated['action'] === 'tolak') {
            $wsRequest['catatan_admin'] = trim((string) ($validated['catatan_admin'] ?? ''));
        }
        Log::info('Approval WS action request', [
            'url' => $wsUrl,
            'request' => $wsRequest,
            'username' => session('user.username'),
        ]);

        try {
            $wsBody = [
                'method' => 'approval',
                'token' => $token,
                'action' => $validated['action'],
                'id' => (int) $validated['id'],
            ];
            if ($validated['action'] === 'tolak') {
                $wsBody['catatan_admin'] = trim((string) ($validated['catatan_admin'] ?? ''));
            }
            $response = Http::timeout(20)->post($wsUrl, $wsBody);
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
                'tanggal_dari' => $validated['tanggal_dari'] ?? null,
                'tanggal_sampai' => $validated['tanggal_sampai'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''))
            ->with('success', $message);
    }

    private function fetchItems(string $status, string $q = '', string $tanggalDari = '', string $tanggalSampai = ''): array
    {
        $token = trim((string) session('user.approval_token', ''));
        if ($token === '') {
            return [[], 'Sesi approval berakhir. Silakan login ulang.', ''];
        }
        $wsUrl = rtrim((string) env('APPROVAL_WS_URL', 'http://103.23.103.43/ws_client/mualimat_reward/index.php'), '/');

        $isapproved = '';
        if ($status === 'pending') {
            $isapproved = 'pending';
        } elseif ($status === 'approved') {
            $isapproved = 'approve';
        } elseif ($status === 'canceled') {
            $isapproved = 'canceled';
        }

        $wsRequest = [
            'method' => 'approval',
            'action' => 'list',
            'isapproved' => $isapproved,
            'q' => $q,
            'tanggal_dari' => $tanggalDari,
            'tanggal_sampai' => $tanggalSampai,
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
                'tanggal_dari' => $tanggalDari,
                'tanggal_sampai' => $tanggalSampai,
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
        $items = $this->applyLocalFilters($items, $q, $tanggalDari, $tanggalSampai);

        $scopeSekolah = trim((string) ($payload['data']['scope_sekolah'] ?? ''));
        $isSuperadmin = strtolower(trim((string) session('user.role', ''))) === 'superadmin';
        if (! $isSuperadmin && $scopeSekolah === '' && $items !== []) {
            $scopeSekolah = trim((string) ($items[0]['sekolah'] ?? ''));
        }

        return [$items, null, $scopeSekolah];
    }

    private function applyLocalFilters(array $items, string $q, string $tanggalDari, string $tanggalSampai): array
    {
        if ($q === '' && $tanggalDari === '' && $tanggalSampai === '') {
            return $items;
        }

        $qLower = mb_strtolower($q);

        return array_values(array_filter($items, function (array $item) use ($qLower, $tanggalDari, $tanggalSampai) {
            if ($qLower !== '') {
                $nocust = mb_strtolower((string) ($item['nocust'] ?? ''));
                $nmcust = mb_strtolower((string) ($item['nmcust'] ?? ''));
                if (! str_contains($nocust, $qLower) && ! str_contains($nmcust, $qLower)) {
                    return false;
                }
            }

            $datePart = substr((string) ($item['created_at'] ?? ''), 0, 10);
            if ($tanggalDari !== '' && ($datePart === '' || $datePart < $tanggalDari)) {
                return false;
            }
            if ($tanggalSampai !== '' && ($datePart === '' || $datePart > $tanggalSampai)) {
                return false;
            }

            return true;
        }));
    }
}
