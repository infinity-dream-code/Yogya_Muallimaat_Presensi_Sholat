<?php

namespace App\Http\Controllers;

use App\Exports\TagihanExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringKepsekController extends Controller
{
    private const API_URL = 'http://103.23.103.43/ws_client/Yogya_Muallimaat_Kepsek_Monitoring/index.php';

    public function showTagihan()
    {
        if (session('user.app') !== 'monitoring-kepsek') {
            return redirect()->route('dashboard');
        }

        return view('tagihan_kepsek');
    }

    public function tagihanFilterOptions()
    {
        if (session('user.app') !== 'monitoring-kepsek') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $cacheKey = 'kepsek_tagihan_filters_' . md5((string) session('user.token'));

        $options = Cache::remember($cacheKey, 600, function () {
            $result = $this->callWs('getFilterTagihan');
            if (($result['status'] ?? 0) === 200) {
                return [
                    'bta'   => $result['data']['bta'] ?? [],
                    'kelas' => $result['data']['kelas'] ?? [],
                ];
            }

            return ['bta' => [], 'kelas' => []];
        });

        return response()->json([
            'success' => true,
            'bta'     => $options['bta'] ?? [],
            'kelas'   => $options['kelas'] ?? [],
        ]);
    }

    public function tagihanSummary(Request $request)
    {
        if (session('user.app') !== 'monitoring-kepsek') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $summary = $this->fetchGrandTotal($request);

        return response()->json([
            'success'       => true,
            'total_rows'    => $summary['total_rows'],
            'total_jumlah'  => $summary['total_jumlah'],
        ]);
    }

    public function tagihanData(Request $request)
    {
        if (session('user.app') !== 'monitoring-kepsek') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $limit = $this->resolveLimit($request);
        $page = max((int) $request->query('page', 1), 1);
        $offset = ($page - 1) * $limit;
        $searchTerm = trim((string) $request->query('search', ''));

        $filterParams = $this->buildFilterParams($request);

        if ($searchTerm !== '') {
            $sortDir = $this->resolveSortDir($request);
            $matched = $this->sortRowsByFurutan(
                $this->fetchMatchingTagihanRows($filterParams, $searchTerm),
                $sortDir
            );
            $slice = array_slice($matched, $offset, $limit + 1);
            $hasMore = count($slice) > $limit;
            if ($hasMore) {
                array_pop($slice);
            }

            return response()->json([
                'success'    => true,
                'data'       => $slice,
                'pagination' => [
                    'page'     => $page,
                    'limit'    => $limit,
                    'offset'   => $offset,
                    'has_more' => $hasMore,
                    'from'     => count($slice) ? $offset + 1 : 0,
                    'to'       => $offset + count($slice),
                ],
            ]);
        }

        $pageParams = array_merge($filterParams, [
            'limit'    => $limit + 1,
            'offset'   => $offset,
            'sort'     => 'furutan',
            'sort_dir' => $this->resolveSortDir($request),
        ]);

        $cacheKey = 'kepsek_page_' . md5(json_encode($pageParams) . (string) session('user.token'));

        $result = Cache::get($cacheKey);
        if ($result === null) {
            $result = $this->callWs('getDataTagihan', $pageParams, 15);
            if (($result['status'] ?? 0) === 200) {
                Cache::put($cacheKey, $result, 300);
            }
        }

        if (($result['status'] ?? 0) !== 200) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Gagal mengambil data tagihan',
            ], 422);
        }

        $rows = $result['data'] ?? [];
        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            array_pop($rows);
        }

        return response()->json([
            'success'    => true,
            'data'       => $rows,
            'pagination' => [
                'page'     => $page,
                'limit'    => $limit,
                'offset'   => $offset,
                'has_more' => $hasMore,
                'from'     => count($rows) ? $offset + 1 : 0,
                'to'       => $offset + count($rows),
            ],
        ]);
    }

    public function tagihanDetail(Request $request)
    {
        if (session('user.app') !== 'monitoring-kepsek') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $validated = $request->validate([
            'custid'       => ['required', 'string'],
            'kode_tagihan' => ['required', 'string'],
        ]);

        $custid = trim($validated['custid']);
        $billcd = trim($validated['kode_tagihan']);
        $cacheKey = 'kepsek_detail_' . md5($custid . '|' . $billcd);

        $result = Cache::remember($cacheKey, 3600, function () use ($custid, $billcd) {
            return $this->callWs('getDetailTagihan', [
                'custid'       => $custid,
                'kode_tagihan' => $billcd,
            ], 12);
        });

        if (($result['status'] ?? 0) !== 200) {
            return response()->json([
                'success' => false,
                'message' => $this->friendlyDetailMessage($result['message'] ?? 'Gagal mengambil rincian tagihan'),
            ], 422);
        }

        $data = $result['data'] ?? ['header' => null, 'detail' => []];
        $details = $data['detail'] ?? [];

        if (empty($details)) {
            return response()->json([
                'success' => false,
                'message' => 'Rincian tagihan tidak ditemukan',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function exportTagihanExcel(Request $request)
    {
        if (session('user.app') !== 'monitoring-kepsek') {
            return redirect()->route('dashboard');
        }

        $rows = $this->fetchTagihanForExport($request);
        if (empty($rows)) {
            return redirect()
                ->route('kepsek.tagihan')
                ->with('error', 'Tidak ada data untuk diexport.');
        }

        $filters = $this->filterLabels($request);
        $summary = $this->fetchGrandTotal($request);
        $filename = 'tagihan_kepsek_' . now('Asia/Jakarta')->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new TagihanExport($rows, $filters, $summary['total_jumlah']),
            $filename
        );
    }

    public function exportTagihanPdf(Request $request)
    {
        if (session('user.app') !== 'monitoring-kepsek') {
            return redirect()->route('dashboard');
        }

        $rows = $this->fetchTagihanForExport($request);
        if (empty($rows)) {
            return redirect()
                ->route('kepsek.tagihan')
                ->with('error', 'Tidak ada data untuk diexport.');
        }

        $filters = $this->filterLabels($request);
        $summary = $this->fetchGrandTotal($request);

        $pdf = Pdf::loadView('tagihan_kepsek_pdf', [
            'rows'         => $rows,
            'filters'      => $filters,
            'totalJumlah'  => $summary['total_jumlah'],
        ])->setPaper('a4', 'landscape');

        $filename = 'tagihan_kepsek_' . now('Asia/Jakarta')->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }

    private function fetchGrandTotal(Request $request): array
    {
        $params = $this->buildFilterParams($request);
        $searchTerm = trim((string) $request->query('search', ''));
        $cacheKey = 'kepsek_grand_total_' . md5(
            json_encode($params) . '|' . $searchTerm . '|' . (string) session('user.token') . '|' . (string) session('user.code01')
        );

        return Cache::remember($cacheKey, 300, function () use ($params, $searchTerm) {
            $result = $this->callWs('getSummaryTagihan', $params, 45);
            $parsed = $this->parseSummaryResult($result);
            if ($parsed !== null) {
                return $parsed;
            }

            Log::warning('Monitoring Kepsek summary fallback', [
                'status'  => $result['status'] ?? null,
                'message' => $result['message'] ?? null,
                'code01'  => session('user.code01'),
            ]);

            if ($searchTerm !== '') {
                $rows = $this->fetchMatchingTagihanRows($params, $searchTerm);
                $totalJumlah = 0;
                foreach ($rows as $row) {
                    $totalJumlah += (float) ($row['jumlah'] ?? 0);
                }

                return [
                    'total_rows'   => count($rows),
                    'total_jumlah' => $totalJumlah,
                ];
            }

            return $this->fetchGrandTotalFallback($params);
        });
    }

    private function fetchGrandTotalFallback(array $params): array
    {
        $totalJumlah = 0;
        $totalRows = 0;
        $offset = 0;
        $limit = 500;

        do {
            $batch = $this->callWs('getDataTagihan', array_merge($params, [
                'limit'  => $limit,
                'offset' => $offset,
            ]), 25);
            $rows = ($batch['status'] ?? 0) === 200 ? ($batch['data'] ?? []) : [];
            foreach ($rows as $row) {
                $totalJumlah += (float) ($row['jumlah'] ?? 0);
                $totalRows++;
            }
            $offset += $limit;
        } while (count($rows) === $limit);

        return [
            'total_rows'   => $totalRows,
            'total_jumlah' => $totalJumlah,
        ];
    }

    private function parseSummaryResult(?array $result): ?array
    {
        if (($result['status'] ?? 0) === 200 && isset($result['data'])) {
            return [
                'total_rows'   => (int) ($result['data']['total_rows'] ?? 0),
                'total_jumlah' => (float) ($result['data']['total_jumlah'] ?? 0),
            ];
        }

        return null;
    }

    /**
     * @param  array<string, array{method: string, params: array}>  $calls
     * @return array<string, array>
     */
    private function callWsPool(array $calls): array
    {
        $token = session('user.token');
        if (! $token || empty($calls)) {
            return [];
        }

        try {
            $responses = Http::pool(function ($pool) use ($calls, $token) {
                foreach ($calls as $key => $call) {
                    $body = array_merge(
                        ['method' => $call['method'], 'token' => $token],
                        $call['params']
                    );

                    $pool->as($key)
                        ->connectTimeout(5)
                        ->timeout(20)
                        ->acceptJson()
                        ->asJson()
                        ->post(self::API_URL, $body);
                }
            });

            $parsed = [];
            foreach ($calls as $key => $call) {
                $response = $responses[$key] ?? null;
                if ($response instanceof \Illuminate\Http\Client\Response) {
                    $json = $response->json();
                    $parsed[$key] = is_array($json) ? $json : [
                        'status'  => $response->status(),
                        'message' => 'Respons server tidak valid',
                    ];
                } else {
                    $parsed[$key] = ['status' => 500, 'message' => 'Tidak dapat terhubung ke server'];
                }
            }

            return $parsed;
        } catch (\Throwable $e) {
            Log::error('Monitoring Kepsek WS pool error', ['message' => $e->getMessage()]);

            return [];
        }
    }

    private function fetchTagihanForExport(Request $request): array
    {
        $filterParams = $this->buildFilterParams($request);
        $searchTerm = trim((string) $request->query('search', ''));

        if ($searchTerm !== '') {
            return $this->fetchMatchingTagihanRows($filterParams, $searchTerm);
        }

        $params = array_merge($filterParams, [
            'limit'  => 5000,
            'offset' => 0,
        ]);

        $result = $this->callWs('getDataTagihan', $params);

        return ($result['status'] ?? 0) === 200 ? ($result['data'] ?? []) : [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function filterRowsBySearch(array $rows, string $search): array
    {
        $search = trim($search);
        if ($search === '') {
            return $rows;
        }

        if (preg_match('/^\d+$/', $search)) {
            return array_values(array_filter($rows, function ($row) use ($search) {
                $nis = (string) ($row['nis'] ?? '');
                $noPend = (string) ($row['no_pend'] ?? '');

                return $nis === $search
                    || str_contains($nis, $search)
                    || str_contains($noPend, $search);
            }));
        }

        $term = mb_strtolower($search);

        return array_values(array_filter($rows, function ($row) use ($term) {
            $nama = mb_strtolower((string) ($row['nama'] ?? ''));
            $nis = mb_strtolower((string) ($row['nis'] ?? ''));

            return str_contains($nama, $term) || str_contains($nis, $term);
        }));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function wsRowsMatchSearch(array $rows, string $searchTerm): bool
    {
        if ($rows === []) {
            return true;
        }

        foreach ($rows as $row) {
            if ($this->filterRowsBySearch([$row], $searchTerm) === []) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchMatchingTagihanRows(array $filterParams, string $searchTerm): array
    {
        $cacheKey = 'kepsek_matched_' . md5(
            json_encode($filterParams) . '|' . $searchTerm . '|' . (string) session('user.token')
        );

        return Cache::remember($cacheKey, 300, function () use ($filterParams, $searchTerm) {
            $tryParams = array_merge($filterParams, ['limit' => 5000, 'offset' => 0]);
            $result = $this->callWs('getDataTagihan', $tryParams, 25);

            if (($result['status'] ?? 0) === 200) {
                $rows = $result['data'] ?? [];
                if ($rows !== [] && $this->wsRowsMatchSearch($rows, $searchTerm)) {
                    return $this->filterRowsBySearch($rows, $searchTerm);
                }
            }

            $baseFilters = $filterParams;
            unset($baseFilters['nis'], $baseFilters['search']);

            $matched = [];
            $offset = 0;
            $batchLimit = 500;

            do {
                $batch = $this->callWs('getDataTagihan', array_merge($baseFilters, [
                    'limit'  => $batchLimit,
                    'offset' => $offset,
                ]), 25);

                $rows = ($batch['status'] ?? 0) === 200 ? ($batch['data'] ?? []) : [];
                if ($rows === []) {
                    break;
                }

                foreach ($this->filterRowsBySearch($rows, $searchTerm) as $row) {
                    $matched[] = $row;
                }

                $offset += $batchLimit;
            } while (count($rows) === $batchLimit);

            return $matched;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function sortRowsByFurutan(array $rows, string $dir = 'asc'): array
    {
        $desc = strtolower($dir) === 'desc';

        usort($rows, function ($a, $b) use ($desc) {
            $cmp = ((int) ($a['furutan'] ?? 0)) <=> ((int) ($b['furutan'] ?? 0));
            if ($cmp === 0) {
                $cmp = strcmp((string) ($a['nis'] ?? ''), (string) ($b['nis'] ?? ''));
            }

            return $desc ? -$cmp : $cmp;
        });

        return array_values($rows);
    }

    private function resolveSortDir(Request $request): string
    {
        $dir = strtolower((string) $request->query('sort_dir', 'asc'));

        return in_array($dir, ['asc', 'desc'], true) ? $dir : 'asc';
    }

    private function friendlyDetailMessage(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return 'Gagal mengambil rincian tagihan';
        }

        if (preg_match('/scctbill|SQLSTATE|PDO|HY093/i', $message)) {
            return 'Rincian tagihan tidak ditemukan';
        }

        return $message;
    }

    private function resolveLimit(Request $request): int
    {
        $limit = (int) $request->query('limit', 10);

        return in_array($limit, [10, 25, 50, 100], true) ? $limit : 10;
    }

    private function buildFilterParams(Request $request): array
    {
        $params = [];

        $bta = trim((string) $request->query('bta', ''));
        if ($bta !== '') {
            $params['bta'] = $bta;
        }

        $paidst = $request->query('paidst', '');
        if ($paidst !== '' && $paidst !== null) {
            $params['paidst'] = (int) $paidst;
        }

        $kelas = trim((string) $request->query('kelas', ''));
        if ($kelas !== '') {
            $params['kelas'] = $kelas;
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $params['search'] = $search;
            if (preg_match('/^\d+$/', $search)) {
                $params['nis'] = $search;
            }
        }

        return $params;
    }

    private function filterLabels(Request $request): array
    {
        $paidst = $request->query('paidst', '');
        $statusLabel = 'Semua';
        if ($paidst === '1') {
            $statusLabel = 'Lunas';
        } elseif ($paidst === '0') {
            $statusLabel = 'Belum Lunas';
        }

        return [
            'bta'    => trim((string) $request->query('bta', '')) ?: 'Semua',
            'kelas'  => trim((string) $request->query('kelas', '')) ?: 'Semua',
            'status' => $statusLabel,
            'search' => trim((string) $request->query('search', '')) ?: '-',
        ];
    }

    private function withSessionContext(array $params): array
    {
        $code01 = session('user.code01');
        if ($code01 !== null && $code01 !== '' && ! isset($params['code01'])) {
            $params['code01'] = (string) $code01;
        }

        return $params;
    }

    private function callWs(string $method, array $params = [], int $timeout = 20): array
    {
        $token = session('user.token');
        if (! $token) {
            return ['status' => 401, 'message' => 'Session tidak valid. Silakan login kembali.'];
        }

        $body = array_merge(['method' => $method, 'token' => $token], $this->withSessionContext($params));

        try {
            $response = Http::connectTimeout(5)
                ->timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->post(self::API_URL, $body);

            $json = $response->json();
            if (is_array($json)) {
                return $json;
            }

            return [
                'status'  => $response->status(),
                'message' => 'Respons server tidak valid',
            ];
        } catch (\Throwable $e) {
            Log::error('Monitoring Kepsek WS error', [
                'method'  => $method,
                'message' => $e->getMessage(),
            ]);

            return ['status' => 500, 'message' => 'Tidak dapat terhubung ke server'];
        }
    }
}
