@extends('layouts.approval')

@section('title', 'Katalog Prestasi')
@section('heading', 'Katalog Prestasi')

@section('content')
    @php
        $tingkatByKat = [];
        foreach ($tingkat as $row) {
            $kid = (int) ($row['kategori_id'] ?? 0);
            $tingkatByKat[$kid][] = $row;
        }
        $poinByTingkat = [];
        foreach ($poin as $row) {
            $tid = (int) ($row['tingkat_id'] ?? 0);
            $poinByTingkat[$tid][] = $row;
        }
        $sekolah = $sekolah ?? [];
        $sekolahLabel = [];
        foreach ($sekolah as $sch) {
            $code = trim((string) ($sch['code01'] ?? ''));
            if ($code !== '') {
                $sekolahLabel[$code] = trim((string) ($sch['sekolah'] ?? $code));
            }
        }
        $unitName = static function (string $code01) use ($sekolahLabel): string {
            $code01 = trim($code01);
            if ($code01 === '') {
                return 'Semua unit';
            }
            return $sekolahLabel[$code01] ?? $code01;
        };
    @endphp

    <div class="panel table-card">
        <div class="filters">
            <div class="filters-grid" style="grid-template-columns: 1.3fr .9fr .9fr .8fr .9fr auto;">
                <div class="field">
                    <label for="katQ">Cari</label>
                    <input id="katQ" type="text" placeholder="Kategori, tingkat, atau capaian">
                </div>
                <div class="field">
                    <label for="katKategori">Kategori</label>
                    <select id="katKategori">
                        <option value="">Semua kategori</option>
                        @foreach($kategori as $opt)
                            <option value="{{ $opt['id'] }}">{{ $opt['kode'] }} · {{ $opt['nama'] }} ({{ $unitName((string) ($opt['code01'] ?? '')) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="katTingkat">Tingkat</label>
                    <select id="katTingkat">
                        <option value="">Semua tingkat</option>
                        @foreach($kategori as $katGroup)
                            @php $levelOpts = $tingkatByKat[(int) ($katGroup['id'] ?? 0)] ?? []; @endphp
                            @if(count($levelOpts) > 0)
                                <optgroup label="{{ $katGroup['kode'] }} · {{ $katGroup['nama'] }} ({{ $unitName((string) ($katGroup['code01'] ?? '')) }})" data-kategori-id="{{ $katGroup['id'] }}">
                                    @foreach($levelOpts as $opt)
                                        <option value="{{ $opt['id'] }}" data-kategori-id="{{ $opt['kategori_id'] }}">{{ $opt['nama'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="katPoin">Poin</label>
                    <select id="katPoin">
                        <option value="">Semua</option>
                        <option value="zero">Belum diisi (0)</option>
                        <option value="set">Sudah diisi</option>
                    </select>
                </div>
                <div class="field">
                    <label for="katUnit">Unit</label>
                    <select id="katUnit">
                        <option value="">Semua unit</option>
                        <option value="*">Hanya yang berlaku semua</option>
                        @foreach($sekolah as $sch)
                            <option value="{{ $sch['code01'] }}">{{ $sch['sekolah'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="btn btn-ghost" id="katReset">Reset</button>
            </div>
        </div>
        <div class="table-toolbar">
            <span class="count-pill" id="katalogCount">{{ count($kategori) }} kategori · {{ count($tingkat) }} tingkat · {{ count($poin) }} capaian</span>
            <button type="button" class="btn btn-primary" id="btnAddKategori">Tambah kategori</button>
        </div>
        <div class="table-wrap" style="max-height:none">
            @if(count($kategori) === 0)
                <div class="empty">Belum ada kategori. Tambah kategori, lalu tingkat, lalu capaian beserta poinnya.</div>
            @else
                <div class="empty" id="katalogEmpty" style="display:none;">Tidak ada data yang cocok dengan filter.</div>
                @foreach($kategori as $kat)
                    @php
                        $kid = (int) ($kat['id'] ?? 0);
                        $levels = $tingkatByKat[$kid] ?? [];
                    @endphp
                    <div class="cat-block" data-id="{{ $kid }}" data-nama="{{ mb_strtolower(trim(($kat['kode'] ?? '').' '.($kat['nama'] ?? ''))) }}" data-code01="{{ trim((string) ($kat['code01'] ?? '')) }}">
                        <div class="cat-head">
                            <span class="cat-kode">{{ $kat['kode'] ?: '-' }}</span>
                            <div class="cat-title">
                                {{ $kat['nama'] ?: '-' }}
                                <div class="cell-sub">{{ $unitName((string) ($kat['code01'] ?? '')) }}</div>
                            </div>
                            <span class="count-pill">{{ count($levels) }} tingkat</span>
                            <button type="button" class="btn btn-ghost btn-sm btn-add-tingkat"
                                data-kategori-id="{{ $kid }}">Tambah tingkat</button>
                            <button type="button" class="btn btn-ghost btn-sm btn-edit-kategori"
                                data-id="{{ $kid }}"
                                data-kode="{{ $kat['kode'] }}"
                                data-nama="{{ $kat['nama'] }}"
                                data-urut="{{ $kat['urut'] ?? '' }}"
                                data-code01="{{ trim((string) ($kat['code01'] ?? '')) }}">Ubah</button>
                            <form method="POST" action="{{ route('approval.katalog.delete') }}" class="js-confirm" data-title="Hapus kategori?" data-text="{{ $kat['nama'] }} akan dihapus. Tingkat di dalamnya harus kosong.">
                                @csrf
                                <input type="hidden" name="entity" value="kategori">
                                <input type="hidden" name="id" value="{{ $kid }}">
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                        @forelse($levels as $lvl)
                            @php
                                $tid = (int) ($lvl['id'] ?? 0);
                                $points = $poinByTingkat[$tid] ?? [];
                            @endphp
                            <div class="lvl-block" data-id="{{ $tid }}" data-kategori-id="{{ $kid }}" data-nama="{{ mb_strtolower(trim((string) ($lvl['nama'] ?? ''))) }}">
                                <div class="lvl-head">
                                    <div class="lvl-title">{{ $lvl['nama'] ?: '-' }}</div>
                                    <button type="button" class="btn btn-ghost btn-sm btn-add-poin"
                                        data-tingkat-id="{{ $tid }}">Tambah capaian</button>
                                    <button type="button" class="btn btn-ghost btn-sm btn-edit-tingkat"
                                        data-id="{{ $tid }}"
                                        data-kategori-id="{{ $kid }}"
                                        data-nama="{{ $lvl['nama'] }}"
                                        data-urut="{{ $lvl['urut'] ?? '' }}">Ubah</button>
                                    <form method="POST" action="{{ route('approval.katalog.delete') }}" class="js-confirm" data-title="Hapus tingkat?" data-text="{{ $lvl['nama'] }} akan dihapus. Capaian di dalamnya harus kosong.">
                                        @csrf
                                        <input type="hidden" name="entity" value="tingkat">
                                        <input type="hidden" name="id" value="{{ $tid }}">
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                                <table class="data poin-table">
                                    <thead>
                                        <tr>
                                            <th>Capaian</th>
                                            <th style="width:120px">Poin</th>
                                            <th style="width:90px">Urut</th>
                                            <th style="width:150px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($points as $p)
                                        <tr class="poin-row" data-nama="{{ mb_strtolower(trim((string) ($p['nama'] ?? ''))) }}" data-nilai="{{ (float) ($p['nilai'] ?? 0) }}">
                                            <td>{{ $p['nama'] ?: '-' }}</td>
                                            <td class="mono">{{ number_format((float) ($p['nilai'] ?? 0), 2, ',', '.') }}</td>
                                            <td class="mono">{{ $p['urut'] ?? '-' }}</td>
                                            <td>
                                                <div class="actions">
                                                    <button type="button" class="btn btn-ghost btn-sm btn-edit-poin"
                                                        data-id="{{ $p['id'] }}"
                                                        data-tingkat-id="{{ $tid }}"
                                                        data-nama="{{ $p['nama'] }}"
                                                        data-nilai="{{ $p['nilai'] }}"
                                                        data-urut="{{ $p['urut'] ?? '' }}">Ubah</button>
                                                    <form method="POST" action="{{ route('approval.katalog.delete') }}" class="js-confirm" data-title="Hapus capaian?" data-text="{{ $p['nama'] }} akan dihapus.">
                                                        @csrf
                                                        <input type="hidden" name="entity" value="poin">
                                                        <input type="hidden" name="id" value="{{ $p['id'] }}">
                                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="cell-sub">Belum ada capaian. Poin default 0 sampai diisi.</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @empty
                            <div class="lvl-block"><p class="cell-sub" style="padding:8px 0 12px">Belum ada tingkat pada kategori ini.</p></div>
                        @endforelse
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <div class="modal-bg" id="katalogModal">
        <div class="modal">
            <h3 id="katModalTitle">Tambah</h3>
            <form method="POST" id="katalogForm">
                @csrf
                <input type="hidden" name="id" id="kId">
                <input type="hidden" name="entity" id="kEntity">
                <input type="hidden" name="kategori_id" id="kKategoriId">
                <input type="hidden" name="tingkat_id" id="kTingkatId">
                <div class="modal-grid">
                    <div class="field" id="kodeField">
                        <label for="kKode">Kode</label>
                        <input id="kKode" name="kode" type="text" maxlength="10" placeholder="Contoh: A">
                    </div>
                    <div class="field">
                        <label for="kNama">Nama</label>
                        <input id="kNama" name="nama" type="text" maxlength="120" required>
                    </div>
                    <div class="field" id="nilaiField">
                        <label for="kNilai">Poin</label>
                        <input id="kNilai" name="nilai" type="number" min="0" step="0.01" value="0">
                    </div>
                    <div class="field">
                        <label for="kUrut">Urutan</label>
                        <input id="kUrut" name="urut" type="number" min="0" max="9999" placeholder="Otomatis jika kosong">
                    </div>
                    <div class="field" id="unitField">
                        <label for="kCode01">Unit</label>
                        <select id="kCode01" name="code01">
                            <option value="">Semua unit</option>
                            @foreach($sekolah as $sch)
                                <option value="{{ $sch['code01'] }}">{{ $sch['sekolah'] }}</option>
                            @endforeach
                        </select>
                        <div class="cell-sub" style="margin-top:4px">Kosong = semua unit. Isi unit jika kategori beserta tingkat dan poinnya hanya untuk unit itu. Poin beda per unit: buat kategori terpisah.</div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-ghost" id="katCancel">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    var modal = document.getElementById('katalogModal');
    var form = document.getElementById('katalogForm');
    var title = document.getElementById('katModalTitle');
    var kodeField = document.getElementById('kodeField');
    var nilaiField = document.getElementById('nilaiField');
    var unitField = document.getElementById('unitField');
    var storeUrl = @json(route('approval.katalog.store'));
    var updateUrl = @json(route('approval.katalog.update'));

    function openModal(opts) {
        form.reset();
        document.getElementById('kId').value = opts.id || '';
        document.getElementById('kEntity').value = opts.entity;
        document.getElementById('kKategoriId').value = opts.kategoriId || '';
        document.getElementById('kTingkatId').value = opts.tingkatId || '';
        document.getElementById('kKode').value = opts.kode || '';
        document.getElementById('kNama').value = opts.nama || '';
        document.getElementById('kNilai').value = opts.nilai != null ? opts.nilai : 0;
        document.getElementById('kUrut').value = opts.urut || '';
        document.getElementById('kCode01').value = opts.code01 || '';
        kodeField.style.display = opts.entity === 'kategori' ? 'block' : 'none';
        nilaiField.style.display = opts.entity === 'poin' ? 'block' : 'none';
        unitField.style.display = opts.entity === 'kategori' ? 'block' : 'none';
        document.getElementById('kKode').required = opts.entity === 'kategori';
        form.action = opts.id ? updateUrl : storeUrl;
        title.textContent = (opts.id ? 'Ubah ' : 'Tambah ') + (
            opts.entity === 'kategori' ? 'kategori' : (opts.entity === 'tingkat' ? 'tingkat' : 'capaian')
        );
        modal.classList.add('open');
    }

    document.getElementById('btnAddKategori').addEventListener('click', function () {
        openModal({ entity: 'kategori' });
    });
    document.querySelectorAll('.btn-add-tingkat').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal({ entity: 'tingkat', kategoriId: btn.getAttribute('data-kategori-id') });
        });
    });
    document.querySelectorAll('.btn-add-poin').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal({ entity: 'poin', tingkatId: btn.getAttribute('data-tingkat-id') });
        });
    });
    document.querySelectorAll('.btn-edit-kategori').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal({
                entity: 'kategori',
                id: btn.getAttribute('data-id'),
                kode: btn.getAttribute('data-kode'),
                nama: btn.getAttribute('data-nama'),
                urut: btn.getAttribute('data-urut'),
                code01: btn.getAttribute('data-code01') || ''
            });
        });
    });
    document.querySelectorAll('.btn-edit-tingkat').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal({
                entity: 'tingkat',
                id: btn.getAttribute('data-id'),
                kategoriId: btn.getAttribute('data-kategori-id'),
                nama: btn.getAttribute('data-nama'),
                urut: btn.getAttribute('data-urut')
            });
        });
    });
    document.querySelectorAll('.btn-edit-poin').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal({
                entity: 'poin',
                id: btn.getAttribute('data-id'),
                tingkatId: btn.getAttribute('data-tingkat-id'),
                nama: btn.getAttribute('data-nama'),
                nilai: btn.getAttribute('data-nilai'),
                urut: btn.getAttribute('data-urut')
            });
        });
    });
    document.getElementById('katCancel').addEventListener('click', function () { modal.classList.remove('open'); });
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.classList.remove('open'); });

    var qEl = document.getElementById('katQ');
    var katEl = document.getElementById('katKategori');
    var lvlEl = document.getElementById('katTingkat');
    var poinEl = document.getElementById('katPoin');
    var unitEl = document.getElementById('katUnit');
    var countEl = document.getElementById('katalogCount');
    var emptyEl = document.getElementById('katalogEmpty');
    var lvlOptions = lvlEl ? Array.prototype.slice.call(lvlEl.querySelectorAll('option[data-kategori-id]')) : [];

    function syncTingkatOptions() {
        if (!lvlEl) return;
        var kid = katEl ? katEl.value : '';
        var keep = lvlEl.value;
        var stillValid = false;
        lvlOptions.forEach(function (opt) {
            var show = !kid || opt.getAttribute('data-kategori-id') === kid;
            opt.hidden = !show;
            if (show && opt.value === keep) stillValid = true;
        });
        lvlEl.querySelectorAll('optgroup').forEach(function (group) {
            var show = !kid || group.getAttribute('data-kategori-id') === kid;
            group.hidden = !show;
            group.disabled = !show;
        });
        if (!stillValid) lvlEl.value = '';
    }

    function applyKatalogFilter() {
        var q = (qEl && qEl.value || '').trim().toLowerCase();
        var kid = katEl ? katEl.value : '';
        var lid = lvlEl ? lvlEl.value : '';
        var poinMode = poinEl ? poinEl.value : '';
        var unit = unitEl ? unitEl.value : '';
        var visKat = 0;
        var visLvl = 0;
        var visPoin = 0;

        document.querySelectorAll('.cat-block').forEach(function (cat) {
            var catId = cat.getAttribute('data-id') || '';
            var catHit = !q || (cat.getAttribute('data-nama') || '').indexOf(q) !== -1;
            var katOk = !kid || catId === kid;
            var catCode = (cat.getAttribute('data-code01') || '').trim();
            var catUnitOk = !unit || (unit === '*' ? catCode === '' : (catCode === '' || catCode === unit));
            var anyLvl = false;

            cat.querySelectorAll('.lvl-block').forEach(function (lvl) {
                var lvlId = lvl.getAttribute('data-id') || '';
                var lvlHit = !q || (lvl.getAttribute('data-nama') || '').indexOf(q) !== -1;
                var lvlOk = !lid || lvlId === lid;
                var rows = lvl.querySelectorAll('tr.poin-row');
                var anyPoin = false;

                rows.forEach(function (row) {
                    var poinHit = !q || (row.getAttribute('data-nama') || '').indexOf(q) !== -1;
                    var nilai = parseFloat(row.getAttribute('data-nilai') || '0') || 0;
                    var poinOk = !poinMode || (poinMode === 'zero' && nilai === 0) || (poinMode === 'set' && nilai > 0);
                    var show = katOk && lvlOk && catUnitOk && poinOk && (catHit || lvlHit || poinHit);
                    row.style.display = show ? '' : 'none';
                    if (show) {
                        anyPoin = true;
                        visPoin += 1;
                    }
                });

                var emptyRow = lvl.querySelector('tbody tr:not(.poin-row)');
                var showLvl = katOk && lvlOk && catUnitOk && (anyPoin || (rows.length === 0 && !poinMode && (catHit || lvlHit || !q)));
                lvl.style.display = showLvl ? '' : 'none';
                if (emptyRow) emptyRow.style.display = showLvl && rows.length === 0 ? '' : 'none';
                if (showLvl) {
                    anyLvl = true;
                    visLvl += 1;
                }
            });

            var showCat = katOk && catUnitOk && anyLvl;
            if (!anyLvl && katOk && catUnitOk && !lid && !poinMode) {
                var hasBlocks = cat.querySelectorAll('.lvl-block').length === 0;
                if (hasBlocks && (!q || catHit)) showCat = true;
            }
            cat.style.display = showCat ? '' : 'none';
            if (showCat) visKat += 1;
        });

        if (countEl) countEl.textContent = visKat + ' kategori · ' + visLvl + ' tingkat · ' + visPoin + ' capaian';
        if (emptyEl) emptyEl.style.display = visKat === 0 ? 'block' : 'none';
    }

    syncTingkatOptions();
    [qEl, katEl, lvlEl, poinEl, unitEl].forEach(function (el) {
        if (!el) return;
        el.addEventListener('input', applyKatalogFilter);
        el.addEventListener('change', function () {
            if (el === katEl) syncTingkatOptions();
            applyKatalogFilter();
        });
    });
    var resetBtn = document.getElementById('katReset');
    if (resetBtn) resetBtn.addEventListener('click', function () {
        if (qEl) qEl.value = '';
        if (katEl) katEl.value = '';
        if (lvlEl) lvlEl.value = '';
        if (poinEl) poinEl.value = '';
        if (unitEl) unitEl.value = '';
        syncTingkatOptions();
        applyKatalogFilter();
    });
    applyKatalogFilter();
})();
</script>
@endsection
