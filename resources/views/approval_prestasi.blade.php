@extends('layouts.approval')

@section('title', 'Approval Prestasi')
@section('heading', 'Approval Prestasi')

@section('content')
    @php
        $tabParams = array_filter([
            'q' => $q,
            'tanggal_dari' => $tanggalDari,
            'tanggal_sampai' => $tanggalSampai,
        ]);
        $sekolahOptions = [];
        $kategoriOptions = [];
        foreach ($items as $row) {
            $sch = trim((string) ($row['sekolah'] ?? ''));
            $kat = trim((string) ($row['kategori_nama'] ?? ''));
            if ($kat === '') {
                $jenis = trim((string) ($row['jenis_prestasi'] ?? ''));
                $kat = $jenis !== '' ? explode(' — ', $jenis)[0] : '';
            }
            if ($sch !== '') {
                $sekolahOptions[$sch] = $sch;
            }
            if ($kat !== '') {
                $kategoriOptions[$kat] = $kat;
            }
        }
        ksort($sekolahOptions);
        ksort($kategoriOptions);
    @endphp

    <div class="tabs">
        <a href="{{ route('approval.prestasi.index', array_merge($tabParams, ['status' => 'pending'])) }}" class="chip {{ $status === 'pending' ? 'active' : '' }}">Pending</a>
        <a href="{{ route('approval.prestasi.index', array_merge($tabParams, ['status' => 'approved'])) }}" class="chip {{ $status === 'approved' ? 'active' : '' }}">Disetujui</a>
        <a href="{{ route('approval.prestasi.index', array_merge($tabParams, ['status' => 'canceled'])) }}" class="chip {{ $status === 'canceled' ? 'active' : '' }}">Ditolak</a>
        <a href="{{ route('approval.prestasi.index', array_merge($tabParams, ['status' => 'all'])) }}" class="chip {{ $status === 'all' ? 'active' : '' }}">Semua</a>
    </div>

    <div class="panel table-card">
        <div class="filters">
            <div class="filters-grid">
                <div class="field">
                    <label for="filterQ">Siswa</label>
                    <input id="filterQ" type="text" value="{{ $q }}" placeholder="NIS atau nama">
                </div>
                @if(!empty($isSuperadmin))
                <div class="field">
                    <label for="filterSekolah">Sekolah</label>
                    <select id="filterSekolah">
                        <option value="">Semua sekolah</option>
                        @foreach($sekolahOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="field">
                    <label for="filterJenis">Kategori</label>
                    <select id="filterJenis">
                        <option value="">Semua kategori</option>
                        @foreach($kategoriOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="filterDari">Dari</label>
                    <input id="filterDari" type="date" value="{{ $tanggalDari }}">
                </div>
                <div class="field">
                    <label for="filterSampai">Sampai</label>
                    <input id="filterSampai" type="date" value="{{ $tanggalSampai }}">
                </div>
                <button type="button" class="btn btn-ghost" id="filterReset">Reset</button>
            </div>
        </div>
        <div class="table-toolbar">
            <span class="count-pill" id="resultCount">{{ count($items) }} data</span>
            <span class="count-pill" id="sortHint">Terbaru dulu</span>
        </div>
        <div class="table-wrap">
            @if(count($items) === 0)
                <div class="empty">
                    <div><i class="fas fa-inbox"></i></div>
                    Tidak ada data pada filter ini.
                </div>
            @else
                <table class="data" id="approvalTable">
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th>Sekolah</th>
                        <th>Prestasi</th>
                        <th>Poin</th>
                        <th>Periode</th>
                        <th class="sortable" id="sortCreated">Diunggah <i class="fas fa-sort"></i></th>
                        <th>Status</th>
                        <th>Bukti</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="approvalBody">
                    @foreach($items as $item)
                        @php
                            $rawStatus = strtolower(trim((string) ($item['isapproved'] ?? 'pending')));
                            $itemStatus = match ($rawStatus) {
                                '1', 'approve', 'approved' => 'approve',
                                'canceled', 'cancelled', 'tolak', 'rejected' => 'canceled',
                                default => 'pending',
                            };
                            $statusBadge = match ($itemStatus) {
                                'approve' => ['class' => 'approved', 'label' => 'Disetujui'],
                                'canceled' => ['class' => 'canceled', 'label' => 'Ditolak'],
                                default => ['class' => 'pending', 'label' => 'Pending'],
                            };
                            $sekolah = trim((string) ($item['sekolah'] ?? ''));
                            $created = (string) ($item['created_at'] ?? '');
                            $createdDate = substr($created, 0, 10);
                            $createdLabel = $created !== '' ? date('d/m/Y H:i', strtotime($created)) : '-';
                            $kategori = trim((string) ($item['kategori_nama'] ?? ''));
                            $tingkat = trim((string) ($item['tingkat_nama'] ?? ''));
                            $capaian = trim((string) ($item['poin_nama'] ?? ''));
                            if ($kategori === '') {
                                $parts = array_map('trim', explode(' — ', (string) ($item['jenis_prestasi'] ?? '')));
                                $kategori = $parts[0] ?? '';
                                $tingkat = $tingkat !== '' ? $tingkat : ($parts[1] ?? '');
                                $capaian = $capaian !== '' ? $capaian : ($parts[2] ?? '');
                            }
                            $semester = (string) ($item['semester'] ?? '');
                            $semesterLabel = $semester === '1' ? 'Ganjil' : ($semester === '2' ? 'Genap' : ($semester !== '' ? 'S'.$semester : '-'));
                            $bukti = trim((string) ($item['url'] ?? ''));
                            $isDrive = $bukti !== '' && (str_contains($bukti, 'drive.google.com') || str_contains($bukti, 'docs.google.com') || str_contains($bukti, 'drive.usercontent.google.com'));
                        @endphp
                        <tr
                            class="data-row"
                            data-nama="{{ mb_strtolower((string) ($item['nmcust'] ?? '')) }}"
                            data-nis="{{ mb_strtolower((string) ($item['nocust'] ?? '')) }}"
                            data-sekolah="{{ $sekolah }}"
                            data-jenis="{{ $kategori }}"
                            data-created="{{ $created }}"
                            data-date="{{ $createdDate }}"
                        >
                            <td>
                                @php
                                    $initials = strtoupper(mb_substr(trim((string) ($item['nmcust'] ?? 'S')), 0, 1));
                                @endphp
                                <div class="cell-user">
                                    <span class="avatar">{{ $initials }}</span>
                                    <div>
                                        <div class="cell-main">{{ $item['nmcust'] ?: '-' }}</div>
                                        <div class="cell-sub">{{ $item['nocust'] ?: '-' }} · {{ $item['kelas'] ?: '-' }}@if(!empty($item['nisn'])) · NISN {{ $item['nisn'] }}@endif</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $sekolah !== '' ? $sekolah : '-' }}</td>
                            <td>
                                <div class="cell-main">{{ $kategori !== '' ? $kategori : '-' }}</div>
                                <div class="cell-sub">{{ trim(implode(' · ', array_filter([$tingkat, $capaian]))) ?: '-' }}</div>
                                @if(!empty($item['penyelenggara']) || !empty($item['no_sertifikat']))
                                    <div class="cell-sub">{{ $item['penyelenggara'] ?: '-' }}@if(!empty($item['no_sertifikat'])) · {{ $item['no_sertifikat'] }}@endif</div>
                                @endif
                                @if(!empty($item['keterangan']))
                                    <div class="cell-sub">{{ $item['keterangan'] }}</div>
                                @endif
                            </td>
                            <td class="mono">{{ number_format((float) ($item['nilai_penghargaan'] ?? 0), 2, ',', '.') }}</td>
                            <td>
                                <div>{{ $item['bta'] ?: '-' }}</div>
                                <div class="cell-sub">{{ $semesterLabel }}</div>
                            </td>
                            <td class="mono">{{ $createdLabel }}</td>
                            <td>
                                <span class="badge {{ $statusBadge['class'] }}">{{ $statusBadge['label'] }}</span>
                                @if($itemStatus !== 'pending' && (trim((string) ($item['approvedby'] ?? '')) !== '' || trim((string) ($item['approveddate'] ?? '')) !== ''))
                                    <div class="cell-sub">{{ $item['approvedby'] ?: '-' }} · {{ $item['approveddate'] ?: '-' }}</div>
                                @endif
                                @if($itemStatus === 'canceled' && trim((string) ($item['catatan_admin'] ?? '')) !== '')
                                    <div class="cell-sub">Catatan: {{ $item['catatan_admin'] }}</div>
                                @endif
                            </td>
                            <td>
                                @if($bukti !== '')
                                    <a class="link" href="{{ $bukti }}" target="_blank" rel="noopener noreferrer">{{ $isDrive ? 'Drive' : 'Lihat' }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    @if($itemStatus !== 'approve')
                                    <form method="POST" action="{{ route('approval.prestasi.action') }}" class="js-confirm" data-title="Setujui prestasi?" data-text="Data ini akan disetujui.">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $item['id'] }}">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="status" value="{{ $status }}">
                                        <input type="hidden" name="q" value="{{ $q }}">
                                        <input type="hidden" name="tanggal_dari" value="{{ $tanggalDari }}">
                                        <input type="hidden" name="tanggal_sampai" value="{{ $tanggalSampai }}">
                                        <button type="submit" class="btn btn-ok btn-sm">Setujui</button>
                                    </form>
                                    @endif
                                    @if($itemStatus !== 'canceled')
                                    <form method="POST" action="{{ route('approval.prestasi.action') }}" class="js-confirm" data-title="Tolak prestasi?" data-text="Isi catatan penolakan. Catatan ini akan terlihat di data yang ditolak." data-note="required">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $item['id'] }}">
                                        <input type="hidden" name="action" value="tolak">
                                        <input type="hidden" name="status" value="{{ $status }}">
                                        <input type="hidden" name="q" value="{{ $q }}">
                                        <input type="hidden" name="tanggal_dari" value="{{ $tanggalDari }}">
                                        <input type="hidden" name="tanggal_sampai" value="{{ $tanggalSampai }}">
                                        <input type="hidden" name="catatan_admin" class="js-catatan-admin" value="">
                                        <button type="submit" class="btn btn-danger btn-sm">Tolak</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="empty" id="emptyFiltered" style="display:none;">Tidak ada data yang cocok dengan filter.</div>
        @endif
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    var body = document.getElementById('approvalBody');
    if (!body) return;
    var groupBySchool = @json(!empty($isSuperadmin));
    var collapsed = {};
    var sortDir = 'desc';
    var qEl = document.getElementById('filterQ');
    var schEl = document.getElementById('filterSekolah');
    var jenisEl = document.getElementById('filterJenis');
    var dariEl = document.getElementById('filterDari');
    var sampaiEl = document.getElementById('filterSampai');
    var countEl = document.getElementById('resultCount');
    var hintEl = document.getElementById('sortHint');
    var emptyEl = document.getElementById('emptyFiltered');
    var table = document.getElementById('approvalTable');

    function apply() {
        var q = (qEl && qEl.value || '').trim().toLowerCase();
        var school = schEl ? schEl.value : '';
        var jenis = jenisEl ? jenisEl.value : '';
        var dari = dariEl ? dariEl.value : '';
        var sampai = sampaiEl ? sampaiEl.value : '';
        var rows = Array.prototype.slice.call(body.querySelectorAll('tr.data-row'));
        body.querySelectorAll('tr.group-row').forEach(function (el) { el.remove(); });

        function byDate(a, b) {
            var av = a.getAttribute('data-created') || '';
            var bv = b.getAttribute('data-created') || '';
            if (av === bv) return 0;
            if (sortDir === 'asc') return av > bv ? 1 : -1;
            return av < bv ? 1 : -1;
        }

        var visible = [];
        rows.forEach(function (row) {
            var ok = true;
            if (q && row.getAttribute('data-nama').indexOf(q) === -1 && row.getAttribute('data-nis').indexOf(q) === -1) ok = false;
            if (ok && school && row.getAttribute('data-sekolah') !== school) ok = false;
            if (ok && jenis && row.getAttribute('data-jenis') !== jenis) ok = false;
            var d = row.getAttribute('data-date') || '';
            if (ok && dari && d < dari) ok = false;
            if (ok && sampai && d > sampai) ok = false;
            row.style.display = ok ? '' : 'none';
            if (ok) visible.push(row);
        });

        if (groupBySchool) {
            var map = {};
            var order = [];
            visible.forEach(function (row) {
                var key = row.getAttribute('data-sekolah') || 'Lainnya';
                if (!map[key]) {
                    map[key] = [];
                    order.push(key);
                }
                map[key].push(row);
            });
            order.sort(function (a, b) { return a.localeCompare(b, 'id'); });
            order.forEach(function (key) {
                map[key].sort(byDate);
                var tr = document.createElement('tr');
                tr.className = 'group-row';
                tr.setAttribute('data-group', key);
                var td = document.createElement('td');
                td.colSpan = 9;
                var open = !collapsed[key];
                td.innerHTML = '<i class="fas ' + (open ? 'fa-chevron-down' : 'fa-chevron-right') + '"></i> ' +
                    key + '<span class="group-count">' + map[key].length + '</span>';
                tr.appendChild(td);
                body.appendChild(tr);
                map[key].forEach(function (row) {
                    row.style.display = open ? '' : 'none';
                    body.appendChild(row);
                });
            });
        } else {
            visible.sort(byDate);
            visible.forEach(function (row) { body.appendChild(row); });
        }

        var shown = visible.filter(function (row) { return row.style.display !== 'none'; }).length;
        if (countEl) countEl.textContent = shown + ' data';
        if (hintEl) hintEl.textContent = sortDir === 'asc' ? 'Terlama dulu' : 'Terbaru dulu';
        if (emptyEl) emptyEl.style.display = visible.length === 0 ? 'block' : 'none';
        if (table) table.style.display = visible.length === 0 ? 'none' : '';
    }

    body.addEventListener('click', function (e) {
        var group = e.target.closest('tr.group-row');
        if (!group) return;
        var key = group.getAttribute('data-group');
        collapsed[key] = !collapsed[key];
        apply();
    });

    var sortBtn = document.getElementById('sortCreated');
    if (sortBtn) sortBtn.addEventListener('click', function () {
        sortDir = sortDir === 'desc' ? 'asc' : 'desc';
        apply();
    });

    [qEl, schEl, jenisEl, dariEl, sampaiEl].forEach(function (el) {
        if (!el) return;
        el.addEventListener('input', apply);
        el.addEventListener('change', apply);
    });

    var resetBtn = document.getElementById('filterReset');
    if (resetBtn) resetBtn.addEventListener('click', function () {
        if (qEl) qEl.value = '';
        if (schEl) schEl.value = '';
        if (jenisEl) jenisEl.value = '';
        if (dariEl) dariEl.value = '';
        if (sampaiEl) sampaiEl.value = '';
        sortDir = 'desc';
        collapsed = {};
        apply();
    });

    apply();
})();
</script>
@endsection
