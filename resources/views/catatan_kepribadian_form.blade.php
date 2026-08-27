@extends('layouts.approval')

@section('title', $item ? 'Ubah Catatan Kepribadian' : 'Tambah Catatan Kepribadian')
@section('heading', $item ? 'Ubah Catatan Kepribadian' : 'Tambah Catatan Kepribadian')

@section('head')
<style>
    .identitas-box { padding: 14px 16px 4px; }
    .identitas-title {
        font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
        color: var(--t2); margin-bottom: 10px;
    }
    .identitas-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .identitas-grid .span-2 { grid-column: 1 / -1; }
    .search-wrap { position: relative; }
    .suggest-list {
        display: none; position: absolute; left: 0; right: 0; top: calc(100% + 4px); z-index: 30;
        background: var(--surface); border: 1px solid var(--border); border-radius: 4px;
        max-height: 240px; overflow: auto;
    }
    .suggest-list.open { display: block; }
    .suggest-item {
        display: block; width: 100%; text-align: left; border: 0; background: transparent;
        padding: 8px 10px; cursor: pointer; color: var(--t1); font: inherit;
    }
    .suggest-item:hover { background: var(--row-hover); }
    .suggest-name { font-weight: 650; font-size: .84rem; }
    .suggest-meta { color: var(--t2); font-size: .74rem; margin-top: 1px; }
    .suggest-empty { padding: 10px; color: var(--t2); font-size: .8rem; }
    .form-actions { display: flex; justify-content: flex-end; gap: 8px; padding: 12px 16px 16px; }
    @media (max-width: 700px) { .identitas-grid { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
    @php
        $nama = old('nama', $item['nmcust'] ?? '');
        $kelas = old('kelas', $item['kelas'] ?? '');
        $nis = old('nis', $item['nocust'] ?? '');
        $tahunVal = old('tahun_akademik', $item['bta'] ?? '');
        $semVal = (string) old('semester', $item['semester'] ?? '1');
        $jenis = old('jenis_pelanggaran', $item['jenis_pelanggaran'] ?? '');
        $pembinaan = old('bentuk_pembinaan', $item['bentuk_pembinaan'] ?? '');
        $skor = old('skor', isset($item['skor']) ? number_format((float) $item['skor'], 2, '.', '') : '');
    @endphp

    <form method="POST" action="{{ $item ? route('catatan.kepribadian.update') : route('catatan.kepribadian.store') }}" class="panel" id="catatanForm">
        @csrf
        @if($item)
            <input type="hidden" name="id" value="{{ $item['id'] }}">
        @endif

        <div class="identitas-box">
            <div class="identitas-title">Identitas siswi</div>
            <div class="identitas-grid">
                <div class="field span-2">
                    <label for="siswaQ">Cari NIS / nama</label>
                    <div class="search-wrap">
                        <input id="siswaQ" type="text" placeholder="Ketik NIS atau nama siswi, lalu pilih" autocomplete="off" value="{{ $nis }}">
                        <div class="suggest-list" id="siswaSuggest"></div>
                    </div>
                </div>
                <div class="field">
                    <label for="fNama">Nama</label>
                    <input id="fNama" name="nama" type="text" value="{{ $nama }}" readonly>
                </div>
                <div class="field">
                    <label for="fKelas">Kelas</label>
                    <input id="fKelas" name="kelas" type="text" value="{{ $kelas }}" readonly>
                </div>
                <div class="field">
                    <label for="fNis">NIS</label>
                    <input id="fNis" name="nis" type="text" value="{{ $nis }}" required readonly>
                </div>
                <div class="field">
                    <label for="fTahun">Tahun Ajaran</label>
                    <select id="fTahun" name="tahun_akademik" required>
                        <option value="">Pilih tahun ajaran</option>
                        @foreach($tahunAkademik as $tahun)
                            <option value="{{ $tahun }}" {{ $tahunVal === $tahun ? 'selected' : '' }}>{{ $tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="fSemester">Semester</label>
                    <select id="fSemester" name="semester" required>
                        <option value="1" {{ $semVal === '1' ? 'selected' : '' }}>Ganjil</option>
                        <option value="2" {{ $semVal === '2' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="identitas-box" style="border-top:1px solid var(--border)">
            <div class="identitas-title">Rincian catatan</div>
            <div class="identitas-grid">
                <div class="field span-2">
                    <label for="fJenis">Jenis pelanggaran</label>
                    <textarea id="fJenis" name="jenis_pelanggaran" maxlength="500" required placeholder="Contoh: Terlambat 2 kali, tidak sholat jamaah, pacaran">{{ $jenis }}</textarea>
                </div>
                <div class="field span-2">
                    <label for="fPembinaan">Bentuk pembinaan</label>
                    <textarea id="fPembinaan" name="bentuk_pembinaan" maxlength="500" required placeholder="Diisi Musrifah">{{ $pembinaan }}</textarea>
                </div>
                <div class="field">
                    <label for="fSkor">Skor</label>
                    <input id="fSkor" name="skor" type="number" min="0" step="0.01" required value="{{ $skor }}" placeholder="0">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('catatan.kepribadian.index') }}" class="btn btn-ghost">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
@endsection

@section('scripts')
<script>
(function () {
    var qEl = document.getElementById('siswaQ');
    var box = document.getElementById('siswaSuggest');
    var namaEl = document.getElementById('fNama');
    var kelasEl = document.getElementById('fKelas');
    var nisEl = document.getElementById('fNis');
    var searchUrl = @json(route('catatan.kepribadian.siswa'));
    var timer = null;

    function closeSuggest() { if (box) box.classList.remove('open'); }

    function pick(row) {
        namaEl.value = row.nmcust || '';
        kelasEl.value = row.kelas || '';
        nisEl.value = row.nocust || '';
        qEl.value = (row.nocust || '') + (row.nmcust ? ' · ' + row.nmcust : '');
        closeSuggest();
    }

    function render(items) {
        box.innerHTML = '';
        if (!items.length) {
            box.innerHTML = '<div class="suggest-empty">Siswi tidak ditemukan.</div>';
            box.classList.add('open');
            return;
        }
        items.forEach(function (row) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'suggest-item';
            btn.innerHTML =
                '<div class="suggest-name"></div><div class="suggest-meta"></div>';
            btn.querySelector('.suggest-name').textContent = row.nmcust || '-';
            btn.querySelector('.suggest-meta').textContent =
                'NIS ' + (row.nocust || '-') + ' · ' + (row.kelas || '-') + (row.sekolah ? ' · ' + row.sekolah : '');
            btn.addEventListener('click', function () { pick(row); });
            box.appendChild(btn);
        });
        box.classList.add('open');
    }

    function search(q) {
        if (q.length < 2) { closeSuggest(); return; }
        fetch(searchUrl + '?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) { return res.json(); }).then(function (json) {
            render(Array.isArray(json.data) ? json.data : []);
        }).catch(function () {
            box.innerHTML = '<div class="suggest-empty">Gagal mencari siswi.</div>';
            box.classList.add('open');
        });
    }

    if (qEl) {
        qEl.addEventListener('input', function () {
            var q = (qEl.value || '').trim();
            namaEl.value = '';
            kelasEl.value = '';
            nisEl.value = '';
            clearTimeout(timer);
            timer = setTimeout(function () { search(q); }, 280);
        });
        qEl.addEventListener('focus', function () {
            var q = (qEl.value || '').trim();
            if (q.length >= 2 && !nisEl.value) search(q);
        });
    }
    document.addEventListener('click', function (e) {
        if (box && !box.contains(e.target) && e.target !== qEl) closeSuggest();
    });

    document.getElementById('catatanForm').addEventListener('submit', function (e) {
        if (!(nisEl.value || '').trim()) {
            e.preventDefault();
            if (window.showToast) showToast('Pilih siswi dari hasil pencarian NIS / nama.', 'error');
            qEl.focus();
        }
    });
})();
</script>
@endsection
