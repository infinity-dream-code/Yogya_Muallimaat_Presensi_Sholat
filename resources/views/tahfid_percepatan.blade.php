@extends('layouts.approval')

@section('title', 'Percepatan Tahfid')
@section('heading', 'Percepatan Tahfid')

@section('head')
<style>
    .assign-card.panel { overflow: visible; }
    .identitas-box { padding: 14px 16px 8px; display: flex; flex-direction: column; gap: 12px; min-width: 0; }
    .identitas-title {
        font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
        color: var(--t2); margin-bottom: 0;
    }
    .assign-search { min-width: 0; }
    .suggest-list {
        display: none; margin-top: 4px; z-index: 20;
        background: var(--surface); border: 1px solid var(--border); border-radius: 4px;
        max-height: 220px; overflow: auto;
    }
    .suggest-list.open { display: block; }
    .suggest-item {
        display: block; width: 100%; text-align: left; border: 0; background: transparent;
        padding: 8px 10px; cursor: pointer; color: var(--t1); font: inherit;
    }
    .suggest-item:hover, .suggest-item.active { background: var(--row-hover); }
    .suggest-name { font-weight: 650; font-size: .84rem; }
    .suggest-meta { color: var(--t2); font-size: .74rem; margin-top: 1px; white-space: normal; }
    .picked {
        display: none; margin-top: 8px; padding: 8px 10px;
        border: 1px solid var(--border); border-radius: 4px; background: var(--accent-soft);
    }
    .picked.open { display: block; }
    .assign-grid {
        display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr) auto;
        gap: 10px; align-items: end; min-width: 0;
    }
    .assign-grid .field { min-width: 0; }
    .hint {
        font-size: .74rem; color: var(--t2); margin: 0; padding: 0;
        line-height: 1.45; white-space: normal;
    }
    .percepatan-list .table-wrap { max-height: none; }
    .percepatan-list table.data { min-width: 640px; }
    @media (max-width: 900px) {
        .assign-grid { grid-template-columns: 1fr; }
        .assign-grid .btn { width: 100%; }
    }
</style>
@endsection

@section('content')
    @php
        $q = $q ?? '';
        $kelasFilter = $kelas ?? '';
        $hasFilter = ($q !== '') || ($kelasFilter !== '') || (($isSuperadmin ?? false) && ($code01 ?? '') !== '');
        $groups = $groups ?? [];
    @endphp

    <form method="POST" action="{{ route('tahfid.percepatan.store') }}" class="panel assign-card" id="percepatanForm">
        @csrf
        <div class="identitas-box">
            <div class="identitas-title">Tugaskan kelas berikutnya</div>
            <div class="field assign-search">
                <label for="siswaQ">Cari siswi</label>
                <input id="siswaQ" type="text" placeholder="Ketik NIS atau nama, lalu pilih dari daftar" autocomplete="off">
                <div class="suggest-list" id="siswaSuggest"></div>
                <div class="picked" id="siswaPicked">
                    <div class="cell-main" id="pickedName"></div>
                    <div class="cell-sub" id="pickedMeta"></div>
                </div>
            </div>
            <div class="assign-grid">
                <div class="field">
                    <label>Kelas saat ini</label>
                    <input id="kelasAsal" type="text" value="" readonly>
                    <input type="hidden" name="nis" id="fNis" required>
                </div>
                <div class="field">
                    <label for="kelasTujuan">Kelas tujuan</label>
                    <select id="kelasTujuan" name="kelas_tujuan" required>
                        <option value="">Pilih kelas yang sudah ada jadwalnya</option>
                        @foreach($jadwalKelas as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
            <p class="hint">Siswi yang sudah mampu dapat mengambil jadwal kelas berikutnya, selama jadwal kelas tujuan sudah dibuat.</p>
        </div>
    </form>

    <div class="panel table-card percepatan-list" style="margin-top:14px">
        <form method="GET" action="{{ route('tahfid.percepatan.index') }}" class="filters">
            <div class="filters-grid" style="grid-template-columns: {{ $isSuperadmin ? '1.2fr ' : '' }}1fr 1.3fr auto auto;">
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
                    <label for="filterKelas">Kelas tujuan</label>
                    <select id="filterKelas" name="kelas">
                        <option value="">Semua kelas</option>
                        @foreach($kelasOptions as $opt)
                            <option value="{{ $opt }}" {{ $kelasFilter === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="filterQ">Cari</label>
                    <input id="filterQ" name="q" type="text" value="{{ $q }}" placeholder="NIS atau nama siswi">
                </div>
                <button type="submit" class="btn btn-ghost">Terapkan</button>
                @if($hasFilter)
                    <a href="{{ route('tahfid.percepatan.index') }}" class="btn btn-ghost">Reset</a>
                @endif
            </div>
        </form>
        <div class="table-toolbar">
            <span class="count-pill">{{ count($items) }} percepatan</span>
        </div>
        <div class="table-wrap">
            @if(count($items) === 0)
                <div class="empty">
                    <div><i class="fas fa-inbox"></i></div>
                    {{ $hasFilter ? 'Tidak ada data pada filter ini.' : 'Belum ada siswi yang ditugaskan ke kelas berikutnya.' }}
                </div>
            @else
                <table class="data">
                    <thead>
                        <tr>
                            <th>Siswi</th>
                            <th>Kelas asal</th>
                            <th>Kelas tujuan</th>
                            <th style="width:110px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($groups as $unit)
                        @if(count($groups) > 1)
                            <tr class="group-row">
                                <td colspan="4">{{ $unit['sekolah'] ?: $unit['code01'] ?: 'Unit' }} <span class="group-count">{{ $unit['count'] }}</span></td>
                            </tr>
                        @endif
                        @foreach($unit['items'] as $item)
                            <tr>
                                <td>
                                    <div class="cell-main">{{ $item['nmcust'] ?: '-' }}</div>
                                    <div class="cell-sub">NIS {{ $item['nocust'] ?: '-' }}@if(count($groups) === 1 && !empty($item['sekolah'])) · {{ $item['sekolah'] }}@endif</div>
                                </td>
                                <td>{{ $item['kelas_asal'] ?: '-' }}</td>
                                <td>{{ $item['kelas_tujuan'] ?: '-' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('tahfid.percepatan.delete') }}" class="js-confirm" data-title="Hapus percepatan?" data-text="Siswi ini tidak lagi melihat jadwal kelas {{ $item['kelas_tujuan'] }}.">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $item['id'] }}">
                                        <button type="submit" class="btn btn-ghost btn-sm">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    var searchUrl = @json(route('tahfid.siswa.search'));
    var jadwalKelasUrl = @json(route('tahfid.jadwal.kelas'));
    var qEl = document.getElementById('siswaQ');
    var listEl = document.getElementById('siswaSuggest');
    var nisEl = document.getElementById('fNis');
    var asalEl = document.getElementById('kelasAsal');
    var tujuanEl = document.getElementById('kelasTujuan');
    var pickedEl = document.getElementById('siswaPicked');
    var pickedName = document.getElementById('pickedName');
    var pickedMeta = document.getElementById('pickedMeta');
    var timer = null;

    function closeSuggest() { listEl.classList.remove('open'); }
    function clearPicked() {
        pickedEl.classList.remove('open');
        pickedName.textContent = '';
        pickedMeta.textContent = '';
    }
    function showPicked(row) {
        pickedName.textContent = row.nmcust || row.nocust || '-';
        pickedMeta.textContent = 'NIS ' + (row.nocust || '-') + ' · ' + (row.kelas || '-') + (row.sekolah ? (' · ' + row.sekolah) : '');
        pickedEl.classList.add('open');
    }
    function kelasName(row) {
        if (row && typeof row === 'object') return String(row.kelas || '');
        return String(row || '');
    }
    function tingkatOf(kelas) {
        var m = String(kelas || '').trim().toUpperCase().match(/^(XII|XI|IX|X|VIII|VII|VI|IV|V|III|II|I)(?=\s|$|[^A-Z])/);
        return m ? m[1] : '';
    }
    function nextTingkat(tingkat) {
        var order = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        var i = order.indexOf(tingkat);
        return i >= 0 && i < order.length - 1 ? order[i + 1] : '';
    }
    function fillTujuan(code01, skipKelas) {
        fetch(jadwalKelasUrl + '?code01=' + encodeURIComponent(code01 || ''), { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                var rows = (json && json.data) ? json.data : [];
                var skip = String(skipKelas || '').trim().toUpperCase();
                var all = [];
                rows.forEach(function (row) {
                    var k = kelasName(row).trim();
                    if (!k || k.toUpperCase() === skip) return;
                    all.push(k);
                });
                var asalTingkat = tingkatOf(skipKelas);
                var target = all;
                if (asalTingkat) {
                    var next = nextTingkat(asalTingkat);
                    var filtered = next ? all.filter(function (k) { return tingkatOf(k) === next; }) : [];
                    if (filtered.length) target = filtered;
                }
                tujuanEl.innerHTML = '<option value="">Pilih kelas yang sudah ada jadwalnya</option>';
                if (!target.length) {
                    var empty = document.createElement('option');
                    empty.disabled = true;
                    empty.textContent = 'Tidak ada kelas tujuan yang tersedia';
                    tujuanEl.appendChild(empty);
                    return;
                }
                var groups = {};
                var order = [];
                target.forEach(function (k) {
                    var g = tingkatOf(k) || 'Lainnya';
                    if (!groups[g]) {
                        groups[g] = [];
                        order.push(g);
                    }
                    groups[g].push(k);
                });
                order.forEach(function (g) {
                    var parent = tujuanEl;
                    if (order.length > 1) {
                        parent = document.createElement('optgroup');
                        parent.label = g;
                        tujuanEl.appendChild(parent);
                    }
                    groups[g].forEach(function (k) {
                        var opt = document.createElement('option');
                        opt.value = k;
                        opt.textContent = k;
                        parent.appendChild(opt);
                    });
                });
            });
    }

    qEl.addEventListener('input', function () {
        nisEl.value = '';
        asalEl.value = '';
        clearPicked();
        var q = qEl.value.trim();
        clearTimeout(timer);
        if (q.length < 2) { closeSuggest(); return; }
        timer = setTimeout(function () {
            fetch(searchUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                .then(function (res) { return res.json(); })
                .then(function (json) {
                    var rows = (json && json.data) ? json.data : [];
                    listEl.innerHTML = '';
                    if (!rows.length) {
                        listEl.innerHTML = '<div class="suggest-empty" style="padding:10px;color:var(--t2);font-size:.8rem">Tidak ditemukan</div>';
                    } else {
                        rows.forEach(function (row) {
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'suggest-item';
                            btn.innerHTML = '<div class="suggest-name"></div><div class="suggest-meta"></div>';
                            btn.querySelector('.suggest-name').textContent = row.nmcust || row.nocust;
                            btn.querySelector('.suggest-meta').textContent = 'NIS ' + (row.nocust || '-') + ' · ' + (row.kelas || '-') + (row.sekolah ? (' · ' + row.sekolah) : '');
                            btn.addEventListener('click', function () {
                                qEl.value = row.nmcust || row.nocust;
                                nisEl.value = row.nocust || '';
                                asalEl.value = row.kelas || '';
                                showPicked(row);
                                fillTujuan(row.code01 || '', row.kelas || '');
                                closeSuggest();
                            });
                            listEl.appendChild(btn);
                        });
                    }
                    listEl.classList.add('open');
                });
        }, 250);
    });
    document.addEventListener('click', function (e) {
        if (!listEl.contains(e.target) && e.target !== qEl) closeSuggest();
    });
    document.getElementById('percepatanForm').addEventListener('submit', function (e) {
        if (!nisEl.value) {
            e.preventDefault();
            window.showToast('Pilih siswi dari hasil pencarian.', 'error');
        }
    });

    var unit = document.getElementById('filterUnit');
    if (unit) {
        unit.addEventListener('change', function () { unit.form.submit(); });
    }
})();
</script>
@endsection
