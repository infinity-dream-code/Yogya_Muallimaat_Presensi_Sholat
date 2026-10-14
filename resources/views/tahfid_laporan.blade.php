@extends('layouts.approval')

@section('title', 'Laporan Setoran Tahfid')
@section('heading', 'Laporan Setoran Tahfid')

@section('head')
<style>
    .laporan-page .table-wrap { max-height: none; }
    .laporan-page table.data { min-width: 760px; }
    .laporan-page table.surat-table { min-width: 720px; }
    .laporan-tabs { padding: 0 12px; margin: 0; }
    .detail-row { display: none; }
    .detail-row.open { display: table-row; }
    .detail-row td { background: var(--bg) !important; padding: 0 11px 10px !important; }
    .detail-box { padding: 4px 0 2px; }
    .laporan-page table.surat-table { min-width: 0; width: 100%; border-collapse: collapse; background: var(--surface); border: 1px solid var(--border); border-radius: 4px; }
    .laporan-page table.surat-table th,
    .laporan-page table.surat-table td { padding: 5px 8px; font-size: .74rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
    .laporan-page table.surat-table th {
        position: static; background: var(--surface-2); text-transform: none; letter-spacing: 0; font-size: .68rem;
    }
    .laporan-page table.surat-table tr:last-child td { border-bottom: 0; }
    .laporan-page table.surat-table tbody tr:hover td { background: var(--row-hover); }
    .bar {
        width: 88px; height: 6px; border-radius: 3px; background: var(--surface-2);
        border: 1px solid var(--border); overflow: hidden;
    }
    .bar > span { display: block; height: 100%; background: var(--accent); }
    .pager {
        display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
        padding: 10px 12px; border-top: 1px solid var(--border); background: var(--surface-2);
    }
    .pager-btns { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
    .pager-btns .btn.is-current { background: var(--accent-soft); color: var(--accent); border-color: var(--accent); }
    .pager-btns .btn.is-off { opacity: .45; pointer-events: none; }
    @media (max-width: 900px) {
        .laporan-page table.data { min-width: 0; }
    }
</style>
@endsection

@section('content')
    @php
        $tabBase = array_filter([
            'code01' => $code01 ?? '',
            'kelas' => $kelas ?? '',
            'q' => $q ?? '',
        ], fn ($v) => $v !== null && $v !== '');
        $exportQuery = array_filter([
            'code01' => $code01 ?? '',
            'tingkat' => $tingkat ?? '',
            'kelas' => $kelas ?? '',
            'q' => $q ?? '',
            'status' => $status ?? '',
        ], fn ($v) => $v !== null && $v !== '');
        $hasFilter = ($tingkat ?? '') !== '' || ($kelas ?? '') !== '' || ($q ?? '') !== '' || ($status ?? '') !== '' || (($isSuperadmin ?? false) && ($code01 ?? '') !== '');
        $ringkasan = $ringkasan ?? ['jumlah_siswi' => 0, 'lunas_penuh' => 0, 'masih_proses' => 0, 'belum_mulai' => 0];
        $paginator = $paginator ?? ['page' => 1, 'per_page' => 20, 'total' => 0, 'last_page' => 1, 'from' => 0, 'to' => 0];
        $pageUrl = function (int $page) use ($exportQuery) {
            $query = $exportQuery;
            if ($page > 1) {
                $query['page'] = $page;
            }

            return route('tahfid.laporan.index', $query);
        };
    @endphp
    <div class="panel table-card laporan-page">
        <form method="GET" action="{{ route('tahfid.laporan.index') }}" class="filters" id="laporanFilter">
            <div class="filters-grid" style="grid-template-columns: {{ $isSuperadmin ? '1.2fr ' : '' }}.8fr 1fr 1.3fr auto auto;">
                @if($isSuperadmin)
                <div class="field">
                    <label for="filterUnit">Unit</label>
                    <select id="filterUnit" name="code01">
                        <option value="">Pilih unit</option>
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
                    <input id="filterQ" name="q" type="text" value="{{ $q }}" placeholder="NIS atau nama siswi">
                </div>
                @if($status !== '')
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <button type="submit" class="btn btn-ghost">Terapkan</button>
                @if($hasFilter)
                    <a href="{{ route('tahfid.laporan.index') }}" class="btn btn-ghost">Reset</a>
                @endif
            </div>
        </form>

        <div class="tabs laporan-tabs">
            <a class="chip {{ $status === '' ? 'active' : '' }}" href="{{ route('tahfid.laporan.index', $tabBase + array_filter(['tingkat' => $tingkat])) }}">Semua</a>
            <a class="chip {{ $status === 'belum_selesai' ? 'active' : '' }}" href="{{ route('tahfid.laporan.index', array_filter($tabBase + ['tingkat' => $tingkat, 'status' => 'belum_selesai'])) }}">Belum selesai</a>
            <a class="chip {{ $status === 'proses' ? 'active' : '' }}" href="{{ route('tahfid.laporan.index', array_filter($tabBase + ['tingkat' => $tingkat, 'status' => 'proses'])) }}">Proses</a>
            <a class="chip {{ $status === 'lunas' ? 'active' : '' }}" href="{{ route('tahfid.laporan.index', array_filter($tabBase + ['tingkat' => $tingkat, 'status' => 'lunas'])) }}">Lunas semua</a>
        </div>

        <div class="table-toolbar">
            <span class="count-pill">
                {{ (int) $ringkasan['jumlah_siswi'] }} siswi
                · {{ (int) $ringkasan['lunas_penuh'] }} lunas penuh
                · {{ (int) $ringkasan['masih_proses'] }} proses
                · {{ (int) $ringkasan['belum_mulai'] }} belum mulai
            </span>
            @if((int) ($ringkasan['jumlah_siswi'] ?? 0) > 0)
                <button type="button" class="btn btn-ghost btn-sm" id="btnExportOpen">
                    <i class="fas fa-file-excel"></i> Unduh Excel
                </button>
            @endif
        </div>

        @if(!empty($needUnit))
            <div class="empty">
                <div><i class="fas fa-school"></i></div>
                Pilih unit terlebih dahulu untuk melihat rekap setoran.
            </div>
        @elseif(count($items) === 0)
            <div class="empty">
                <div><i class="fas fa-inbox"></i></div>
                {{ $hasFilter ? 'Tidak ada siswi yang punya jadwal pada filter ini.' : 'Belum ada siswi dengan jadwal setoran untuk unit ini.' }}
            </div>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Siswi</th>
                            <th>Kelas</th>
                            <th>Progress</th>
                            <th style="width:90px">Status</th>
                            <th style="width:130px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($groups as $group)
                        <tr class="group-row">
                            <td colspan="5">
                                <i class="fas fa-folder"></i>
                                {{ $group['kelas'] }}
                                <span class="group-count">{{ $group['lunas_penuh'] }}/{{ $group['count'] }} lunas</span>
                            </td>
                        </tr>
                        @foreach($group['items'] as $item)
                            @php
                                $badge = trim((string) ($item['badge'] ?? 'belum'));
                                $badgeClass = $badge === 'lunas' ? 'approved' : ($badge === 'proses' ? 'pending' : 'role-ms');
                                $badgeLabel = $badge === 'lunas' ? 'Lunas' : ($badge === 'proses' ? 'Proses' : 'Belum');
                                $target = (int) ($item['target'] ?? 0);
                                $lunas = (int) ($item['lunas'] ?? 0);
                                $persen = (int) ($item['persen'] ?? 0);
                                $initials = strtoupper(mb_substr(trim((string) ($item['nmcust'] ?? 'S')), 0, 1));
                                $details = is_array($item['details'] ?? null) ? $item['details'] : [];
                                $rowId = preg_replace('/[^a-zA-Z0-9]/', '', (string) ($item['nocust'] ?? $item['custid'] ?? uniqid()));
                            @endphp
                            <tr>
                                <td>
                                    <div class="cell-user">
                                        <span class="avatar">{{ $initials }}</span>
                                        <div>
                                            <div class="cell-main">{{ $item['nmcust'] ?: '-' }}</div>
                                            <div class="cell-sub">
                                                NIS {{ $item['nocust'] ?: '-' }}
                                                @if(!empty($item['percepatan']))
                                                    · Percepatan
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $item['kelas'] ?: '-' }}</td>
                                <td>
                                    @if($target < 1)
                                        <div class="cell-sub">Belum ada jadwal</div>
                                    @else
                                        <div class="cell-main">{{ $lunas }}/{{ $target }} surat</div>
                                        <div style="display:flex;align-items:center;gap:8px;margin-top:4px">
                                            <div class="bar" title="{{ $persen }}%"><span style="width: {{ $persen }}%"></span></div>
                                            <span class="cell-sub">{{ $persen }}%</span>
                                        </div>
                                    @endif
                                </td>
                                <td><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
                                <td>
                                    <div class="actions">
                                        <button type="button" class="btn btn-ghost btn-sm js-toggle" data-id="{{ $rowId }}" {{ count($details) === 0 ? 'disabled' : '' }}>Surat</button>
                                        <a href="{{ route('tahfid.progress.index', ['nis' => $item['nocust']]) }}" class="btn btn-ghost btn-sm">Lihat</a>
                                    </div>
                                </td>
                            </tr>
                            <tr class="detail-row" data-record="{{ $rowId }}">
                                <td colspan="5">
                                    <div class="detail-box">
                                        @if(count($details) === 0)
                                            <div class="cell-sub">Belum ada jadwal surat untuk siswi ini.</div>
                                        @else
                                            <table class="surat-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width:44px">No</th>
                                                        <th>Surat</th>
                                                        <th style="width:110px">Ayat</th>
                                                        <th>Catatan</th>
                                                        <th style="width:140px">Waktu setor</th>
                                                        <th style="width:140px">Asal</th>
                                                        <th style="width:90px">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($details as $i => $row)
                                                    @php
                                                        $st = trim((string) ($row['status'] ?? 'belum'));
                                                        $stClass = $st === 'lunas' ? 'approved' : ($st === 'proses' ? 'pending' : 'role-ms');
                                                        $stLabel = $st === 'lunas' ? 'Lunas' : ($st === 'proses' ? 'Proses' : 'Belum');
                                                        $asal = trim((string) ($row['sumber'] ?? 'kelas')) === 'percepatan' ? 'Percepatan' : 'Kelas';
                                                        $asalKelas = trim((string) ($row['kelas'] ?? ''));
                                                        $ayatDari = (int) ($row['ayat_dari'] ?? 0);
                                                        $ayatSampai = (int) ($row['ayat_sampai'] ?? 0);
                                                        $ayatLabel = trim((string) ($row['ayat_label'] ?? ''));
                                                        if ($ayatLabel === '' && $ayatDari > 0 && $ayatSampai > 0) {
                                                            $ayatLabel = $ayatDari === $ayatSampai ? ('Ayat '.$ayatDari) : ('Ayat '.$ayatDari.'–'.$ayatSampai);
                                                        }
                                                        $catatan = trim((string) ($row['catatan'] ?? ''));
                                                        $setorAt = trim((string) ($row['setor_at'] ?? ''));
                                                    @endphp
                                                    <tr>
                                                        <td class="mono">{{ $i + 1 }}</td>
                                                        <td>
                                                            <div class="cell-main">{{ $row['surah_nama'] ?: ('Surat '.$row['surah_nomor']) }}</div>
                                                            <div class="cell-sub">No. {{ $row['surah_nomor'] ?: '-' }}</div>
                                                        </td>
                                                        <td>{{ $ayatLabel !== '' ? $ayatLabel : '—' }}</td>
                                                        <td>{{ $catatan !== '' ? $catatan : '—' }}</td>
                                                        <td class="cell-sub">{{ $setorAt !== '' ? $setorAt : '—' }}</td>
                                                        <td>
                                                            {{ $asal }}
                                                            @if($asalKelas !== '')
                                                                · {{ $asalKelas }}
                                                            @endif
                                                        </td>
                                                        <td><span class="badge {{ $stClass }}">{{ $stLabel }}</span></td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if((int) $paginator['total'] > 0)
                @php
                    $cur = (int) $paginator['page'];
                    $last = (int) $paginator['last_page'];
                    $start = max(1, $cur - 2);
                    $end = min($last, $cur + 2);
                @endphp
                <div class="pager">
                    <span class="count-pill">Menampilkan {{ (int) $paginator['from'] }}–{{ (int) $paginator['to'] }} dari {{ (int) $paginator['total'] }} siswi</span>
                    @if($last > 1)
                    <div class="pager-btns">
                        <a href="{{ $pageUrl(max(1, $cur - 1)) }}" class="btn btn-ghost btn-sm {{ $cur <= 1 ? 'is-off' : '' }}">Sebelumnya</a>
                        @if($start > 1)
                            <a href="{{ $pageUrl(1) }}" class="btn btn-ghost btn-sm">1</a>
                            @if($start > 2)
                                <span class="count-pill">…</span>
                            @endif
                        @endif
                        @for($n = $start; $n <= $end; $n++)
                            <a href="{{ $pageUrl($n) }}" class="btn btn-ghost btn-sm {{ $n === $cur ? 'is-current' : '' }}">{{ $n }}</a>
                        @endfor
                        @if($end < $last)
                            @if($end < $last - 1)
                                <span class="count-pill">…</span>
                            @endif
                            <a href="{{ $pageUrl($last) }}" class="btn btn-ghost btn-sm">{{ $last }}</a>
                        @endif
                        <a href="{{ $pageUrl(min($last, $cur + 1)) }}" class="btn btn-ghost btn-sm {{ $cur >= $last ? 'is-off' : '' }}">Berikutnya</a>
                    </div>
                    @endif
                </div>
            @endif
        @endif
    </div>

    <div class="modal-bg" id="exportModal">
        <div class="modal">
            <h3>Unduh Excel</h3>
            <form method="GET" action="{{ route('tahfid.laporan.export') }}" id="exportForm">
                @if(!empty($code01))<input type="hidden" name="code01" value="{{ $code01 }}">@endif
                @if(!empty($tingkat))<input type="hidden" name="tingkat" value="{{ $tingkat }}">@endif
                @if(!empty($kelas))<input type="hidden" name="kelas" value="{{ $kelas }}">@endif
                <div class="modal-grid">
                    <div class="field">
                        <label for="exportStatus">Cakupan</label>
                        <select id="exportStatus" name="status">
                            <option value="" {{ ($status ?? '') === '' ? 'selected' : '' }}>Keseluruhan</option>
                            <option value="lunas" {{ ($status ?? '') === 'lunas' ? 'selected' : '' }}>Sudah selesai / lunas semua</option>
                            <option value="proses" {{ ($status ?? '') === 'proses' ? 'selected' : '' }}>Masih proses</option>
                            <option value="belum_selesai" {{ ($status ?? '') === 'belum_selesai' ? 'selected' : '' }}>Belum selesai</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="exportQ">Cari nama / NIS</label>
                        <input id="exportQ" name="q" type="text" value="{{ $q }}" placeholder="Kosongkan untuk semua">
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-ghost" id="exportCancel">Batal</button>
                    <button type="submit" class="btn btn-ok">Unduh</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    var form = document.getElementById('laporanFilter');
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
    var exportModal = document.getElementById('exportModal');
    var exportOpen = document.getElementById('btnExportOpen');
    var exportCancel = document.getElementById('exportCancel');
    if (exportOpen && exportModal) {
        exportOpen.addEventListener('click', function () { exportModal.classList.add('open'); });
    }
    if (exportCancel && exportModal) {
        exportCancel.addEventListener('click', function () { exportModal.classList.remove('open'); });
    }
    if (exportModal) {
        exportModal.addEventListener('click', function (e) {
            if (e.target === exportModal) exportModal.classList.remove('open');
        });
    }
    document.querySelectorAll('.js-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = document.querySelector('.detail-row[data-record="' + btn.getAttribute('data-id') + '"]');
            if (!row) return;
            var open = row.classList.toggle('open');
            btn.textContent = open ? 'Tutup' : 'Surat';
        });
    });
})();
</script>
@endsection
