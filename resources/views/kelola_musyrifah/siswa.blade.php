@extends('kelola_musyrifah.layout')

@section('title', 'Data Siswa')

@push('styles')
<style>
    .modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); display: none; align-items: center; justify-content: center; z-index: 50; padding: 16px; }
    .modal-backdrop.open { display: flex; }
    .modal { background: #fff; border-radius: 18px; padding: 20px; width: 100%; max-width: 420px; box-shadow: 0 24px 48px rgba(0,0,0,0.2); }
    .modal h3 { margin: 0 0 4px; font-size: 1.05rem; }
    .modal .ss-wrap { margin: 14px 0 18px; }
    .modal-actions { display: flex; justify-content: flex-end; gap: 8px; }
</style>
@endpush

@section('content')
    @php
        $namaByUsername = $musyrifahList->mapWithKeys(fn ($m) => [strtolower(trim($m->username)) => $m->nama]);
    @endphp
    <div class="card">
        <form method="GET" action="{{ route('kelola-musyrifah.siswa') }}" class="toolbar">
            <input type="text" name="search" class="input" value="{{ $search }}" placeholder="Cari nama atau NIS">
            <select name="unit" class="select">
                <option value="">Semua unit</option>
                @foreach($units as $unit)
                    <option value="{{ $unit }}" @selected($unitFilter === $unit)>{{ $unit }}</option>
                @endforeach
            </select>
            <select name="musyrifah" class="select js-search-select" data-placeholder="Cari musyrifah…">
                <option value="">Semua musyrifah</option>
                <option value="__none__" @selected($musyrifahFilter === '__none__')>Belum ada musyrifah</option>
                @foreach($musyrifahList as $m)
                    <option value="{{ $m->username }}" @selected(strtolower($musyrifahFilter) === strtolower($m->username))>{{ $m->nama }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-magnifying-glass"></i> Cari</button>
            @if($search !== '' || $unitFilter !== '' || $musyrifahFilter !== '')
                <a href="{{ route('kelola-musyrifah.siswa') }}" class="btn btn-light">Reset</a>
            @endif
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Unit</th>
                    <th>Kelas</th>
                    <th>Kelompok</th>
                    <th>Musyrifah</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($entries as $row)
                    @php
                        $musrifahNama = $row->musrifah ? ($namaByUsername[strtolower($row->musrifah)] ?? $row->musrifah) : null;
                    @endphp
                    <tr>
                        <td>{{ $row->nocust }}</td>
                        <td>{{ $row->nama }}</td>
                        <td>{{ $row->unit ?: '-' }}</td>
                        <td>{{ $row->kelas ?: '-' }}</td>
                        <td>{{ $row->kelompok ?: '-' }}</td>
                        <td>
                            @if($musrifahNama)
                                <span class="chip">{{ $musrifahNama }}</span>
                            @else
                                <span class="chip chip-empty">Belum ada</span>
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-light btn-sm btn-edit"
                                    data-custid="{{ $row->custid }}"
                                    data-nama="{{ $row->nama }}"
                                    data-nis="{{ $row->nocust }}"
                                    data-musrifah="{{ $row->musrifah }}">
                                <i class="fas fa-pen"></i> Edit
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted" style="text-align:center;">Tidak ada siswa yang cocok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <span class="muted">
                Menampilkan {{ $entries->firstItem() ?? 0 }}–{{ $entries->lastItem() ?? 0 }} dari {{ $entries->total() }} siswa
            </span>
            <div style="display:flex; gap:6px;">
                @if($entries->onFirstPage())
                    <span class="btn btn-light btn-sm" style="opacity:0.5;"><i class="fas fa-chevron-left"></i> Sebelumnya</span>
                @else
                    <a href="{{ $entries->previousPageUrl() }}" class="btn btn-light btn-sm"><i class="fas fa-chevron-left"></i> Sebelumnya</a>
                @endif
                <span class="chip" style="align-self:center;">{{ $entries->currentPage() }} / {{ $entries->lastPage() }}</span>
                @if($entries->hasMorePages())
                    <a href="{{ $entries->nextPageUrl() }}" class="btn btn-light btn-sm">Berikutnya <i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="btn btn-light btn-sm" style="opacity:0.5;">Berikutnya <i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        </div>
    </div>

    <div class="modal-backdrop" id="editModal">
        <form method="POST" action="{{ route('kelola-musyrifah.siswa.update') }}" class="modal">
            @csrf
            <input type="hidden" name="custid" id="editCustid">
            <h3 id="editNama">-</h3>
            <div class="muted" id="editNis">-</div>
            <select name="musrifah" id="editMusrifah" class="select js-search-select" data-placeholder="Ketik nama musyrifah…">
                <option value="">— Tanpa musyrifah —</option>
                @foreach($musyrifahList as $m)
                    <option value="{{ $m->username }}">{{ $m->nama }} ({{ $m->username }})</option>
                @endforeach
            </select>
            <div class="modal-actions">
                <button type="button" class="btn btn-light" id="editCancel">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Simpan</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var modal = document.getElementById('editModal');
        var select = document.getElementById('editMusrifah');

        document.querySelectorAll('.btn-edit').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('editCustid').value = btn.dataset.custid;
                document.getElementById('editNama').textContent = btn.dataset.nama;
                document.getElementById('editNis').textContent = 'NIS: ' + btn.dataset.nis;
                var current = (btn.dataset.musrifah || '').toLowerCase();
                select.value = '';
                for (var i = 0; i < select.options.length; i++) {
                    if (select.options[i].value.toLowerCase() === current) {
                        select.selectedIndex = i;
                        break;
                    }
                }
                if (select.ssSync) select.ssSync();
                modal.classList.add('open');
            });
        });

        function closeModal() { modal.classList.remove('open'); }
        document.getElementById('editCancel').addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    })();
</script>
@endpush
