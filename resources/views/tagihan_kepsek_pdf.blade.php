<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Tagihan Siswa</title>
    <style>
        @page { margin: 14mm 10mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111827; }
        .header { display: flex; align-items: center; margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; }
        .logo { width: 42px; height: 42px; margin-right: 10px; }
        .logo img { width: 100%; height: 100%; object-fit: contain; }
        .title-block h1 { margin: 0; font-size: 14px; }
        .title-block p { margin: 2px 0 0; font-size: 9px; color: #4b5563; }
        .meta { margin-bottom: 10px; font-size: 9px; }
        .meta span { display: inline-block; margin-right: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 5px; text-align: left; font-size: 9px; }
        th { background: #f3f4f6; font-weight: bold; }
        .text-right { text-align: right; }
        .badge-lunas { color: #166534; }
        .badge-belum { color: #991b1b; }
    </style>
</head>
<body>
@php
    $printAt = now('Asia/Jakarta')->format('d/m/Y H:i');
    $fmtRupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
@endphp
<div class="header">
    <div class="logo">
        <img src="{{ public_path('logo.png') }}" alt="Logo">
    </div>
    <div class="title-block">
        <h1>Data Tagihan Siswa</h1>
        <p>Monitoring Kepala Sekolah — dicetak {{ $printAt }}</p>
    </div>
</div>

<div class="meta">
    <span><strong>BTA:</strong> {{ $filters['bta'] ?? 'Semua' }}</span>
    <span><strong>Kelas:</strong> {{ $filters['kelas'] ?? 'Semua' }}</span>
    <span><strong>Status:</strong> {{ $filters['status'] ?? 'Semua' }}</span>
    <span><strong>Pencarian:</strong> {{ $filters['search'] ?? '-' }}</span>
</div>

<table>
    <thead>
        <tr>
            <th>No</th>
            <th>NIS</th>
            <th>Nama</th>
            <th>Kelas</th>
            <th>Kode Tagihan</th>
            <th>Nama Tagihan</th>
            <th>Urutan</th>
            <th class="text-right">Jumlah</th>
            <th>BTA</th>
            <th>Status</th>
            <th>Tgl Tagihan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $row)
            @php $paid = (int) ($row['status_bayar'] ?? 0) === 1; @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row['nis'] ?? '-' }}</td>
                <td>{{ $row['nama'] ?? '-' }}</td>
                <td>{{ $row['kelas'] ?? '-' }}</td>
                <td>{{ $row['kode_tagihan'] ?? '-' }}</td>
                <td>{{ $row['nama_tagihan'] ?? '-' }}</td>
                <td>{{ $row['furutan'] ?? '-' }}</td>
                <td class="text-right">{{ $fmtRupiah($row['jumlah'] ?? 0) }}</td>
                <td>{{ $row['tahun_akademik'] ?? '-' }}</td>
                <td class="{{ $paid ? 'badge-lunas' : 'badge-belum' }}">{{ $paid ? 'Lunas' : 'Belum Lunas' }}</td>
                <td>{{ $row['tanggal_tagihan'] ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="11" style="text-align:center;">Tidak ada data</td></tr>
        @endforelse
    </tbody>
    @if(!empty($totalJumlah))
    <tfoot>
        <tr>
            <td colspan="7" class="text-right" style="font-weight:bold;">Total Tagihan</td>
            <td class="text-right" style="font-weight:bold;">{{ $fmtRupiah($totalJumlah) }}</td>
            <td colspan="3"></td>
        </tr>
    </tfoot>
    @endif
</table>
</body>
</html>
