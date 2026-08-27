@extends('layouts.approval')

@section('title', 'Progress Hafalan Siswi')
@section('heading', 'Progress Hafalan Siswi')

@section('head')
<style>
    .progress-page.panel { overflow: visible; }
    .progress-page .table-wrap { max-height: none; }
    .progress-page table.data { min-width: 720px; }
    .suggest-list {
        display: none; margin-top: 4px;
        background: var(--surface); border: 1px solid var(--border); border-radius: 4px;
        max-height: 220px; overflow: auto;
    }
    .suggest-list.open { display: block; }
    .suggest-item {
        display: block; width: 100%; text-align: left; border: 0; background: transparent;
        padding: 8px 10px; cursor: pointer; color: var(--t1); font: inherit;
    }
    .suggest-item:hover { background: var(--row-hover); }
    .suggest-name { font-weight: 650; font-size: .84rem; }
    .suggest-meta { color: var(--t2); font-size: .74rem; margin-top: 1px; white-space: normal; }
    .record-row td { background: var(--bg) !important; padding: 0 !important; border-bottom: 1px solid var(--border); }
    .record-box { display: none; padding: 12px 16px 14px; }
    .record-box.open { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(220px, .8fr); gap: 16px; align-items: start; }
    .record-grid { display: grid; grid-template-columns: 140px 72px 72px minmax(0, 1fr) auto; gap: 8px; align-items: end; }
    .record-grid .field { min-width: 0; }
    .log-list { border: 1px solid var(--border); border-radius: 4px; background: var(--surface); }
    .log-list .log-h {
        padding: 6px 10px; font-size: .68rem; font-weight: 700; letter-spacing: .04em;
        text-transform: uppercase; color: var(--t2); border-bottom: 1px solid var(--border); background: var(--surface-2);
    }
    .log-item { padding: 7px 10px; font-size: .74rem; border-bottom: 1px solid var(--border); }
    .log-item:last-child { border-bottom: 0; }
    .log-empty { padding: 10px; color: var(--t2); font-size: .78rem; }
    tr.is-hidden { display: none; }
    @media (max-width: 900px) {
        .record-box.open, .record-grid { grid-template-columns: 1fr; }
        .record-grid .btn { width: 100%; }
        .progress-page table.data { min-width: 0; }
    }
</style>
@endsection

@section('content')
    @php
        $openId = (int) request('open', 0);
        $total = (int) ($ringkasan['jumlah_surat'] ?? 0);
        $lunasN = (int) ($ringkasan['jumlah_lunas'] ?? 0);
        $initials = strtoupper(mb_substr(trim((string) ($siswa['nmcust'] ?? 'S')), 0, 1));
    @endphp
    <div class="panel table-card progress-page">
        <div class="filters">
            <div class="filters-grid" style="grid-template-columns: 1.6fr auto;">
                <div class="field">
                    <label for="siswaQ">Cari siswi</label>
                    <input id="siswaQ" type="text" placeholder="Ketik NIS atau nama, lalu pilih" autocomplete="off" value="{{ $siswa['nmcust'] ?? $nis }}">
                    <div class="suggest-list" id="siswaSuggest"></div>
                </div>
                @if($siswa)
                    <a href="{{ route('tahfid.progress.index') }}" class="btn btn-ghost">Ganti siswi</a>
                @endif
            </div>
        </div>

        @if($siswa)
            <div class="table-toolbar">
                <div class="cell-user">
                    <span class="avatar">{{ $initials }}</span>
                    <div>
                        <div class="cell-main">{{ $siswa['nmcust'] ?: '-' }}</div>
                        <div class="cell-sub">NIS {{ $siswa['nocust'] ?: '-' }} · {{ $siswa['sekolah'] ?: 'Unit' }} · Kelas {{ $siswa['kelas'] ?: '-' }}</div>
                    </div>
                </div>
                <span class="count-pill">{{ $lunasN }}/{{ $total }} surat lunas</span>
            </div>

            @if(count($groups) === 0)
                <div class="empty">
                    <div><i class="fas fa-inbox"></i></div>
                    Siswi ini belum memiliki jadwal kelas atau percepatan.
                </div>
            @else
                <div class="tabs" style="padding:0 12px">
                    <button type="button" class="chip active" data-filter="all">Semua</button>
                    <button type="button" class="chip" data-filter="belum">Belum</button>
                    <button type="button" class="chip" data-filter="proses">Proses</button>
                    <button type="button" class="chip" data-filter="lunas">Lunas</button>
                </div>
                <div class="table-wrap">
                    <table class="data" id="progressTable">
                        <thead>
                            <tr>
                                <th style="width:40px">No</th>
                                <th>Surat</th>
                                <th>Target</th>
                                <th>Tercatat</th>
                                <th style="width:90px">Status</th>
                                <th style="width:90px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($groups as $group)
                            @php
                                $details = is_array($group['details'] ?? null) ? $group['details'] : [];
                                $isAccel = ($group['sumber'] ?? '') === 'percepatan';
                            @endphp
                            <tr class="group-row" data-group>
                                <td colspan="6">
                                    <i class="fas fa-folder"></i>
                                    {{ $isAccel ? 'Percepatan' : 'Kelas siswi' }} · {{ $group['kelas'] ?? '-' }}
                                    <span class="group-count">{{ (int) ($group['jumlah_lunas'] ?? 0) }}/{{ count($details) }} lunas</span>
                                </td>
                            </tr>
                            @foreach($details as $i => $row)
                                @php
                                    $st = trim((string) ($row['progress_status'] ?? 'belum'));
                                    $badge = $st === 'lunas' ? 'approved' : ($st === 'proses' ? 'pending' : 'role-ms');
                                    $label = $st === 'lunas' ? 'Lunas' : ($st === 'proses' ? 'Proses' : 'Belum');
                                    $targetDari = (int) ($row['ayat_dari'] ?? 1);
                                    $targetSampai = (int) ($row['ayat_sampai'] ?? $targetDari);
                                    $nextDari = $st === 'proses' ? max($targetDari, ((int) ($row['progress_ayat_sampai'] ?? 0)) + 1) : $targetDari;
                                    if ($nextDari > $targetSampai) {
                                        $nextDari = $targetDari;
                                    }
                                    $logs = [];
                                    $seenLog = [];
                                    foreach (is_array($row['logs'] ?? null) ? $row['logs'] : [] as $log) {
                                        $key = trim((string) ($log['ayat_label'] ?? '')).'|'.trim((string) ($log['status'] ?? '')).'|'.substr((string) ($log['created_at'] ?? ''), 0, 16).'|'.trim((string) ($log['catatan'] ?? ''));
                                        if (isset($seenLog[$key])) {
                                            continue;
                                        }
                                        $seenLog[$key] = true;
                                        $logs[] = $log;
                                        if (count($logs) >= 5) {
                                            break;
                                        }
                                    }
                                    $isOpen = $openId === (int) ($row['id'] ?? 0);
                                @endphp
                                <tr class="data-row" data-status="{{ $st }}" data-row="{{ $row['id'] }}">
                                    <td class="mono">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="cell-main">{{ $row['surah_nama'] ?: ('Surat '.$row['surah_nomor']) }}</div>
                                        <div class="cell-sub">No. {{ $row['surah_nomor'] }} · {{ $row['juz_label'] ?? '-' }}</div>
                                    </td>
                                    <td>{{ $row['ayat_label'] ?? '-' }}</td>
                                    <td>
                                        <div>{{ $row['progress_label'] ?: '—' }}</div>
                                        @if(!empty($row['progress_catatan']))
                                            <div class="cell-sub">{{ $row['progress_catatan'] }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $badge }}">{{ $label }}</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-ghost btn-sm js-toggle" data-id="{{ $row['id'] }}">{{ $isOpen ? 'Tutup' : 'Catat' }}</button>
                                    </td>
                                </tr>
                                <tr class="record-row" data-record="{{ $row['id'] }}" data-status="{{ $st }}">
                                    <td colspan="6">
                                        <div class="record-box {{ $isOpen ? 'open' : '' }}">
                                            <form method="POST" action="{{ route('tahfid.progress.store') }}" class="js-progress-form">
                                                @csrf
                                                <input type="hidden" name="nis" value="{{ $siswa['nocust'] }}">
                                                <input type="hidden" name="jadwal_detail_id" value="{{ $row['id'] }}">
                                                <input type="hidden" class="js-target-dari" value="{{ $targetDari }}">
                                                <input type="hidden" class="js-target-sampai" value="{{ $targetSampai }}">
                                                <div class="record-grid">
                                                    <div class="field">
                                                        <label>Status</label>
                                                        <select name="status" class="js-mode">
                                                            <option value="proses" {{ $st !== 'lunas' ? 'selected' : '' }}>Proses</option>
                                                            <option value="lunas" {{ $st === 'lunas' ? 'selected' : '' }}>Lunas / selesai</option>
                                                        </select>
                                                    </div>
                                                    <div class="field js-ayat">
                                                        <label>Ayat dari</label>
                                                        <input type="number" name="ayat_dari" min="{{ $targetDari }}" max="{{ $targetSampai }}" value="{{ $nextDari }}">
                                                    </div>
                                                    <div class="field js-ayat">
                                                        <label>Sampai</label>
                                                        <input type="number" name="ayat_sampai" min="{{ $targetDari }}" max="{{ $targetSampai }}" value="{{ $targetSampai }}">
                                                    </div>
                                                    <div class="field">
                                                        <label>Catatan</label>
                                                        <input type="text" name="catatan" maxlength="500" placeholder="Opsional">
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                                </div>
                                            </form>
                                            <div class="log-list">
                                                <div class="log-h">Riwayat</div>
                                                @forelse($logs as $log)
                                                    <div class="log-item">
                                                        <div>{{ !empty($log['created_at']) ? date('d/m/Y H:i', strtotime($log['created_at'])) : '-' }} · {{ $log['ayat_label'] ?? '' }} · {{ $log['status_label'] ?? '' }}</div>
                                                        @if(!empty($log['catatan']))
                                                            <div class="cell-sub">{{ $log['catatan'] }}</div>
                                                        @endif
                                                    </div>
                                                @empty
                                                    <div class="log-empty">Belum ada riwayat.</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            <div class="empty">
                <div><i class="fas fa-search"></i></div>
                Cari siswi untuk mencatat progress hafalan sesuai jadwal kelas atau percepatan.
            </div>
        @endif
    </div>
@endsection

@section('scripts')
<script>
(function () {
    var searchUrl = @json(route('tahfid.siswa.search'));
    var progressUrl = @json(route('tahfid.progress.index'));
    var qEl = document.getElementById('siswaQ');
    var listEl = document.getElementById('siswaSuggest');
    var timer = null;
    function closeSuggest() { if (listEl) listEl.classList.remove('open'); }
    if (qEl) {
        qEl.addEventListener('input', function () {
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
                            listEl.innerHTML = '<div style="padding:10px;color:var(--t2);font-size:.8rem">Tidak ditemukan</div>';
                        } else {
                            rows.forEach(function (row) {
                                var btn = document.createElement('button');
                                btn.type = 'button';
                                btn.className = 'suggest-item';
                                btn.innerHTML = '<div class="suggest-name"></div><div class="suggest-meta"></div>';
                                btn.querySelector('.suggest-name').textContent = row.nmcust || row.nocust;
                                btn.querySelector('.suggest-meta').textContent = 'NIS ' + (row.nocust || '-') + ' · ' + (row.kelas || '-') + (row.sekolah ? (' · ' + row.sekolah) : '');
                                btn.addEventListener('click', function () {
                                    window.location.href = progressUrl + '?nis=' + encodeURIComponent(row.nocust || '');
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
    }

    function closeAll() {
        document.querySelectorAll('.record-box.open').forEach(function (box) { box.classList.remove('open'); });
        document.querySelectorAll('.js-toggle').forEach(function (btn) { btn.textContent = 'Catat'; });
    }
    document.querySelectorAll('.js-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-id');
            var box = document.querySelector('.record-row[data-record="' + id + '"] .record-box');
            var wasOpen = box && box.classList.contains('open');
            closeAll();
            if (!wasOpen && box) {
                box.classList.add('open');
                btn.textContent = 'Tutup';
                box.scrollIntoView({ block: 'nearest' });
            }
        });
    });

    document.querySelectorAll('.js-progress-form').forEach(function (form) {
        var mode = form.querySelector('.js-mode');
        var ayatFields = form.querySelectorAll('.js-ayat');
        var dari = form.querySelector('[name="ayat_dari"]');
        var sampai = form.querySelector('[name="ayat_sampai"]');
        var tDari = parseInt(form.querySelector('.js-target-dari').value, 10) || 1;
        var tSampai = parseInt(form.querySelector('.js-target-sampai').value, 10) || tDari;
        function sync() {
            var lunas = mode.value === 'lunas';
            ayatFields.forEach(function (el) { el.style.opacity = lunas ? '.55' : '1'; });
            if (dari) { dari.disabled = lunas; if (lunas) dari.value = tDari; }
            if (sampai) { sampai.disabled = lunas; if (lunas) sampai.value = tSampai; }
        }
        mode.addEventListener('change', sync);
        form.addEventListener('submit', function () {
            if (mode.value === 'lunas') {
                if (dari) dari.disabled = false;
                if (sampai) sampai.disabled = false;
            }
        });
        sync();
    });

    var chips = document.querySelectorAll('.chip[data-filter]');
    function applyFilter(f) {
        chips.forEach(function (c) { c.classList.toggle('active', c.getAttribute('data-filter') === f); });
        document.querySelectorAll('#progressTable .data-row').forEach(function (row) {
            var ok = f === 'all' || row.getAttribute('data-status') === f;
            row.classList.toggle('is-hidden', !ok);
            var rec = document.querySelector('.record-row[data-record="' + row.getAttribute('data-row') + '"]');
            if (rec) rec.classList.toggle('is-hidden', !ok);
        });
        document.querySelectorAll('#progressTable [data-group]').forEach(function (g) {
            var next = g.nextElementSibling;
            var any = false;
            while (next && !next.hasAttribute('data-group')) {
                if (next.classList.contains('data-row') && !next.classList.contains('is-hidden')) any = true;
                next = next.nextElementSibling;
            }
            g.classList.toggle('is-hidden', !any);
        });
    }
    chips.forEach(function (chip) {
        chip.addEventListener('click', function () { applyFilter(chip.getAttribute('data-filter')); });
    });
})();
</script>
@endsection
