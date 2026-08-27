@extends('layouts.approval')

@section('title', 'Jadwal Tahfid')
@section('heading', 'Jadwal Tahfid')

@section('head')
<style>
    .jadwal-tabs { padding: 0 12px; margin: 0; }
    .jadwal-list { padding: 12px; display: flex; flex-direction: column; gap: 14px; }
    .unit-block { border: 1px solid var(--border); border-radius: 6px; overflow: hidden; background: var(--surface); }
    .unit-head {
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        padding: 10px 12px; background: var(--surface-2); border-bottom: 1px solid var(--border);
    }
    .unit-head .cell-main { font-size: .95rem; }
    .tingkat-block + .tingkat-block { border-top: 1px solid var(--border); }
    .tingkat-head {
        display: flex; align-items: center; gap: 8px; padding: 8px 12px;
        background: var(--bg); color: var(--t1); font-size: .78rem; font-weight: 700;
        cursor: pointer; user-select: none;
    }
    .tingkat-head i { width: 12px; color: var(--t2); font-size: .7rem; }
    .tingkat-body { padding: 10px 12px 12px; display: flex; flex-direction: column; gap: 10px; }
    .tingkat-body.is-collapsed { display: none; }
    .kelas-card { border: 1px solid var(--border); border-radius: 6px; overflow: hidden; background: var(--surface); }
    .kelas-head {
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        padding: 8px 12px; background: var(--surface);
    }
    .kelas-head .actions { margin-left: auto; display: flex; gap: 6px; align-items: center; }
    .kelas-meta { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .kelas-card table.data { min-width: 0; }
    .kelas-card .table-wrap { max-height: none; }
    .surat-chip {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 1px 7px; border-radius: 3px; border: 1px solid var(--border);
        background: var(--surface-2); color: var(--t2); font-size: .68rem; font-weight: 700;
    }
    @media (min-width: 1100px) {
        .tingkat-body { display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); align-items: start; }
        .tingkat-body.is-collapsed { display: none; }
    }
</style>
@endsection

@section('content')
    @php
        $filterQuery = array_filter([
            'code01' => $code01 ?? '',
            'tingkat' => $tingkat ?? '',
            'kelas' => $kelas ?? '',
            'q' => $q ?? '',
        ], fn ($v) => $v !== null && $v !== '');
        $tabBase = array_filter([
            'code01' => $code01 ?? '',
            'q' => $q ?? '',
        ], fn ($v) => $v !== null && $v !== '');
        $hasFilter = ($tingkat ?? '') !== '' || ($kelas ?? '') !== '' || ($q ?? '') !== '' || (($isSuperadmin ?? false) && ($code01 ?? '') !== '');
    @endphp
    <div class="panel table-card">
        <form method="GET" action="{{ route('tahfid.jadwal.index') }}" class="filters" id="jadwalFilter">
            <div class="filters-grid" style="grid-template-columns: {{ $isSuperadmin ? '1.2fr ' : '' }}.8fr 1fr 1.3fr auto auto;">
                @if($isSuperadmin)
                <div class="field">
                    <label for="filterUnit">Unit</label>
                    <select id="filterUnit" name="code01">
                        <option value="">Semua unit</option>
                        @foreach($sekolah as $sch)
                            <option value="{{ $sch['code01'] }}" {{ $code01 === ($sch['code01'] ?? '') ? 'selected' : '' }}>{{ $sch['sekolah'] }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="field">
                    <label for="filterTingkat">Tingkat</label>
                    <select id="filterTingkat" name="tingkat">
                        <option value="">Semua tingkat</option>
                        @foreach($tingkatOptions as $opt => $n)
                            <option value="{{ $opt }}" {{ $tingkat === $opt ? 'selected' : '' }}>{{ $opt }} ({{ $n }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="filterKelas">Kelas</label>
                    <select id="filterKelas" name="kelas">
                        <option value="">Semua kelas</option>
                        @foreach($kelasOptions as $opt)
                            <option value="{{ $opt }}" {{ $kelas === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="filterQ">Cari</label>
                    <input id="filterQ" name="q" type="text" value="{{ $q }}" placeholder="Nama kelas atau surat">
                </div>
                <button type="submit" class="btn btn-ghost">Terapkan</button>
                @if($hasFilter)
                    <a href="{{ route('tahfid.jadwal.index') }}" class="btn btn-ghost">Reset</a>
                @endif
            </div>
        </form>
        @if(count($tingkatOptions) > 0)
        <div class="tabs jadwal-tabs">
            <a class="chip {{ $tingkat === '' ? 'active' : '' }}" href="{{ route('tahfid.jadwal.index', $tabBase) }}">Semua</a>
            @foreach($tingkatOptions as $opt => $n)
                <a class="chip {{ $tingkat === $opt ? 'active' : '' }}" href="{{ route('tahfid.jadwal.index', array_merge($tabBase, ['tingkat' => $opt])) }}">{{ $opt }} <span class="group-count">{{ $n }}</span></a>
            @endforeach
        </div>
        @endif
        <div class="table-toolbar">
            <span class="count-pill">{{ count($items) }} jadwal@if(count($groups) > 1) · {{ count($groups) }} unit@endif</span>
            <a href="{{ route('tahfid.jadwal.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Buat jadwal</a>
        </div>
        @if(count($items) === 0)
            <div class="empty">
                <div><i class="fas fa-inbox"></i></div>
                @if($hasFilter)
                    Tidak ada jadwal pada filter ini.
                @else
                    Belum ada jadwal. Pilih unit dan kelas dari master kelas, lalu tambahkan surat hafalan.
                @endif
            </div>
        @else
            <div class="jadwal-list">
                @foreach($groups as $unit)
                    <section class="unit-block">
                        <div class="unit-head">
                            <div>
                                <div class="cell-main">{{ $unit['sekolah'] ?: $unit['code01'] ?: 'Unit' }}</div>
                                <div class="cell-sub">{{ $unit['count'] }} kelas · {{ count($unit['tingkat']) }} tingkat</div>
                            </div>
                        </div>
                        @foreach($unit['tingkat'] as $grp)
                            <div class="tingkat-block">
                                <div class="tingkat-head" data-toggle>
                                    <i class="fas fa-chevron-down"></i>
                                    Tingkat {{ $grp['tingkat'] }}
                                    <span class="group-count">{{ $grp['count'] }} kelas</span>
                                </div>
                                <div class="tingkat-body">
                                    @foreach($grp['items'] as $item)
                                        @php
                                            $details = is_array($item['details'] ?? null) ? $item['details'] : [];
                                            $updated = trim((string) ($item['updated_at'] ?? ''));
                                            $updatedLabel = $updated !== '' ? date('d/m/Y H:i', strtotime($updated)) : '';
                                        @endphp
                                        <article class="kelas-card">
                                            <div class="kelas-head">
                                                <div>
                                                    <div class="cell-main">Kelas {{ $item['kelas'] ?: '-' }}</div>
                                                    <div class="kelas-meta">
                                                        <span class="surat-chip">{{ count($details) }} surat</span>
                                                        @if($updatedLabel !== '')
                                                            <span class="cell-sub">{{ $updatedLabel }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="actions">
                                                    <a href="{{ route('tahfid.jadwal.edit', $item['id']) }}" class="btn btn-ghost btn-sm">Ubah</a>
                                                    <form method="POST" action="{{ route('tahfid.jadwal.delete') }}" class="js-confirm" data-title="Hapus jadwal?" data-text="Jadwal kelas {{ $item['kelas'] }} akan dihapus beserta setoran terkait.">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $item['id'] }}">
                                                        <button type="submit" class="btn btn-ghost btn-sm">Hapus</button>
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="table-wrap">
                                                @if(count($details) === 0)
                                                    <div class="empty" style="padding:16px">Belum ada surat pada jadwal ini.</div>
                                                @else
                                                    <table class="data">
                                                        <thead>
                                                            <tr>
                                                                <th style="width:40px">No</th>
                                                                <th>Surat</th>
                                                                <th>Juz</th>
                                                                <th>Ayat</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                        @foreach($details as $i => $row)
                                                            <tr>
                                                                <td class="mono">{{ $i + 1 }}</td>
                                                                <td>
                                                                    <div class="cell-main">{{ $row['surah_nama'] ?: ('Surat '.$row['surah_nomor']) }}</div>
                                                                    <div class="cell-sub">No. {{ $row['surah_nomor'] }}</div>
                                                                </td>
                                                                <td>{{ $row['juz_label'] ?? '-' }}</td>
                                                                <td>{{ $row['ayat_label'] ?? '-' }}</td>
                                                            </tr>
                                                        @endforeach
                                                        </tbody>
                                                    </table>
                                                @endif
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </section>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@section('scripts')
<script>
(function () {
    var form = document.getElementById('jadwalFilter');
    var unit = document.getElementById('filterUnit');
    var tingkat = document.getElementById('filterTingkat');
    var kelas = document.getElementById('filterKelas');
    if (unit) {
        unit.addEventListener('change', function () {
            if (tingkat) tingkat.value = '';
            if (kelas) kelas.value = '';
            form.submit();
        });
    }
    if (tingkat) {
        tingkat.addEventListener('change', function () {
            if (kelas) kelas.value = '';
            form.submit();
        });
    }
    document.querySelectorAll('[data-toggle]').forEach(function (head) {
        head.addEventListener('click', function () {
            var body = head.nextElementSibling;
            var icon = head.querySelector('i');
            if (!body) return;
            var closed = body.classList.toggle('is-collapsed');
            if (icon) icon.className = closed ? 'fas fa-chevron-right' : 'fas fa-chevron-down';
        });
    });
})();
</script>
@endsection
