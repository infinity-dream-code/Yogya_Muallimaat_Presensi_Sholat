@extends('layouts.approval')

@section('title', $item ? 'Ubah Jadwal Tahfid' : 'Buat Jadwal Tahfid')
@section('heading', $item ? 'Ubah Jadwal Tahfid' : 'Buat Jadwal Tahfid')

@section('head')
<style>
    .identitas-box { padding: 14px 16px 8px; }
    .identitas-title {
        font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
        color: var(--t2); margin-bottom: 10px;
    }
    .identitas-grid { display: grid; grid-template-columns: 220px 1fr; gap: 12px; align-items: start; }
    .hint { font-size: .74rem; color: var(--t2); margin-top: 6px; }
    .kelas-box {
        display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px;
        max-height: 230px; overflow: auto; padding: 2px;
    }
    .kelas-empty { color: var(--t2); font-size: .82rem; padding: 10px; grid-column: 1 / -1; }
    .kelas-group {
        border: 1px solid var(--border); border-radius: 4px; padding: 8px 10px; background: var(--surface-2);
    }
    .kelas-group-head {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        font-weight: 700; font-size: .8rem; margin-bottom: 6px;
    }
    .kelas-group-head label { display: flex; align-items: center; gap: 6px; cursor: pointer; }
    .kelas-chips { display: flex; flex-wrap: wrap; gap: 5px; }
    .kelas-chip {
        display: inline-flex; align-items: center; gap: 5px; font-size: .76rem; font-weight: 500; cursor: pointer;
        padding: 2px 7px; border: 1px solid var(--border); border-radius: 4px; background: var(--surface);
    }
    .kelas-chip:has(input:checked) { border-color: var(--accent); background: var(--accent-soft); }
    .kelas-chip input { margin: 0; }
    .surat-add { display: grid; grid-template-columns: 1.6fr .9fr 110px auto; gap: 8px; align-items: end; }
    .range-row { display: none; grid-template-columns: 140px 140px 1fr; gap: 8px; align-items: end; margin-top: 8px; }
    .range-row.open { display: grid; }
    .range-max { font-size: .74rem; color: var(--t2); padding-bottom: 8px; }
    .form-actions { display: flex; justify-content: flex-end; gap: 8px; padding: 8px 16px 16px; }
    .search-wrap { position: relative; }
    .suggest-list {
        display: none; position: absolute; left: 0; right: 0; top: calc(100% + 4px); z-index: 30;
        background: var(--surface); border: 1px solid var(--border); border-radius: 4px;
        max-height: 280px; overflow: auto;
    }
    .suggest-list.open { display: block; }
    .suggest-item {
        display: block; width: 100%; text-align: left; border: 0; background: transparent;
        padding: 8px 10px; cursor: pointer; color: var(--t1); font: inherit;
    }
    .suggest-item:hover, .suggest-item.active { background: var(--row-hover); }
    .suggest-name { font-weight: 650; font-size: .84rem; }
    .suggest-meta { color: var(--t2); font-size: .74rem; margin-top: 1px; }
    @media (max-width: 900px) {
        .identitas-grid, .kelas-box, .surat-add, .range-row { grid-template-columns: 1fr; }
        .kelas-box { max-height: none; }
    }
</style>
@endsection

@section('content')
    @php
        $codeVal = old('code01', $item['code01'] ?? ($isSuperadmin ? '' : $scopeCode01));
        $kelasIdVal = old('kelas_id', (is_array($item) && !empty($item['code03'])) ? [(string) $item['code03']] : []);
        if (! is_array($kelasIdVal)) {
            $kelasIdVal = $kelasIdVal !== '' && $kelasIdVal !== null ? [(string) $kelasIdVal] : [];
        }
        $kelasIdVal = array_values(array_filter(array_map('strval', $kelasIdVal), fn ($v) => $v !== ''));
        $kelasNameVal = old('kelas_nama', is_array($item) ? (string) ($item['kelas'] ?? '') : '');
        $detailsOld = old('details_json');
        $details = [];
        if (is_string($detailsOld) && $detailsOld !== '') {
            $decoded = json_decode($detailsOld, true);
            $details = is_array($decoded) ? $decoded : [];
        } elseif (!empty($item['details']) && is_array($item['details'])) {
            $details = $item['details'];
        }
        $sekolahLabel = [];
        foreach ($sekolah as $sch) {
            $sekolahLabel[trim((string) ($sch['code01'] ?? ''))] = trim((string) ($sch['sekolah'] ?? ''));
        }
    @endphp

    <form method="POST" action="{{ $item ? route('tahfid.jadwal.update') : route('tahfid.jadwal.store') }}" class="panel" id="jadwalForm">
        @csrf
        @if($item)
            <input type="hidden" name="id" value="{{ $item['id'] }}">
        @endif
        <input type="hidden" name="details_json" id="detailsJson" value="{{ json_encode($details, JSON_UNESCAPED_UNICODE) }}">

        <div class="identitas-box">
            <div class="identitas-title">Header jadwal</div>
            <div class="identitas-grid">
                <div class="field">
                    <label for="fUnit">Unit</label>
                    @if($isSuperadmin)
                        <select id="fUnit" name="code01" required>
                            <option value="">Pilih unit</option>
                            @foreach($sekolah as $sch)
                                <option value="{{ $sch['code01'] }}" {{ $codeVal === ($sch['code01'] ?? '') ? 'selected' : '' }}>{{ $sch['sekolah'] }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" value="{{ $sekolahLabel[$scopeCode01] ?? $scopeSekolah ?: $scopeCode01 }}" readonly>
                        <input type="hidden" id="fUnit" name="code01" value="{{ $scopeCode01 }}">
                    @endif
                </div>
                <div class="field">
                    <label>Kelas / tingkat</label>
                    <div id="kelasBox" class="kelas-box">
                        <div class="kelas-empty">Pilih unit terlebih dahulu, lalu centang kelas. Contoh: centang “Semua IX” untuk IX A sampai IX F.</div>
                    </div>
                    <div class="hint">Kelas diambil dari master kelas. Centang “Semua VII” agar semua rombel tingkat itu memakai jadwal yang sama.</div>
                </div>
            </div>
        </div>

        <div class="identitas-box">
            <div class="identitas-title">Detail surat</div>
            <div class="surat-add">
                <div class="field">
                    <label for="surahSearch">Surat</label>
                    <div class="search-wrap">
                        <input id="surahSearch" type="text" placeholder="Ketik nama surat, contoh Ikhlas" autocomplete="off">
                        <select id="surahPick" style="display:none">
                            <option value="">Pilih surat</option>
                            @foreach($surat as $s)
                                <option value="{{ $s['nomor'] }}" data-nama="{{ $s['namaLatin'] }}" data-max="{{ $s['jumlahAyat'] }}">
                                    {{ $s['nomor'] }}. {{ $s['namaLatin'] }} ({{ $s['jumlahAyat'] }} ayat)
                                </option>
                            @endforeach
                        </select>
                        <div class="suggest-list" id="surahSuggest"></div>
                    </div>
                </div>
                <div class="field">
                    <label for="modePick">Keterangan</label>
                    <select id="modePick">
                        <option value="lengkap">Lengkap</option>
                        <option value="batas">Batas ayat</option>
                    </select>
                </div>
                <div class="field">
                    <label>Juz</label>
                    <input id="juzPreview" type="text" value="—" readonly>
                </div>
                <button type="button" class="btn btn-primary" id="btnAddSurat"><i class="fas fa-plus"></i> Tambah</button>
            </div>
            <div class="range-row" id="rangeWrap">
                <div class="field">
                    <label for="ayatDari">Ayat dari</label>
                    <input id="ayatDari" type="number" min="1" max="1" value="1" step="1">
                </div>
                <div class="field">
                    <label for="ayatSampai">Ayat sampai</label>
                    <input id="ayatSampai" type="number" min="1" max="1" value="1" step="1">
                </div>
                <div class="range-max" id="ayatMaxHint">Pilih surat dulu untuk melihat batas ayat.</div>
            </div>
            <div class="hint" id="suratHint">Ketik nama surat, pilih lengkap atau batas ayat, lalu tambah ke tabel.</div>
        </div>

        <div class="table-wrap" style="max-height:none;padding:0 16px 8px">
            <table class="data" id="detailTable">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Surat</th>
                        <th>Juz</th>
                        <th>Ayat</th>
                        <th style="width:90px"></th>
                    </tr>
                </thead>
                <tbody id="detailBody"></tbody>
            </table>
            <div class="empty" id="detailEmpty">Belum ada surat. Contoh MTs kelas IX: Al-Fatihah, Al-Ikhlas, Al-Falaq.</div>
        </div>

        <div class="form-actions">
            <a href="{{ route('tahfid.jadwal.index') }}" class="btn btn-ghost">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan jadwal</button>
        </div>
    </form>
@endsection

@section('scripts')
<script>
(function () {
    var surat = @json($surat);
    var kelasUrl = @json(route('tahfid.kelas'));
    var selectedIds = @json($kelasIdVal);
    var selectedName = @json((string) $kelasNameVal);
    var unitEl = document.getElementById('fUnit');
    var kelasBox = document.getElementById('kelasBox');
    var surahPick = document.getElementById('surahPick');
    var modePick = document.getElementById('modePick');
    var rangeWrap = document.getElementById('rangeWrap');
    var ayatDari = document.getElementById('ayatDari');
    var ayatSampai = document.getElementById('ayatSampai');
    var ayatMaxHint = document.getElementById('ayatMaxHint');
    var juzPreview = document.getElementById('juzPreview');
    var details = [];
    try { details = JSON.parse(document.getElementById('detailsJson').value || '[]') || []; } catch (e) { details = []; }

    var juzStarts = [
        [1,1,1],[2,2,142],[3,2,253],[4,3,93],[5,4,24],[6,4,148],[7,5,82],[8,6,111],[9,7,88],[10,8,41],
        [11,9,93],[12,11,6],[13,12,53],[14,15,1],[15,17,1],[16,18,75],[17,21,1],[18,23,1],[19,25,21],[20,27,56],
        [21,29,46],[22,33,31],[23,36,28],[24,39,32],[25,41,47],[26,46,1],[27,51,31],[28,58,1],[29,67,1],[30,78,1]
    ];
    function juzFor(surah, ayah) {
        var juz = 1;
        juzStarts.forEach(function (row) {
            if (surah > row[1] || (surah === row[1] && ayah >= row[2])) juz = row[0];
        });
        return juz;
    }
    function juzLabel(a, b) { return a === b ? ('Juz ' + a) : ('Juz ' + a + '–' + b); }

    var surat = @json($surat);
    var surahSearch = document.getElementById('surahSearch');
    var surahSuggest = document.getElementById('surahSuggest');
    var selectedSurahObj = null;
    if (!Array.isArray(surat) || !surat.length) {
        surat = [];
        Array.prototype.forEach.call(surahPick.options, function (opt) {
            if (!opt.value) return;
            surat.push({
                nomor: parseInt(opt.value, 10),
                namaLatin: opt.getAttribute('data-nama') || opt.textContent,
                nama: opt.getAttribute('data-nama') || '',
                jumlahAyat: parseInt(opt.getAttribute('data-max') || '0', 10)
            });
        });
    }

    function surahLabel(s) {
        return s.nomor + '. ' + (s.namaLatin || s.nama || '') + ' (' + s.jumlahAyat + ' ayat)';
    }
    function filterSurah(q) {
        q = (q || '').toLowerCase().trim();
        if (!q) return surat.slice(0, 20);
        return surat.filter(function (s) {
            return String(s.nomor) === q
                || String(s.nomor).indexOf(q) === 0
                || (s.namaLatin || '').toLowerCase().indexOf(q) !== -1
                || (s.nama || '').toLowerCase().indexOf(q) !== -1
                || (s.arti || '').toLowerCase().indexOf(q) !== -1
                || (s.nomor === 112 && ('qulhu qul hu ikhlas').indexOf(q) !== -1)
                || (s.nomor === 113 && ('falaq').indexOf(q) !== -1)
                || (s.nomor === 114 && q === 'nas');
        }).slice(0, 20);
    }
    function renderSurahSuggest(rows) {
        surahSuggest.innerHTML = '';
        if (!rows.length) {
            surahSuggest.innerHTML = '<div class="suggest-meta" style="padding:10px">Surat tidak ditemukan</div>';
            surahSuggest.classList.add('open');
            return;
        }
        rows.forEach(function (s) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'suggest-item';
            btn.innerHTML = '<div class="suggest-name"></div><div class="suggest-meta"></div>';
            btn.querySelector('.suggest-name').textContent = s.nomor + '. ' + (s.namaLatin || s.nama);
            btn.querySelector('.suggest-meta').textContent = s.jumlahAyat + ' ayat' + (s.arti ? (' · ' + s.arti) : '');
            btn.addEventListener('click', function () { pickSurah(s); });
            surahSuggest.appendChild(btn);
        });
        surahSuggest.classList.add('open');
    }
    function pickSurah(s) {
        selectedSurahObj = s;
        surahPick.value = String(s.nomor);
        surahSearch.value = surahLabel(s);
        surahSuggest.classList.remove('open');
        toggleRange();
    }
    surahSearch.addEventListener('focus', function () {
        renderSurahSuggest(filterSurah(selectedSurahObj ? '' : surahSearch.value));
    });
    surahSearch.addEventListener('input', function () {
        selectedSurahObj = null;
        surahPick.value = '';
        renderSurahSuggest(filterSurah(surahSearch.value));
        refreshJuz();
    });
    document.addEventListener('click', function (e) {
        if (!surahSuggest.contains(e.target) && e.target !== surahSearch) {
            surahSuggest.classList.remove('open');
        }
    });

    function selectedSurah() {
        if (selectedSurahObj) return selectedSurahObj;
        var nomor = parseInt(surahPick.value, 10) || 0;
        return surat.find(function (s) { return Number(s.nomor) === nomor; }) || null;
    }
    function currentRange() {
        var s = selectedSurah();
        if (!s) return null;
        var lengkap = modePick.value === 'lengkap';
        var max = Number(s.jumlahAyat) || 1;
        var dari = lengkap ? 1 : (parseInt(ayatDari.value, 10) || 0);
        var sampai = lengkap ? max : (parseInt(ayatSampai.value, 10) || 0);
        return { s: s, lengkap: lengkap, dari: dari, sampai: sampai, max: max };
    }
    function clampAyat() {
        var s = selectedSurah();
        var max = s ? (Number(s.jumlahAyat) || 1) : 1;
        ayatDari.min = 1;
        ayatSampai.min = 1;
        ayatDari.max = max;
        ayatSampai.max = max;
        var dari = parseInt(ayatDari.value, 10);
        var sampai = parseInt(ayatSampai.value, 10);
        if (isNaN(dari) || dari < 1) dari = 1;
        if (dari > max) dari = max;
        if (isNaN(sampai) || sampai < 1) sampai = dari;
        if (sampai > max) sampai = max;
        if (sampai < dari) sampai = dari;
        ayatDari.value = String(dari);
        ayatSampai.value = String(sampai);
        if (ayatMaxHint) {
            ayatMaxHint.textContent = s
                ? ('Maksimal ayat 1–' + max + ' untuk ' + (s.namaLatin || s.nama))
                : 'Pilih surat dulu untuk melihat batas ayat.';
        }
        refreshJuz();
    }
    function refreshJuz() {
        var r = currentRange();
        if (!r || r.dari < 1 || r.sampai < r.dari || r.sampai > r.max) {
            juzPreview.value = '—';
            return;
        }
        juzPreview.value = juzLabel(juzFor(r.s.nomor, r.dari), juzFor(r.s.nomor, r.sampai));
    }
    function toggleRange() {
        if (modePick.value === 'batas') rangeWrap.classList.add('open');
        else rangeWrap.classList.remove('open');
        clampAyat();
    }
    modePick.addEventListener('change', toggleRange);
    ayatDari.addEventListener('input', clampAyat);
    ayatDari.addEventListener('change', clampAyat);
    ayatSampai.addEventListener('input', clampAyat);
    ayatSampai.addEventListener('change', clampAyat);

    function renderDetails() {
        var body = document.getElementById('detailBody');
        var empty = document.getElementById('detailEmpty');
        body.innerHTML = '';
        empty.style.display = details.length ? 'none' : 'block';
        details.forEach(function (row, idx) {
            var tr = document.createElement('tr');
            var lengkap = !!row.is_lengkap;
            var dari = Number(row.ayat_dari || 1);
            var sampai = Number(row.ayat_sampai || dari);
            var jd = Number(row.juz_dari || juzFor(Number(row.surah_nomor), dari));
            var js = Number(row.juz_sampai || juzFor(Number(row.surah_nomor), sampai));
            tr.innerHTML =
                '<td class="mono">' + (idx + 1) + '</td>' +
                '<td><div class="cell-main">' + (row.surah_nama || ('Surat ' + row.surah_nomor)) + '</div><div class="cell-sub">No. ' + row.surah_nomor + '</div></td>' +
                '<td>' + juzLabel(jd, js) + '</td>' +
                '<td>' + (lengkap ? ('Lengkap (ayat ' + dari + '–' + sampai + ')') : ('Ayat ' + dari + '–' + sampai)) + '</td>' +
                '<td><button type="button" class="btn btn-ghost btn-sm" data-idx="' + idx + '">Hapus</button></td>';
            body.appendChild(tr);
        });
        body.querySelectorAll('button[data-idx]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                details.splice(parseInt(btn.getAttribute('data-idx'), 10), 1);
                renderDetails();
            });
        });
        document.getElementById('detailsJson').value = JSON.stringify(details);
    }

    document.getElementById('btnAddSurat').addEventListener('click', function () {
        var r = currentRange();
        if (!r) {
            window.showToast('Pilih surat terlebih dahulu.', 'error');
            return;
        }
        if (r.dari < 1 || r.sampai < r.dari || r.sampai > r.max) {
            window.showToast('Rentang ayat tidak valid (1–' + r.max + ').', 'error');
            return;
        }
        details.push({
            surah_nomor: r.s.nomor,
            surah_nama: r.s.namaLatin || r.s.nama,
            is_lengkap: r.lengkap,
            ayat_dari: r.dari,
            ayat_sampai: r.sampai,
            juz_dari: juzFor(r.s.nomor, r.dari),
            juz_sampai: juzFor(r.s.nomor, r.sampai)
        });
        renderDetails();
    });

    function fillKelas(code01, selected) {
        selected = Array.isArray(selected) ? selected.map(String) : (selected ? [String(selected)] : []);
        if (!code01) {
            kelasBox.innerHTML = '<div class="kelas-empty">Pilih unit terlebih dahulu, lalu centang kelas.</div>';
            return;
        }
        fetch(kelasUrl + '?code01=' + encodeURIComponent(code01), { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (json && json.error) {
                    window.showToast(json.error, 'error');
                }
                renderKelas((json && json.data) ? json.data : [], selected);
            })
            .catch(function () {
                window.showToast('Gagal memuat daftar kelas.', 'error');
            });
    }

    function rowId(row) {
        if (!row || typeof row !== 'object') return '';
        return String(row.id || row.code03 || '');
    }
    function rowName(row) {
        if (!row || typeof row !== 'object') return String(row || '');
        return String(row.kelas || '');
    }
    function tingkatOf(kelas) {
        var m = String(kelas || '').trim().toUpperCase().match(/^(XII|XI|IX|X|VIII|VII|VI|IV|V|III|II|I)(?=\s|$|[^A-Z])/);
        return m ? m[1] : 'Lainnya';
    }
    function isSelected(row, selected) {
        var id = rowId(row);
        if (id && selected.indexOf(id) !== -1) return true;
        if (selectedName && rowName(row) === selectedName) return true;
        return false;
    }

    function renderKelas(rows, selected) {
        selected = selected || [];
        if (!rows.length) {
            kelasBox.innerHTML = '<div class="kelas-empty">Tidak ada kelas master pada unit ini.</div>';
            return;
        }
        var groups = {};
        var order = [];
        rows.forEach(function (row) {
            var g = tingkatOf(rowName(row));
            if (!groups[g]) {
                groups[g] = [];
                order.push(g);
            }
            groups[g].push(row);
        });
        kelasBox.innerHTML = '';
        order.forEach(function (g, gi) {
            var wrap = document.createElement('div');
            wrap.className = 'kelas-group';
            var allId = 'tingkatAll-' + gi;
            var head = document.createElement('div');
            head.className = 'kelas-group-head';
            head.innerHTML = '<label><input type="checkbox" id="' + allId + '"> ' + (g === 'Lainnya' ? 'Lainnya' : ('Semua ' + g)) + '</label>' +
                '<span class="cell-sub">' + groups[g].length + ' kelas</span>';
            var chips = document.createElement('div');
            chips.className = 'kelas-chips';
            groups[g].forEach(function (row) {
                var lab = document.createElement('label');
                lab.className = 'kelas-chip';
                var id = rowId(row);
                var checked = isSelected(row, selected) ? ' checked' : '';
                lab.innerHTML = '<input type="checkbox" name="kelas_id[]" value="" class="js-kelas"' + checked + '> <span></span>';
                lab.querySelector('input').value = id;
                lab.querySelector('span').textContent = rowName(row);
                chips.appendChild(lab);
            });
            wrap.appendChild(head);
            wrap.appendChild(chips);
            kelasBox.appendChild(wrap);
            var allBox = head.querySelector('input');
            var itemBoxes = chips.querySelectorAll('.js-kelas');
            function syncAll() {
                var n = 0;
                itemBoxes.forEach(function (el) { if (el.checked) n++; });
                allBox.checked = n === itemBoxes.length && itemBoxes.length > 0;
                allBox.indeterminate = n > 0 && n < itemBoxes.length;
            }
            allBox.addEventListener('change', function () {
                itemBoxes.forEach(function (el) { el.checked = allBox.checked; });
            });
            itemBoxes.forEach(function (el) { el.addEventListener('change', syncAll); });
            syncAll();
        });
    }

    if (unitEl) {
        unitEl.addEventListener('change', function () { fillKelas(unitEl.value, []); });
        if (unitEl.value) fillKelas(unitEl.value, selectedIds);
    }

    document.getElementById('jadwalForm').addEventListener('submit', function (e) {
        document.getElementById('detailsJson').value = JSON.stringify(details);
        var picked = kelasBox.querySelectorAll('.js-kelas:checked');
        if (!picked.length) {
            e.preventDefault();
            window.showToast('Centang minimal satu kelas.', 'error');
            return;
        }
        if (!details.length) {
            e.preventDefault();
            window.showToast('Tambahkan minimal satu surat.', 'error');
        }
    });

    renderDetails();
    toggleRange();
})();
</script>
@endsection
