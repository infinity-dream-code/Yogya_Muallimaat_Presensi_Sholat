@extends('layouts.approval')

@section('title', 'Catatan Kepribadian Siswi')
@section('heading', 'Catatan Kepribadian Siswi')

@section('content')
    <form method="GET" action="{{ route('catatan.kepribadian.index') }}" class="panel table-card">
        <div class="filters">
            <div class="filters-grid" style="grid-template-columns: 1.4fr .9fr .8fr auto;">
                <div class="field">
                    <label for="filterQ">Siswi</label>
                    <input id="filterQ" name="q" type="text" value="{{ $q }}" placeholder="NIS atau nama">
                </div>
                <div class="field">
                    <label for="filterBta">Tahun Ajaran</label>
                    <select id="filterBta" name="bta">
                        <option value="">Semua tahun</option>
                        @foreach($tahunAkademik as $tahun)
                            <option value="{{ $tahun }}" {{ $bta === $tahun ? 'selected' : '' }}>{{ $tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="filterSemester">Semester</label>
                    <select id="filterSemester" name="semester">
                        <option value="">Semua</option>
                        <option value="1" {{ $semester === '1' ? 'selected' : '' }}>Ganjil</option>
                        <option value="2" {{ $semester === '2' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-ghost">Terapkan</button>
            </div>
        </div>
        <div class="table-toolbar">
            <span class="count-pill">{{ count($items) }} catatan</span>
            <a href="{{ route('catatan.kepribadian.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah catatan</a>
        </div>
        <div class="table-wrap">
            @if(count($items) === 0)
                <div class="empty">
                    <div><i class="fas fa-inbox"></i></div>
                    Belum ada catatan pada filter ini.
                </div>
            @else
                <table class="data">
                    <thead>
                        <tr>
                            <th>Siswi</th>
                            <th>Kelas</th>
                            <th>Tahun / Semester</th>
                            <th>Jenis pelanggaran</th>
                            <th>Bentuk pembinaan</th>
                            <th>Skor</th>
                            <th style="width:150px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $item)
                        @php
                            $semesterLabel = ((string) ($item['semester'] ?? '') === '2') ? 'Genap' : 'Ganjil';
                            $initials = strtoupper(mb_substr(trim((string) ($item['nmcust'] ?? 'S')), 0, 1));
                        @endphp
                        <tr>
                            <td>
                                <div class="cell-user">
                                    <span class="avatar">{{ $initials }}</span>
                                    <div>
                                        <div class="cell-main">{{ $item['nmcust'] ?: '-' }}</div>
                                        <div class="cell-sub">NIS {{ $item['nocust'] ?: '-' }}@if(!empty($item['sekolah'])) · {{ $item['sekolah'] }}@endif</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $item['kelas'] ?: '-' }}</td>
                            <td>
                                <div>{{ $item['bta'] ?: '-' }}</div>
                                <div class="cell-sub">{{ $semesterLabel }}</div>
                            </td>
                            <td>{{ $item['jenis_pelanggaran'] ?: '-' }}</td>
                            <td>{{ $item['bentuk_pembinaan'] ?: '-' }}</td>
                            <td class="mono">{{ number_format((float) ($item['skor'] ?? 0), 2, ',', '.') }}</td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('catatan.kepribadian.edit', (int) $item['id']) }}" class="btn btn-ghost btn-sm">Ubah</a>
                                    <form method="POST" action="{{ route('catatan.kepribadian.delete') }}" class="js-confirm" data-title="Hapus catatan?" data-text="Catatan {{ $item['nmcust'] }} akan dihapus.">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $item['id'] }}">
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </form>
@endsection
