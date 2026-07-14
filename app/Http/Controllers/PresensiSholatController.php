<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RekapSholatExport;

class PresensiSholatController extends Controller
{
    private const API_BASE_URL_PRESENSI_SHOLAT = 'http://vps1.smartpayment.co.id:8888/Data/Yogya_Muallimaat_PresensiSholat/WebAPI.php';
    private const JWT_SECRET = 'a7c2a8a9b3c4a5a6a7a8a9b0c1a2a3';

    public function showQr()
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('dashboard');
        }

        return view('presensi_sholat_qr');
    }

    public function showHaidQr()
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('dashboard');
        }

        return view('presensi_haid_qr');
    }

    public function postSholat(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return response()->json([
                'ok' => false,
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $validated = $request->validate([
            'nokartu' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $username = session('user.username');
        if (!$username) {
            return response()->json([
                'ok' => false,
                'message' => 'Session tidak valid. Silakan login kembali.',
            ], 401);
        }

        $payload = [
            'METHOD'  => 'POSTSholat',
            'NOKARTU' => $validated['nokartu'],
            'USERNAME' => $username,
        ];

        $token = $this->generateJwt($payload);

        try {
            Log::info('POSTSholat request', [
                'username' => $username,
                'nokartu' => $validated['nokartu'],
                'payload' => $payload,
                'token_preview' => substr($token, 0, 40) . '...',
            ]);

            $response = Http::timeout(15)
                ->get(self::API_BASE_URL_PRESENSI_SHOLAT . '?token=' . urlencode($token));
        } catch (\Throwable $e) {
            Log::error('POSTSholat error calling external API', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Tidak dapat terhubung ke server. Silakan coba lagi.',
            ], 502);
        }

        if (! $response->ok()) {
            Log::warning('POSTSholat non-200 response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return response()->json([
                'ok' => false,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi.',
            ], 502);
        }

        $data = $response->json();

        Log::info('POSTSholat API response parsed', [
            'status' => $response->status(),
            'data' => $data,
        ]);

        // Format respon Presensi Sholat berbeda dengan perizinan.
        // Contoh: [{"STATUS":"NOTOK","RESULT":"TELAH_MELAKUKAN_PRESENSI","RES":"PRESENSI TELAH DILAKUKAN"}]
        if (is_array($data) && isset($data[0])) {
            $item = $data[0];
            $statusFlag = $item['STATUS'] ?? null;
            $resultCode = $item['RESULT'] ?? null;
            $resMessage = $item['RES'] ?? null;

            if ($statusFlag === 'OK') {
                return response()->json([
                    'ok' => true,
                    'message' => $resMessage ?: 'Presensi sholat berhasil.',
                    'data' => $data,
                ]);
            }

            // Khusus jika sudah pernah presensi, beri pesan yang jelas
            if ($statusFlag === 'NOTOK' && $resultCode === 'TELAH_MELAKUKAN_PRESENSI') {
                return response()->json([
                    'ok' => false,
                    'message' => $resMessage ?: 'Presensi sudah pernah dilakukan untuk kartu ini.',
                    'data' => $data,
                ], 422);
            }

            return response()->json([
                'ok' => false,
                'message' => $resMessage ?: 'Presensi sholat gagal.',
                'data' => $data,
            ], 422);
        }

        // Fallback untuk format lain
        if (isset($data['KodeRespon']) && (int) $data['KodeRespon'] === 1) {
            return response()->json([
                'ok' => true,
                'message' => $data['PesanRespon'] ?? 'Presensi sholat berhasil.',
                'data' => $data,
            ]);
        }

        return response()->json([
            'ok' => false,
            'message' => $data['PesanRespon'] ?? 'Presensi sholat gagal.',
            'data' => $data,
        ], 422);
    }

    public function postHaid(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return response()->json([
                'ok' => false,
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $validated = $request->validate([
            'nokartu' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $username = session('user.username');
        if (!$username) {
            return response()->json([
                'ok' => false,
                'message' => 'Session tidak valid. Silakan login kembali.',
            ], 401);
        }

        $payload = [
            'METHOD'  => 'POSTHaid',
            'NOKARTU' => $validated['nokartu'],
            'USERNAME' => $username,
        ];

        $token = $this->generateJwt($payload);

        try {
            Log::info('POSTHaid request', [
                'username' => $username,
                'nokartu' => $validated['nokartu'],
                'payload' => $payload,
                'token_preview' => substr($token, 0, 40) . '...',
            ]);

            $response = Http::timeout(15)
                ->get(self::API_BASE_URL_PRESENSI_SHOLAT . '?token=' . urlencode($token));
        } catch (\Throwable $e) {
            Log::error('POSTHaid error calling external API', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Tidak dapat terhubung ke server. Silakan coba lagi.',
            ], 502);
        }

        if (! $response->ok()) {
            Log::warning('POSTHaid non-200 response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return response()->json([
                'ok' => false,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi.',
            ], 502);
        }

        $data = $response->json();

        Log::info('POSTHaid API response parsed', [
            'status' => $response->status(),
            'data' => $data,
        ]);

        if (is_array($data) && isset($data[0])) {
            $item = $data[0];
            $statusFlag = $item['STATUS'] ?? null;
            $resultCode = $item['RESULT'] ?? null;
            $resMessage = $item['RES'] ?? null;

            if ($statusFlag === 'OK') {
                return response()->json([
                    'ok' => true,
                    'message' => $resMessage ?: 'Presensi haid berhasil.',
                    'data' => $data,
                ]);
            }

            if ($statusFlag === 'NOTOK' && $resultCode === 'TELAH_MELAKUKAN_PRESENSI') {
                return response()->json([
                    'ok' => false,
                    'message' => $resMessage ?: 'Presensi haid sudah pernah dilakukan untuk kartu ini.',
                    'data' => $data,
                ], 422);
            }

            return response()->json([
                'ok' => false,
                'message' => $resMessage ?: 'Presensi haid gagal.',
                'data' => $data,
            ], 422);
        }

        if (isset($data['KodeRespon']) && (int) $data['KodeRespon'] === 1) {
            return response()->json([
                'ok' => true,
                'message' => $data['PesanRespon'] ?? 'Presensi haid berhasil.',
                'data' => $data,
            ]);
        }

        return response()->json([
            'ok' => false,
            'message' => $data['PesanRespon'] ?? 'Presensi haid gagal.',
            'data' => $data,
        ], 422);
    }

    public function showLogMarifah(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('dashboard');
        }

        $username = session('user.username');
        if (! $username) {
            return redirect()->route('login.form');
        }

        $tanggal = $request->query('tanggal');
        $today   = now()->format('Y-m-d');

        if (! $tanggal) {
            $tanggal = $today;
        }

        // Jika tanggal = hari ini pakai LogMarifahTodayRequest, selain itu LogMarifahRequest
        if ($tanggal === $today) {
            $payload = [
                'METHOD'   => 'LogMarifahTodayRequest',
                'USERNAME' => $username,
            ];
        } else {
            $payload = [
                'METHOD'   => 'LogMarifahRequest',
                'USERNAME' => $username,
                'HARIOUT'  => $tanggal,
            ];
        }

        $token = $this->generateJwt($payload);

        $entries = [];
        $error   = null;

        try {
            Log::info('LogMarifah request', [
                'username' => $username,
                'tanggal'  => $tanggal,
                'payload'  => $payload,
            ]);

            $response = Http::timeout(15)
                ->get(self::API_BASE_URL_PRESENSI_SHOLAT . '?token=' . urlencode($token));

            if ($response->ok()) {
                $data = $response->json();
                Log::info('LogMarifah API response', [
                    'status' => $response->status(),
                    'data' => $data,
                ]);

                if (is_array($data)) {
                    // API mengembalikan { "datas": [ ... ] }
                    if (isset($data['datas']) && is_array($data['datas'])) {
                        $entries = $data['datas'];
                    } else {
                        $entries = $data;
                    }
                }
            } else {
                $error = 'Terjadi kesalahan pada server (HTTP ' . $response->status() . ').';
            }
        } catch (\Throwable $e) {
            Log::error('LogMarifah error', [
                'message' => $e->getMessage(),
            ]);
            $error = 'Tidak dapat terhubung ke server. Silakan coba lagi.';
        }

        return view('log_marifah', [
            'tanggal' => $tanggal,
            'today'   => $today,
            'entries' => $entries,
            'error'   => $error,
        ]);
    }

    public function showLogPresensi(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('dashboard');
        }

        $username = session('user.username');
        if (! $username) {
            return redirect()->route('login.form');
        }

        $tanggal = $request->query('tanggal');
        $today   = now()->format('Y-m-d');

        if (! $tanggal) {
            $tanggal = $today;
        }

        if ($tanggal === $today) {
            $payload = [
                'METHOD'   => 'LogPresensiTodayRequest',
                'USERNAME' => $username,
            ];
        } else {
            $payload = [
                'METHOD'   => 'LogPresensiRequest',
                'USERNAME' => $username,
                'HARIOUT'  => $tanggal,
            ];
        }

        $token = $this->generateJwt($payload);

        $entries = [];
        $error   = null;

        try {
            Log::info('LogPresensi request', [
                'username' => $username,
                'tanggal'  => $tanggal,
                'payload'  => $payload,
            ]);

            $response = Http::timeout(15)
                ->get(self::API_BASE_URL_PRESENSI_SHOLAT . '?token=' . urlencode($token));

            if ($response->ok()) {
                $data = $response->json();

                if (is_array($data)) {
                    if (isset($data['datas']) && is_array($data['datas'])) {
                        $entries = $data['datas'];
                    } else {
                        $entries = $data;
                    }
                }
            } else {
                $error = 'Terjadi kesalahan pada server (HTTP ' . $response->status() . ').';
            }
        } catch (\Throwable $e) {
            Log::error('LogPresensi error', ['message' => $e->getMessage()]);
            $error = 'Tidak dapat terhubung ke server. Silakan coba lagi.';
        }

        return view('log_presensi', [
            'tanggal' => $tanggal,
            'today'   => $today,
            'entries' => $entries,
            'error'   => $error,
        ]);
    }

    public function showKelolaPresensi(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('dashboard');
        }

        $username = session('user.username');
        if (! $username) {
            return redirect()->route('login.form');
        }

        $tanggal = $request->query('tanggal');
        $today   = now()->format('Y-m-d');

        if (! $tanggal) {
            $tanggal = $today;
        }

        return view('kelola_presensi', [
            'tanggal' => $tanggal,
            'today'   => $today,
            'entries' => [],
            'error'   => null,
        ]);
    }

    public function kelolaPresensiData(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return response('', 403);
        }

        $username = session('user.username');
        if (! $username) {
            return response('', 401);
        }

        $tanggal = $request->query('tanggal', now()->format('Y-m-d'));
        $today     = now()->format('Y-m-d');
        $page      = max(1, (int) $request->query('page', 1));
        $perPage   = min(100, max(20, (int) $request->query('per_page', 50)));
        $search    = trim((string) $request->query('search', ''));
        $unit      = trim((string) $request->query('unit', ''));
        $musyrifah = trim((string) $request->query('musyrifah', ''));
        $sort      = trim((string) $request->query('sort', 'unit_asc'));

        $cacheKey = 'kelola_presensi_' . $username . '_' . $tanggal;
        $all      = \Illuminate\Support\Facades\Cache::remember($cacheKey, 180, function () use ($username, $tanggal, $today) {
            if ($tanggal === $today) {
                $payload = [
                    'METHOD'   => 'DetailPresensiRequestToday',
                    'USERNAME' => $username,
                ];
            } else {
                $payload = [
                    'METHOD'   => 'DetailPresensiRequest',
                    'USERNAME' => $username,
                    'HARIOUT'  => $tanggal,
                ];
            }

            $token = $this->generateJwt($payload);
            $list  = [];

            try {
                $response = Http::timeout(20)
                    ->get(self::API_BASE_URL_PRESENSI_SHOLAT . '?token=' . urlencode($token));

                if ($response->ok()) {
                    $data = $response->json();
                    if (is_array($data)) {
                        $list = $data['datas'] ?? $data;
                        if (! is_array($list)) {
                            $list = [];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error('KelolaPresensi data error', ['message' => $e->getMessage()]);
            }

            return $list;
        });

        // Simpan list unit & musyrifah dari seluruh data (untuk filter dropdown)
        $allUnits = [];
        $allMus   = [];
        foreach ($all as $e) {
            $u = (string) ($e['UNIT'] ?? $e['Unit'] ?? '');
            if ($u !== '' && ! in_array($u, $allUnits, true)) {
                $allUnits[] = $u;
            }

            // Musyrifah: kolom khusus jika ada, lalu USER_1..5 selain "System"
            $m = $e['Musrifah'] ?? $e['MUSRIFAH'] ?? $e['Musftr'] ?? null;
            if ($m !== null && $m !== '' && strtoupper((string) $m) !== 'SYSTEM') {
                if (! in_array((string) $m, $allMus, true)) {
                    $allMus[] = (string) $m;
                }
            }
            for ($i = 1; $i <= 5; $i++) {
                $k = 'USER_' . $i;
                $v = $e[$k] ?? null;
                if ($v !== null && $v !== '' && strtoupper((string) $v) !== 'SYSTEM' && ! in_array((string) $v, $allMus, true)) {
                    $allMus[] = (string) $v;
                }
            }
        }

        if ($search !== '') {
            $q = mb_strtolower($search);
            $all = array_values(array_filter($all, function ($e) use ($q) {
                $nama = mb_strtolower($e['NamaCust'] ?? $e['NAMA'] ?? $e['NAMASISWA'] ?? $e['Nama'] ?? '');
                $nis  = mb_strtolower($e['NIS'] ?? $e['NOKARTU'] ?? '');

                return str_contains($nama, $q) || str_contains($nis, $q);
            }));
        }

        if ($unit !== '') {
            $u = mb_strtolower($unit);
            $all = array_values(array_filter($all, function ($e) use ($u) {
                $unitVal = mb_strtolower($e['UNIT'] ?? $e['Unit'] ?? '');
                return $unitVal === $u;
            }));
        }

        if ($musyrifah !== '') {
            $m = mb_strtolower($musyrifah);
            $all = array_values(array_filter($all, function ($e) use ($m) {
                $first = '';
                for ($i = 1; $i <= 5; $i++) {
                    $k = 'USER_' . $i;
                    $v = $e[$k] ?? null;
                    if ($v !== null && $v !== '') {
                        $first = (string) $v;
                        break;
                    }
                }
                return mb_strtolower($first) === $m;
            }));
        }

        usort($all, function ($a, $b) use ($sort) {
            $unitA = (string) ($a['UNIT'] ?? $a['Unit'] ?? '');
            $unitB = (string) ($b['UNIT'] ?? $b['Unit'] ?? '');
            $nameA = (string) ($a['NamaCust'] ?? $a['NAMA'] ?? $a['NAMASISWA'] ?? $a['Nama'] ?? '');
            $nameB = (string) ($b['NamaCust'] ?? $b['NAMA'] ?? $b['NAMASISWA'] ?? $b['Nama'] ?? '');

            $cmpUnit = strcasecmp($unitA, $unitB);
            $cmpName = strcasecmp($nameA, $nameB);

            if ($sort === 'unit_desc') {
                $cmpUnit *= -1;
            }

            return $cmpUnit !== 0 ? $cmpUnit : $cmpName;
        });

        $total   = count($all);
        $entries = array_slice($all, ($page - 1) * $perPage, $perPage);
        $hasMore = (($page - 1) * $perPage + count($entries)) < $total;

        sort($allUnits);
        sort($allMus);

        $html = view('kelola_presensi_list', [
            'entries'    => $entries,
            'tanggal'    => $tanggal,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'hasMore'    => $hasMore,
            'allUnits'   => $allUnits,
            'allMuslist' => $allMus,
        ])->render();

        return response($html, 200, [
            'Content-Type'     => 'text/html',
            'X-Total-Count'    => (string) $total,
            'X-Page'           => (string) $page,
            'X-Has-More'       => $hasMore ? '1' : '0',
        ]);
    }

    public function showRekapSholat(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('dashboard');
        }

        $username = session('user.username');
        if (! $username) {
            return redirect()->route('login.form');
        }

        $bulan = $request->query('bulan', '');

        return view('rekap_sholat', [
            'bulan' => $bulan,
        ]);
    }

    public function rekapSholatData(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return response('', 403);
        }

        $username = session('user.username');
        if (! $username) {
            return response('', 401);
        }

        $bulan   = $request->query('bulan', now()->format('Y-m'));
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(20, (int) $request->query('per_page', 50)));
        $search  = trim((string) $request->query('search', ''));

        $all = $this->enrichRekapWithSekolah($this->fetchRekapSholatList($username, $bulan));
        $units = $this->collectRekapUnits($all);

        if ($search !== '') {
            $q   = mb_strtolower($search);
            $all = array_values(array_filter($all, function ($e) use ($q) {
                $nama = mb_strtolower($e['NamaCust'] ?? $e['NAMA'] ?? $e['NAMASISWA'] ?? $e['Nama'] ?? '');
                $nis  = mb_strtolower($e['NOCUST'] ?? $e['nocust'] ?? $e['NIS'] ?? $e['NOKARTU'] ?? $e['nis'] ?? '');

                return str_contains($nama, $q) || str_contains($nis, $q);
            }));
        }

        $total   = count($all);
        $entries = array_slice($all, ($page - 1) * $perPage, $perPage);
        $hasMore = (($page - 1) * $perPage + count($entries)) < $total;

        $html = view('rekap_sholat_list', [
            'entries' => $entries,
            'bulan'   => $bulan,
            'page'    => $page,
            'total'   => $total,
            'hasMore' => $hasMore,
            'units'   => $page === 1 ? $units : [],
        ])->render();

        return response($html, 200, [
            'Content-Type'  => 'text/html',
            'X-Total-Count' => (string) $total,
            'X-Has-More'    => $hasMore ? '1' : '0',
        ]);
    }

    public function exportRekapSholatExcel(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('dashboard');
        }

        $username = session('user.username');
        if (! $username) {
            return redirect()->route('login.form');
        }

        $bulan = $request->query('bulan', now()->format('Y-m'));
        $all = $this->enrichRekapWithSekolah($this->fetchRekapSholatList($username, $bulan));

        if (empty($all)) {
            return redirect()
                ->route('presensi.rekap-sholat', ['bulan' => $bulan])
                ->with('error', 'Tidak ada data untuk diexport.');
        }

        $flat = $this->flattenRekapDatas($all);

        $filename = 'rekap_sholat_' . str_replace([':', ' '], '_', $bulan) . '.xlsx';

        return Excel::download(
            new RekapSholatExport($flat, $bulan),
            $filename
        );
    }

    public function exportRekapSholatPdf(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('dashboard');
        }

        $username = session('user.username');
        if (! $username) {
            return redirect()->route('login.form');
        }

        $bulan = $request->query('bulan', now()->format('Y-m'));
        $all = $this->enrichRekapWithSekolah($this->fetchRekapSholatList($username, $bulan));

        if (empty($all)) {
            return redirect()
                ->route('presensi.rekap-sholat', ['bulan' => $bulan])
                ->with('error', 'Tidak ada data untuk diexport.');
        }

        $pdf = Pdf::loadView('rekap_sholat_pdf', [
            'entries' => $all,
            'bulan'   => $bulan,
        ])->setPaper('a4', 'landscape');

        $filename = 'rekap_sholat_' . str_replace([':', ' '], '_', $bulan) . '.pdf';

        return $pdf->download($filename);
    }

    public function updatePresensi(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return back()->with('error', 'Akses ditolak.');
        }

        $username = session('user.username');
        if (! $username) {
            return back()->with('error', 'Session tidak valid. Silakan login kembali.');
        }

        $validated = $request->validate([
            'id'      => ['required', 'string'],
            'session' => ['required', 'string', 'in:1,2,3,4,5'],
            'status'  => ['required', 'string', 'in:Alpa,Izin,Sholat,Haid,Sakit'],
        ]);

        $statusToCode = [
            'Alpa'   => 'A',
            'Izin'   => 'I',
            'Sholat' => 'SH',
            'Haid'   => 'H',
            'Sakit'  => 'SK',
        ];
        $izinCode = $statusToCode[$validated['status']] ?? $validated['status'];

        $payload = [
            'METHOD'   => 'UpdatePresensiRequest',
            'ID'       => $validated['id'],
            'SESSION'  => $validated['session'],
            'USERNAME' => $username,
            'IZIN'     => $izinCode,
        ];

        $token = $this->generateJwt($payload);

        try {
            $response = Http::timeout(15)
                ->get(self::API_BASE_URL_PRESENSI_SHOLAT . '?token=' . urlencode($token));

            $data = $response->json();
            $item = (is_array($data) && isset($data[0])) ? $data[0] : (is_array($data) ? $data : []);
            $isOk = ($item['STATUS'] ?? '') === 'OK' || ((int) ($item['KodeRespon'] ?? 0)) === 1;

            if ($response->ok() && $data !== null) {
                if ($isOk) {
                    $tanggal = $request->input('tanggal', now()->format('Y-m-d'));
                    $username = session('user.username');
                    if ($username) {
                        \Illuminate\Support\Facades\Cache::forget('kelola_presensi_' . $username . '_' . $tanggal);
                    }
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['ok' => true]);
                    }
                    return redirect()->route('presensi.kelola', ['tanggal' => $tanggal])
                        ->with('success', 'Presensi berhasil diupdate.');
                }
                $msg = $item['RES'] ?? $item['PesanRespon'] ?? $item['message'] ?? 'Update gagal.';
                Log::info('UpdatePresensi API returned non-OK', ['response' => $data, 'id' => $validated['id'], 'session' => $validated['session']]);
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['ok' => false, 'message' => $msg]);
                }
                return back()->with('error', $msg);
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Response tidak valid dari server.']);
            }
            $tanggal = $request->input('tanggal', now()->format('Y-m-d'));
            return redirect()->route('presensi.kelola', ['tanggal' => $tanggal]);
        } catch (\Throwable $e) {
            Log::error('UpdatePresensi error', ['message' => $e->getMessage()]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Tidak dapat terhubung ke server.']);
            }
            return back()->with('error', 'Tidak dapat terhubung ke server.');
        }
    }

    public function exportLogPresensiExcel(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('dashboard');
        }

        $username = session('user.username');
        if (! $username) {
            return redirect()->route('login.form');
        }

        $tanggal = $request->query('tanggal');
        $today   = now()->format('Y-m-d');
        if (! $tanggal) {
            $tanggal = $today;
        }

        if ($tanggal === $today) {
            $payload = [
                'METHOD'   => 'LogPresensiTodayRequest',
                'USERNAME' => $username,
            ];
        } else {
            $payload = [
                'METHOD'   => 'LogPresensiRequest',
                'USERNAME' => $username,
                'HARIOUT'  => $tanggal,
            ];
        }

        $token = $this->generateJwt($payload);
        $entries = [];

        try {
            $response = Http::timeout(20)
                ->get(self::API_BASE_URL_PRESENSI_SHOLAT . '?token=' . urlencode($token));

            if ($response->ok()) {
                $data = $response->json();
                if (is_array($data)) {
                    $entries = $data['datas'] ?? $data;
                    if (! is_array($entries)) {
                        $entries = [];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('LogPresensi export error', ['message' => $e->getMessage()]);
        }

        if (empty($entries)) {
            return redirect()
                ->route('presensi.log-presensi', ['tanggal' => $tanggal])
                ->with('error', 'Tidak ada data untuk diexport.');
        }

        $filename = 'log_presensi_' . str_replace([':', ' '], '_', $tanggal) . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LogPresensiExport($entries, $tanggal),
            $filename,
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function exportLogPresensiPdf(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('dashboard');
        }

        $username = session('user.username');
        if (! $username) {
            return redirect()->route('login.form');
        }

        $tanggal = $request->query('tanggal');
        $today   = now()->format('Y-m-d');
        if (! $tanggal) {
            $tanggal = $today;
        }

        if ($tanggal === $today) {
            $payload = [
                'METHOD'   => 'LogPresensiTodayRequest',
                'USERNAME' => $username,
            ];
        } else {
            $payload = [
                'METHOD'   => 'LogPresensiRequest',
                'USERNAME' => $username,
                'HARIOUT'  => $tanggal,
            ];
        }

        $token   = $this->generateJwt($payload);
        $entries = [];

        try {
            $response = Http::timeout(20)
                ->get(self::API_BASE_URL_PRESENSI_SHOLAT . '?token=' . urlencode($token));

            if ($response->ok()) {
                $data = $response->json();
                if (is_array($data)) {
                    $entries = $data['datas'] ?? $data;
                    if (! is_array($entries)) {
                        $entries = [];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('LogPresensi pdf export error', ['message' => $e->getMessage()]);
        }

        if (empty($entries)) {
            return redirect()
                ->route('presensi.log-presensi', ['tanggal' => $tanggal])
                ->with('error', 'Tidak ada data untuk diexport.');
        }

        $pdf = Pdf::loadView('log_presensi_pdf', [
            'entries' => $entries,
            'tanggal' => $tanggal,
        ])->setPaper('a4', 'portrait');

        $filename = 'log_presensi_' . str_replace([':', ' '], '_', $tanggal) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Ratakan data rekap: bila API mengembalikan grup (datas/siswa), ambil tiap siswa.
     * Jangan masukkan record parent — hanya siswa dengan data per-waktu.
     */
    private function flattenRekapDatas(array $entries): array
    {
        $flat = [];
        foreach ($entries as $e) {
            if (! is_array($e)) {
                continue;
            }
            foreach (['datas', 'siswa', 'students'] as $key) {
                if (isset($e[$key]) && is_array($e[$key]) && count($e[$key]) > 0) {
                    foreach ($e[$key] as $s) {
                        if (is_array($s) && $this->isRekapStudentRecord($s)) {
                            $flat[] = $s;
                        }
                    }
                    continue 2;
                }
            }
            if (! isset($e['datas']) && $this->isRekapStudentRecord($e)) {
                $flat[] = $e;
            }
        }
        return $flat;
    }

    private function isRekapStudentRecord(array $e): bool
    {
        return isset($e['NamaCust']) || isset($e['NAMA']) || isset($e['NAMASISWA'])
            || isset($e['NOCUST']) || isset($e['nocust']) || isset($e['NIS']) || isset($e['NOKARTU']);
    }

    private function fetchRekapSholatList(string $username, string $bulan): array
    {
        $cacheKey = 'rekap_sholat_' . $username . '_' . $bulan;

        return Cache::remember($cacheKey, 180, function () use ($username, $bulan) {
            $payload = [
                'METHOD' => 'RekapRequest',
                'USERNAME' => $username,
                'BULAN' => $bulan,
            ];

            $token = $this->generateJwt($payload);
            $list = [];

            try {
                $response = Http::timeout(25)
                    ->get(self::API_BASE_URL_PRESENSI_SHOLAT . '?token=' . urlencode($token));

                if ($response->ok()) {
                    $data = $response->json();
                    if (is_array($data)) {
                        $list = $data['datas'] ?? $data;
                        if (! is_array($list)) {
                            $list = [];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error('RekapSholat data error', ['message' => $e->getMessage()]);
            }

            return $list;
        });
    }

    /**
     * Samakan UNIT dengan mst_sekolah via CODE01 (scctcust), hilangkan duplikat case seperti MTS/MTs.
     */
    private function enrichRekapWithSekolah(array $entries): array
    {
        if ($entries === []) {
            return $entries;
        }

        $sekolahByCode = [];
        $sekolahByDesc = [];
        try {
            foreach (DB::table('mst_sekolah')->select('CODE01', 'DESC01')->get() as $row) {
                $code = trim((string) ($row->CODE01 ?? ''));
                $desc = trim((string) ($row->DESC01 ?? ''));
                if ($code === '') {
                    continue;
                }
                $sekolahByCode[$code] = $desc !== '' ? $desc : $code;
                if ($desc !== '') {
                    $sekolahByDesc[mb_strtoupper($desc)] = [
                        'code01' => $code,
                        'sekolah' => $desc,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('RekapSholat mst_sekolah lookup failed', ['message' => $e->getMessage()]);
        }

        $nocusts = [];
        foreach ($entries as $entry) {
            if (! is_array($entry) || ! $this->isRekapStudentRecord($entry)) {
                continue;
            }
            $nocust = trim((string) ($entry['NOCUST'] ?? $entry['nocust'] ?? $entry['NIS'] ?? $entry['nis'] ?? ''));
            if ($nocust !== '') {
                $nocusts[] = $nocust;
            }
        }
        $nocusts = array_values(array_unique($nocusts));

        $byNocust = [];
        if ($nocusts !== []) {
            try {
                foreach (array_chunk($nocusts, 500) as $chunk) {
                    $rows = DB::table('scctcust as sc')
                        ->leftJoin('mst_sekolah as ms', 'ms.CODE01', '=', 'sc.CODE01')
                        ->whereIn(DB::raw('TRIM(sc.NOCUST)'), $chunk)
                        ->selectRaw('TRIM(sc.NOCUST) as nocust, TRIM(sc.CODE01) as code01, TRIM(ms.DESC01) as sekolah')
                        ->get();

                    foreach ($rows as $row) {
                        $nocust = trim((string) ($row->nocust ?? ''));
                        $code01 = trim((string) ($row->code01 ?? ''));
                        if ($nocust === '' || $code01 === '') {
                            continue;
                        }
                        $sekolah = trim((string) ($row->sekolah ?? ''));
                        if ($sekolah === '' && isset($sekolahByCode[$code01])) {
                            $sekolah = $sekolahByCode[$code01];
                        }
                        $byNocust[$nocust] = [
                            'code01' => $code01,
                            'sekolah' => $sekolah !== '' ? $sekolah : $code01,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('RekapSholat scctcust lookup failed', ['message' => $e->getMessage()]);
            }
        }

        foreach ($entries as &$entry) {
            if (! is_array($entry) || ! $this->isRekapStudentRecord($entry)) {
                continue;
            }

            $nocust = trim((string) ($entry['NOCUST'] ?? $entry['nocust'] ?? $entry['NIS'] ?? $entry['nis'] ?? ''));
            $rawUnit = trim((string) ($entry['UNIT'] ?? $entry['Unit'] ?? $entry['unit'] ?? ''));
            $code01 = trim((string) ($entry['CODE01'] ?? $entry['code01'] ?? ''));
            $sekolah = '';

            if ($nocust !== '' && isset($byNocust[$nocust])) {
                $code01 = $byNocust[$nocust]['code01'];
                $sekolah = $byNocust[$nocust]['sekolah'];
            } elseif ($code01 !== '' && isset($sekolahByCode[$code01])) {
                $sekolah = $sekolahByCode[$code01];
            } elseif ($rawUnit !== '') {
                $key = mb_strtoupper($rawUnit);
                if (isset($sekolahByDesc[$key])) {
                    $code01 = $sekolahByDesc[$key]['code01'];
                    $sekolah = $sekolahByDesc[$key]['sekolah'];
                } elseif (isset($sekolahByCode[$rawUnit])) {
                    $code01 = $rawUnit;
                    $sekolah = $sekolahByCode[$rawUnit];
                }
            }

            if ($code01 === '' && $rawUnit !== '') {
                // Tetap dedupe case: pakai label upper sebagai nilai filter
                $code01 = 'UNIT:' . mb_strtoupper($rawUnit);
                $sekolah = mb_strtoupper($rawUnit);
            }

            if ($sekolah === '') {
                $sekolah = $rawUnit;
            }

            $entry['CODE01'] = $code01;
            $entry['UNIT'] = $sekolah;
            $entry['Unit'] = $sekolah;
            $entry['unit'] = $sekolah;
            $entry['SEKOLAH'] = $sekolah;
        }
        unset($entry);

        return $entries;
    }

    private function collectRekapUnits(array $entries): array
    {
        $units = [];
        foreach ($entries as $entry) {
            if (! is_array($entry) || ! $this->isRekapStudentRecord($entry)) {
                continue;
            }
            $code01 = trim((string) ($entry['CODE01'] ?? ''));
            $label = trim((string) ($entry['SEKOLAH'] ?? $entry['UNIT'] ?? ''));
            if ($code01 === '') {
                continue;
            }
            if (! isset($units[$code01])) {
                $units[$code01] = [
                    'code01' => $code01,
                    'label' => $label !== '' ? $label : $code01,
                ];
            }
        }

        $list = array_values($units);
        usort($list, fn ($a, $b) => strcasecmp($a['label'], $b['label']));

        return $list;
    }

    private function generateJwt(array $payload): string
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signingInput = $headerEncoded . '.' . $payloadEncoded;
        $signature = hash_hmac('sha256', $signingInput, self::JWT_SECRET, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return $signingInput . '.' . $signatureEncoded;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
