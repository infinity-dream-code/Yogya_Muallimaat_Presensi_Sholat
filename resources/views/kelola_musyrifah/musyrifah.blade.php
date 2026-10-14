@extends('kelola_musyrifah.layout')

@section('title', 'Data Musyrifah')

@section('content')
    <div class="card">
        <div class="toolbar">
            <input type="text" class="input" id="searchInput" placeholder="Cari username atau nama" autocomplete="off">
            <span class="chip">{{ $entries->count() }} user</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Nama</th>
                    <th>Role</th>
                    <th>Jumlah Siswa</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($entries as $row)
                    <tr class="row-search" data-search="{{ strtolower($row->username . ' ' . $row->nama) }}">
                        <td>{{ $row->idincrement }}</td>
                        <td>{{ $row->username }}</td>
                        <td>{{ $row->nama }}</td>
                        <td><span class="chip">{{ $row->role }}</span></td>
                        <td>{{ $row->jumlah_siswa }}</td>
                        <td>
                            @if($row->jumlah_siswa > 0)
                                <a href="{{ route('kelola-musyrifah.siswa', ['musyrifah' => $row->username]) }}" class="btn btn-light btn-sm">
                                    <i class="fas fa-users"></i> Lihat siswa
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted" style="text-align:center;">Belum ada data musyrifah.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var input = document.getElementById('searchInput');
        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase();
            document.querySelectorAll('.row-search').forEach(function (row) {
                row.style.display = (q === '' || (row.dataset.search || '').indexOf(q) >= 0) ? '' : 'none';
            });
        });
    })();
</script>
@endpush
