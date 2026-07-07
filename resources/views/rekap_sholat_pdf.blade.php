<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Sholat - {{ $bulan }}</title>
    <style>
        @page { margin: 20mm 15mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #111827;
        }
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
        }
        .logo {
            width: 55px;
            height: 55px;
            margin-right: 12px;
        }
        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .title-block {
            flex: 1;
        }
        .title-block h1 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
        }
        .title-block p {
            margin: 2px 0 0;
            font-size: 11px;
            color: #4b5563;
        }
        .meta {
            margin-bottom: 10px;
            font-size: 10px;
        }
        .meta span {
            display: inline-block;
            margin-right: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 4px 5px;
            text-align: center;
        }
        th {
            background: #f3f4f6;
            font-size: 10px;
        }
        td {
            font-size: 10px;
        }
        .text-left { text-align: left; }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body>
@php
    $bulanText = $bulan;
    if (strpos($bulan, '-') !== false) {
        [$y, $m] = explode('-', $bulan);
        $bulanText = $m . '/' . $y;
    }
    $sholatNames = ['Subuh' => 'SUBUH', 'Dhuhur' => 'DHUHUR', 'Ashar' => 'ASHAR', 'Maghrib' => 'MAGHRIB', 'Isya' => 'ISYA'];
    $badgeKeys = [
        'SHOLAT' => 'Sholat',
        'SAKIT' => 'Sakit',
        'IZIN' => 'Izin',
        'ALPA' => 'Alpa',
        'HAID' => 'Haid',
        'BELUM_PRESENSI' => 'Belum',
    ];
@endphp

<div class="header">
    <div class="logo">
        <img src="{{ public_path('logo.png') }}" alt="Logo">
    </div>
    <div class="title-block">
        <h1>Rekap Sholat Siswa</h1>
        <p>Bulan: {{ $bulanText }}</p>
    </div>
</div>

<div class="meta">
    <span>Tanggal cetak: {{ now()->format('d/m/Y H:i') }}</span>
</div>

<table>
    <thead>
    <tr>
        <th rowspan="2">No</th>
        <th rowspan="2" class="text-left">Nama</th>
        <th rowspan="2">NIS</th>
        <th rowspan="2">Unit</th>
        <th rowspan="2">Total<br>Hari</th>
        <th colspan="6">Total Bulanan</th>
        @foreach($sholatNames as $label => $prefix)
            <th colspan="6">{{ $label }}</th>
        @endforeach
    </tr>
    <tr>
        @foreach($badgeKeys as $short)
            <th>{{ $short }}</th>
        @endforeach
        @foreach($sholatNames as $label => $prefix)
            @foreach($badgeKeys as $short)
                <th>{{ $short }}</th>
            @endforeach
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($entries as $idx => $entry)
        @php
            $title = $entry['NamaCust'] ?? $entry['NAMA'] ?? $entry['NAMASISWA'] ?? $entry['Nama'] ?? '-';
            $unitVal = $entry['UNIT'] ?? $entry['Unit'] ?? $entry['unit'] ?? '';
            $nisVal = $entry['NOCUST'] ?? $entry['nocust'] ?? $entry['NIS'] ?? $entry['NOKARTU'] ?? $entry['nis'] ?? '';
            $totalHari = $entry['TOTAL_HARI'] ?? $entry['TOT_HARI'] ?? $entry['TOTALHARI'] ?? '';
            $totalHari = $totalHari !== null && $totalHari !== '' ? $totalHari : 0;
        @endphp
        <tr>
            <td>{{ $idx + 1 }}</td>
            <td class="text-left">{{ $title }}</td>
            <td class="nowrap">{{ $nisVal }}</td>
            <td>{{ $unitVal }}</td>
            <td>{{ $totalHari }}</td>
            @foreach($badgeKeys as $key => $labelShort)
                @php
                    $val = $entry[$key] ?? $entry[str_replace('_', ' ', $key)] ?? 0;
                    $val = $val !== null && $val !== '' ? $val : 0;
                @endphp
                <td>{{ $val }}</td>
            @endforeach
            @foreach($sholatNames as $label => $prefix)
                @foreach($badgeKeys as $key => $labelShort)
                    @php
                        $k1 = $prefix . '_' . $key;
                        $k2 = $prefix . $key;
                        $num = array_search($prefix, array_values($sholatNames), true);
                        $k3 = $num !== false ? 'SHOLAT_' . ($num + 1) . '_' . $key : null;
                        $val = $entry[$k1] ?? $entry[$k2] ?? ($k3 ? ($entry[$k3] ?? 0) : 0);
                        $val = $val !== null && $val !== '' ? $val : 0;
                    @endphp
                    <td>{{ $val }}</td>
                @endforeach
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>

