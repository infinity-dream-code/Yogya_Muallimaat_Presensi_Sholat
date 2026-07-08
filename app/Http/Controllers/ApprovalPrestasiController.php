<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalPrestasiController extends Controller
{
    public function index(Request $request)
    {
        if (session('user.app') !== 'approval-prestasi') {
            return redirect()->route('dashboard.presensi-sholat');
        }

        $status = $request->query('status', 'pending');
        $items = $this->fetchItems($status);

        return view('approval_prestasi', [
            'items' => $items,
            'status' => $status,
            'scopeCode01' => trim((string) session('user.code01', '')),
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

        $userCode01 = trim((string) session('user.code01', ''));
        $approvedBy = trim((string) session('user.nama', session('user.username', 'SYSTEM')));
        if ($approvedBy === '') {
            $approvedBy = 'SYSTEM';
        }

        $rewardRow = DB::table('aka_reward as ar')
            ->leftJoin('scctcust as sc', 'sc.CUSTID', '=', 'ar.custid')
            ->select('ar.id', 'sc.CODE01')
            ->where('ar.id', (int) $validated['id'])
            ->when($userCode01 !== '', function ($query) use ($userCode01) {
                $query->where('sc.CODE01', $userCode01);
            })
            ->first();

        if (! $rewardRow) {
            return back()->with('error', 'Data tidak ditemukan atau bukan scope sekolah Anda.');
        }

        if ($validated['action'] === 'approve') {
            DB::table('aka_reward')
                ->where('id', (int) $validated['id'])
                ->update([
                    'isapproved' => 1,
                    'approveddate' => now(),
                    'approvedby' => $approvedBy,
                    'updated_at' => now(),
                ]);
            $message = 'Data berhasil di-approve.';
        } else {
            DB::table('aka_reward')
                ->where('id', (int) $validated['id'])
                ->update([
                    'isapproved' => 0,
                    'approveddate' => null,
                    'approvedby' => null,
                    'updated_at' => now(),
                ]);
            $message = 'Data berhasil ditolak.';
        }

        return redirect()
            ->route('approval.prestasi.index', ['status' => $validated['status'] ?? 'pending'])
            ->with('success', $message);
    }

    private function fetchItems(string $status): array
    {
        $userCode01 = trim((string) session('user.code01', ''));
        $query = DB::table('aka_reward as ar')
            ->leftJoin('scctcust as sc', 'sc.CUSTID', '=', 'ar.custid')
            ->leftJoin('mst_sekolah as ms', 'ms.CODE01', '=', 'sc.CODE01')
            ->select(
                'ar.id',
                'ar.custid',
                'ar.nocust',
                'ar.nmcust',
                'ar.kelas',
                'ar.jenis_prestasi',
                'ar.keterangan',
                'ar.nilai_penghargaan',
                'ar.bta',
                'ar.url',
                'ar.isapproved',
                'ar.approveddate',
                'ar.approvedby',
                'ar.created_at',
                'sc.CODE01 as code01',
                'ms.DESC01 as sekolah'
            )
            ->when($userCode01 !== '', function ($builder) use ($userCode01) {
                $builder->where('sc.CODE01', $userCode01);
            })
            ->orderByDesc('ar.created_at')
            ->orderByDesc('ar.id');

        if ($status === 'pending') {
            $query->where('ar.isapproved', 0);
        } elseif ($status === 'approved') {
            $query->where('ar.isapproved', 1);
        }

        return $query->limit(1000)->get()->map(function ($row) {
            return (array) $row;
        })->toArray();
    }
}

