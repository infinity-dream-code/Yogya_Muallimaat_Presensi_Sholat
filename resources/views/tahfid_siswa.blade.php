@extends('layouts.approval')

@section('title', 'Jadwal Hafalan')
@section('heading', 'Jadwal Hafalan Saya')

@section('content')
    @php
        $nama = $siswa['nmcust'] ?? session('user.nama');
        $kelas = $siswa['kelas'] ?? session('user.kelas');
        $sekolah = $siswa['sekolah'] ?? '';
        $nis = $siswa['nocust'] ?? session('user.nocust');
    @endphp

    <div class="panel table-card">
        <div class="table-toolbar">
            <div>
                <div class="cell-main">{{ $nama }}</div>
                <div class="cell-sub">NIS {{ $nis ?: '-' }} · {{ $sekolah ?: 'Unit' }} · Kelas {{ $kelas ?: '-' }}</div>
            </div>
        </div>
        @if(count($groups) === 0)
            <div class="empty">
                <div><i class="fas fa-quran"></i></div>
                Jadwal hafalan untuk kelas Anda belum diatur. Hubungi musrifah atau admin.
            </div>
        @else
            @foreach($groups as $group)
                @php
                    $details = is_array($group['details'] ?? null) ? $group['details'] : [];
                    $isAccel = ($group['sumber'] ?? '') === 'percepatan';
                @endphp
                <div style="padding:4px 16px 16px">
                    <div class="table-toolbar" style="padding-left:0;padding-right:0">
                        <span class="count-pill">
                            {{ $isAccel ? 'Percepatan' : 'Kelas saya' }} · {{ $group['kelas'] ?? $kelas }}
                            · {{ count($details) }} surat
                        </span>
                    </div>
                    <div class="table-wrap" style="max-height:none">
                        <table class="data">
                            <thead>
                                <tr>
                                    <th style="width:40px">No</th>
                                    <th>Surat</th>
                                    <th>Juz</th>
                                    <th>Ayat</th>
                                    <th>Status</th>
                                    <th style="width:140px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($details as $i => $row)
                                @php
                                    $st = trim((string) ($row['progress_status'] ?? ''));
                                    if ($st === '' && !empty($row['sudah_setor'])) {
                                        $st = 'lunas';
                                    }
                                    $done = $st === 'lunas';
                                @endphp
                                <tr>
                                    <td class="mono">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="cell-main">{{ $row['surah_nama'] ?: ('Surat '.$row['surah_nomor']) }}</div>
                                        <div class="cell-sub">No. {{ $row['surah_nomor'] }}</div>
                                    </td>
                                    <td>{{ $row['juz_label'] ?? '-' }}</td>
                                    <td>{{ $row['ayat_label'] ?? '-' }}</td>
                                    <td>
                                        @if($done)
                                            <span class="badge approved">Lunas</span>
                                            <div class="cell-sub">{{ $row['progress_at'] ?: ($row['setor_at'] ?? '') }}</div>
                                        @elseif($st === 'proses')
                                            <span class="badge pending">Proses</span>
                                            <div class="cell-sub">{{ $row['progress_label'] ?: '' }}</div>
                                        @else
                                            <span class="badge pending">Belum setor</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($done)
                                            <span class="cell-sub">Selesai</span>
                                        @else
                                            <form method="POST" action="{{ route('tahfid.siswa.setor') }}" class="js-confirm" data-title="Catat setoran?" data-text="Setoran {{ $row['surah_nama'] }} akan tercatat lunas.">
                                                @csrf
                                                <input type="hidden" name="jadwal_detail_id" value="{{ $row['id'] }}">
                                                <button type="submit" class="btn btn-primary btn-sm">Setor lunas</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">Jadwal kelas ini belum berisi surat.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection
