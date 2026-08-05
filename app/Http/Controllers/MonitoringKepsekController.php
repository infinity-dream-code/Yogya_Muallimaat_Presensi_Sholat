<?php

namespace App\Http\Controllers;

use App\Exports\TagihanExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringKepsekController extends Controller
{
    private const DB_CONNECTION = 'mysql';

    public function showTagihan()
    {
        if (session('user.app') !== 'monitoring-kepsek') {
            return redirect()->route('dashboard');
        }

        return view('tagihan_kepsek');
    }

    public function tagihanFilterOptions(Request $request)
    {
        if (session('user.app') !== 'monitoring-kepsek') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $cacheKey = 'kepsek_v2_filters_' . md5((string) session('user.token'));

        $options = Cache::remember($cacheKey, 600, function () {
            $sekolah = DB::connection(self::DB_CONNECTION)->table('scctcust')
                ->select('CODE01 as value', 'DESC01 as label')
                ->whereNotNull('CODE01')
                ->where('CODE01', '!=', '')
                ->groupBy('CODE01', 'DESC01')
                ->orderBy('DESC01')
                ->get();

            $bta = DB::connection(self::DB_CONNECTION)->table('scctbill')
                ->select('BTA')
                ->whereNotNull('BTA')
                ->where('BTA', '!=', '')
                ->groupBy('BTA')
                ->orderByDesc('BTA')
                ->pluck('BTA');

            $tagihan = DB::connection(self::DB_CONNECTION)->table('scctbill')
                ->select('BILLNM')
                ->whereNotNull('BILLNM')
                ->where('BILLNM', '!=', '')
                ->groupBy('BILLNM')
                ->orderBy('BILLNM')
                ->pluck('BILLNM');

            return [
                'sekolah' => $sekolah,
                'bta' => $bta,
                'tagihan' => $tagihan,
            ];
        });

        $kelas = collect();
        $sekolahFilter = trim((string) $request->query('sekolah', ''));
        if ($sekolahFilter !== '') {
            $kelasCacheKey = 'kepsek_v2_kelas_' . md5($sekolahFilter);
            $kelas = Cache::remember($kelasCacheKey, 600, function () use ($sekolahFilter) {
                return DB::connection(self::DB_CONNECTION)->table('scctcust')
                    ->select('CODE02 as value', 'DESC02 as label')
                    ->where('CODE01', $sekolahFilter)
                    ->whereNotNull('CODE02')
                    ->where('CODE02', '!=', '')
                    ->groupBy('CODE02', 'DESC02')
                    ->orderBy('DESC02')
                    ->get();
            });
        }

        return response()->json([
            'success' => true,
            'sekolah' => $options['sekolah'],
            'bta' => $options['bta'],
            'kelas' => $kelas,
            'tagihan' => $options['tagihan'],
        ]);
    }

    public function tagihanSiswa(Request $request)
    {
        if (session('user.app') !== 'monitoring-kepsek') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $limit = $this->resolveLimit($request);
        $page = max((int) $request->query('page', 1), 1);
        $offset = ($page - 1) * $limit;

        $grouped = $this->groupedStudentQuery($request);

        $totalRows = DB::connection(self::DB_CONNECTION)
            ->table(DB::raw('(' . $grouped->toSql() . ') as sub'))
            ->mergeBindings($grouped)
            ->count();

        $rows = $grouped
            ->orderBy('scctcust.NMCUST')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function ($row) {
                $total = (float) $row->total_tagihan;
                $bayar = (float) $row->total_terbayar;
                $sisa = $total - $bayar;

                return [
                    'custid' => $row->CUSTID,
                    'nama' => $row->NMCUST,
                    'sekolah' => $row->sekolah,
                    'kelas' => $row->kelas,
                    'gender' => $row->gender,
                    'total_tagihan' => $total,
                    'total_terbayar' => $bayar,
                    'sisa_tagihan' => $sisa,
                    'status_bayar' => $sisa <= 0 ? 1 : 0,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalRows,
                'last_page' => (int) max(ceil($totalRows / max($limit, 1)), 1),
                'from' => $totalRows ? $offset + 1 : 0,
                'to' => min($offset + $limit, $totalRows),
            ],
        ]);
    }

    public function tagihanSummary(Request $request)
    {
        if (session('user.app') !== 'monitoring-kepsek') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $grouped = $this->groupedStudentQuery($request)
            ->select('scctcust.CUSTID')
            ->selectRaw('SUM(scctbill.BILLAM) as total_tagihan')
            ->selectRaw("SUM(CASE WHEN scctbill.PAIDST = '1' THEN scctbill.BILLAM ELSE 0 END) as total_terbayar")
            ->groupBy('scctcust.CUSTID');

        $sums = DB::connection(self::DB_CONNECTION)
            ->table(DB::raw('(' . $grouped->toSql() . ') as sub'))
            ->mergeBindings($grouped)
            ->selectRaw('COUNT(*) as total_siswa, SUM(total_tagihan) as total_tagihan, SUM(total_terbayar) as total_terbayar')
            ->first();

        $totalTagihan = (float) ($sums->total_tagihan ?? 0);
        $totalTerbayar = (float) ($sums->total_terbayar ?? 0);

        return response()->json([
            'success' => true,
            'total_siswa' => (int) ($sums->total_siswa ?? 0),
            'total_tagihan' => $totalTagihan,
            'total_terbayar' => $totalTerbayar,
            'total_piutang' => $totalTagihan - $totalTerbayar,
        ]);
    }

    public function tagihanSiswaDetail(Request $request, $custid)
    {
        if (session('user.app') !== 'monitoring-kepsek') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $limit = $this->resolveLimit($request, 20);
        $page = max((int) $request->query('page', 1), 1);
        $offset = ($page - 1) * $limit;

        $base = DB::connection(self::DB_CONNECTION)->table('scctbill')
            ->where('CUSTID', $custid);
        $base = $this->applyBillFilters($base, $request);

        $grouped = (clone $base)
            ->select('BILLCD')
            ->selectRaw('MAX(BILLNM) as nama_tagihan')
            ->selectRaw('MAX(BTA) as bta')
            ->selectRaw('MAX(FTGLTagihan) as tanggal_tagihan')
            ->selectRaw('MAX(PAIDDT) as tanggal_bayar')
            ->selectRaw('MIN(FUrutan) as furutan')
            ->selectRaw('SUM(BILLAM) as total_tagihan')
            ->selectRaw("SUM(CASE WHEN PAIDST = '1' THEN BILLAM ELSE 0 END) as total_terbayar")
            ->selectRaw('MIN(PAIDST) as min_paidst')
            ->groupBy('BILLCD');

        $totalRows = DB::connection(self::DB_CONNECTION)
            ->table(DB::raw('(' . $grouped->toSql() . ') as sub'))
            ->mergeBindings($grouped)
            ->count();

        $rows = $grouped
            ->orderBy('furutan')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function ($row) {
                $total = (float) $row->total_tagihan;
                $bayar = (float) $row->total_terbayar;
                $lunas = $row->min_paidst === '1';

                return [
                    'billcd' => $row->BILLCD,
                    'nama_tagihan' => $row->nama_tagihan,
                    'bta' => $row->bta,
                    'tanggal_tagihan' => $row->tanggal_tagihan,
                    'tanggal_bayar' => $lunas ? $row->tanggal_bayar : null,
                    'total_tagihan' => $total,
                    'total_terbayar' => $bayar,
                    'sisa_tagihan' => $total - $bayar,
                    'status_bayar' => $lunas ? 1 : 0,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalRows,
                'last_page' => (int) max(ceil($totalRows / max($limit, 1)), 1),
                'from' => $totalRows ? $offset + 1 : 0,
                'to' => min($offset + $limit, $totalRows),
            ],
        ]);
    }

    public function tagihanKomponen(Request $request, $custid, $billcd)
    {
        if (session('user.app') !== 'monitoring-kepsek') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $cacheKey = 'kepsek_v2_komponen_' . md5($custid . '|' . $billcd);

        $rows = Cache::remember($cacheKey, 600, function () use ($custid, $billcd) {
            return DB::connection(self::DB_CONNECTION)->table('scctbill')
                ->where('CUSTID', $custid)
                ->where('BILLCD', $billcd)
                ->orderBy('AA')
                ->get(['BILLAC', 'BILLNM', 'BTA', 'BILLAM', 'PAIDST'])
                ->map(function ($row) {
                    return [
                        'kode_akun' => $row->BILLAC,
                        'nama_akun' => $row->BILLNM,
                        'bta' => $row->BTA,
                        'jumlah' => (float) $row->BILLAM,
                        'status_bayar' => $row->PAIDST === '1' ? 1 : 0,
                    ];
                });
        });

        if ($rows->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Rincian tagihan tidak ditemukan'], 422);
        }

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function exportTagihanExcel(Request $request)
    {
        if (session('user.app') !== 'monitoring-kepsek') {
            return redirect()->route('dashboard');
        }

        $rows = $this->fetchAllStudentRows($request);
        if (empty($rows)) {
            return redirect()->route('kepsek.tagihan')->with('error', 'Tidak ada data untuk diexport.');
        }

        $summary = $this->computeSummaryTotals($request);
        $filename = 'tagihan_kepsek_' . now('Asia/Jakarta')->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new TagihanExport($rows, $this->filterLabels($request), $summary),
            $filename
        );
    }

    public function exportTagihanPdf(Request $request)
    {
        if (session('user.app') !== 'monitoring-kepsek') {
            return redirect()->route('dashboard');
        }

        $rows = $this->fetchAllStudentRows($request);
        if (empty($rows)) {
            return redirect()->route('kepsek.tagihan')->with('error', 'Tidak ada data untuk diexport.');
        }

        $filters = $this->filterLabels($request);
        $summary = $this->computeSummaryTotals($request);

        $pdf = Pdf::loadView('tagihan_kepsek_pdf', [
            'rows' => $rows,
            'filters' => $filters,
            'summary' => $summary,
        ])->setPaper('a4', 'landscape');

        $filename = 'tagihan_kepsek_' . now('Asia/Jakarta')->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }

    private function fetchAllStudentRows(Request $request, int $cap = 5000): array
    {
        $grouped = $this->groupedStudentQuery($request)
            ->orderBy('scctcust.NMCUST')
            ->limit($cap);

        return $grouped->get()->map(function ($row) {
            $total = (float) $row->total_tagihan;
            $bayar = (float) $row->total_terbayar;

            return [
                'custid' => $row->CUSTID,
                'nama' => $row->NMCUST,
                'sekolah' => $row->sekolah,
                'kelas' => $row->kelas,
                'gender' => $row->gender,
                'total_tagihan' => $total,
                'total_terbayar' => $bayar,
                'sisa_tagihan' => $total - $bayar,
                'status_bayar' => ($total - $bayar) <= 0 ? 1 : 0,
            ];
        })->toArray();
    }

    private function computeSummaryTotals(Request $request): array
    {
        $grouped = $this->groupedStudentQuery($request)
            ->select('scctcust.CUSTID')
            ->selectRaw('SUM(scctbill.BILLAM) as total_tagihan')
            ->selectRaw("SUM(CASE WHEN scctbill.PAIDST = '1' THEN scctbill.BILLAM ELSE 0 END) as total_terbayar")
            ->groupBy('scctcust.CUSTID');

        $sums = DB::connection(self::DB_CONNECTION)
            ->table(DB::raw('(' . $grouped->toSql() . ') as sub'))
            ->mergeBindings($grouped)
            ->selectRaw('SUM(total_tagihan) as total_tagihan, SUM(total_terbayar) as total_terbayar')
            ->first();

        $totalTagihan = (float) ($sums->total_tagihan ?? 0);
        $totalTerbayar = (float) ($sums->total_terbayar ?? 0);

        return [
            'total_tagihan' => $totalTagihan,
            'total_terbayar' => $totalTerbayar,
            'total_piutang' => $totalTagihan - $totalTerbayar,
        ];
    }

    private function groupedStudentQuery(Request $request)
    {
        $status = $request->query('status', '');

        $base = DB::connection(self::DB_CONNECTION)->table('scctcust')
            ->join('scctbill', 'scctbill.CUSTID', '=', 'scctcust.CUSTID');

        $base = $this->applyStudentFilters($base, $request);
        $base = $this->applyBillFilters($base, $request);

        $grouped = $base
            ->select(
                'scctcust.CUSTID',
                'scctcust.NMCUST',
                'scctcust.DESC01 as sekolah',
                'scctcust.DESC02 as kelas',
                'scctcust.GENUS as gender'
            )
            ->selectRaw('SUM(scctbill.BILLAM) as total_tagihan')
            ->selectRaw("SUM(CASE WHEN scctbill.PAIDST = '1' THEN scctbill.BILLAM ELSE 0 END) as total_terbayar")
            ->groupBy('scctcust.CUSTID', 'scctcust.NMCUST', 'scctcust.DESC01', 'scctcust.DESC02', 'scctcust.GENUS');

        if ($status === '1') {
            $grouped->havingRaw("SUM(scctbill.BILLAM) - SUM(CASE WHEN scctbill.PAIDST = '1' THEN scctbill.BILLAM ELSE 0 END) <= 0");
        } elseif ($status === '0') {
            $grouped->havingRaw("SUM(scctbill.BILLAM) - SUM(CASE WHEN scctbill.PAIDST = '1' THEN scctbill.BILLAM ELSE 0 END) > 0");
        }

        return $grouped;
    }

    private function applyStudentFilters($query, Request $request)
    {
        $sekolah = trim((string) $request->query('sekolah', ''));
        if ($sekolah !== '') {
            $query->where('scctcust.CODE01', $sekolah);
        }

        $kelas = trim((string) $request->query('kelas', ''));
        if ($kelas !== '') {
            $query->where('scctcust.CODE02', $kelas);
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('scctcust.NMCUST', 'like', "%{$search}%")
                    ->orWhere('scctcust.NOCUST', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function applyBillFilters($query, Request $request)
    {
        $bta = trim((string) $request->query('bta', ''));
        if ($bta !== '') {
            $query->where('scctbill.BTA', $bta);
        }

        $tagihan = $this->resolveTagihanArray($request);
        if (!empty($tagihan)) {
            $query->whereIn('scctbill.BILLNM', $tagihan);
        }

        return $query;
    }

    private function resolveTagihanArray(Request $request): array
    {
        $tagihan = $request->query('tagihan', []);
        if (is_string($tagihan)) {
            $tagihan = $tagihan === '' ? [] : explode(',', $tagihan);
        }
        if (!is_array($tagihan)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', $tagihan), fn($v) => $v !== ''));
    }

    private function resolveLimit(Request $request, int $default = 10): int
    {
        $limit = (int) $request->query('limit', $default);

        return in_array($limit, [10, 20, 25, 50, 100], true) ? $limit : $default;
    }

    private function filterLabels(Request $request): array
    {
        $paidst = $request->query('status', '');
        $statusLabel = 'Semua status';
        if ($paidst === '1') {
            $statusLabel = 'Lunas';
        } elseif ($paidst === '0') {
            $statusLabel = 'Belum Lunas';
        }

        $tagihan = $this->resolveTagihanArray($request);

        return [
            'sekolah' => trim((string) $request->query('sekolah', '')) ?: 'Semua sekolah',
            'bta' => trim((string) $request->query('bta', '')) ?: 'Semua tahun ajaran',
            'kelas' => trim((string) $request->query('kelas', '')) ?: 'Semua kelas',
            'status' => $statusLabel,
            'search' => trim((string) $request->query('search', '')) ?: '-',
            'tagihan' => $tagihan ? implode(', ', $tagihan) : 'Semua tagihan',
        ];
    }
}
