<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Log Presensi - {{ $tanggal }}</title>
    <style>
        @page { margin: 18mm 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #111827;
        }
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
        }
        .logo {
            width: 45px;
            height: 45px;
            margin-right: 10px;
        }
        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .title-block h1 {
            margin: 0;
            font-size: 15px;
        }
        .title-block p {
            margin: 2px 0 0;
            font-size: 10px;
            color: #4b5563;
        }
        .meta {
            margin-bottom: 8px;
            font-size: 10px;
        }
        .meta span {
            display: inline-block;
            margin-right: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 3px 4px;
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
        .st-sholat { background-color: #86efac; }
        .st-izin  { background-color: #fef08a; }
        .st-alpa  { background-color: #fca5a5; }
        .st-haid  { background-color: #fbcfe8; }
        .st-sakit { background-color: #bae6fd; }
        .st-belum { background-color: #e5e7eb; }
    </style>
</head>
<body>
@php
    $printAt = now('Asia/Jakarta')->format('d/m/Y H:i');
@endphp
<div class="header">
    <div class="logo">
        <img src="{{ public_path('logo.png') }}" alt="Logo">
    </div>
    <div class="title-block">
        <h1>Log Presensi Siswa</h1>
        <p>Tanggal: {{ $tanggal }}</p>
    </div>
    <div style="margin-left:auto;font-size:9px;color:#6b7280;">
        Dicetak: {{ $printAt }}
    </div>
</div>

<table>
    <thead>
    <tr>
        <th>No</th>
        <th class="text-left">Nama</th>
        <th>NIS</th>
        <th>Unit</th>
        <th>Subuh</th>
        <th>Dzuhur</th>
        <th>Ashar</th>
        <th>Maghrib</th>
        <th>Isya</th>
    </tr>
    </thead>
    <tbody>
    @foreach($entries as $idx => $entry)
        @php
            $title   = $entry['NamaCust'] ?? $entry['NAMA'] ?? $entry['NAMASISWA'] ?? 'Siswa';
            $unitVal = $entry['UNIT'] ?? $entry['Unit'] ?? '';
            $nisVal  = $entry['NOCUST'] ?? $entry['nocust'] ?? $entry['NIS'] ?? $entry['NOKARTU'] ?? $entry['nis'] ?? '';
            $cells   = [];
            $classes = [];
            for ($i = 1; $i <= 5; $i++) {
                $status = $entry['JADWAL_'.$i] ?? null;
                if ($status === null || $status === '') {
                    $cells[$i] = '';
                    $classes[$i] = '';
                } else {
                    $s = strtoupper((string) $status);
                    if (in_array($s, ['SHOLAT'])) {
                        $cells[$i] = 'Sholat';
                        $classes[$i] = 'st-sholat';
                    } elseif ($s === 'IZIN' || $s === 'TIDAK HADIR') {
                        $cells[$i] = 'Izin';
                        $classes[$i] = 'st-izin';
                    } elseif ($s === 'ALPA') {
                        $cells[$i] = 'Alpa';
                        $classes[$i] = 'st-alpa';
                    } elseif ($s === 'HAID') {
                        $cells[$i] = 'Haid';
                        $classes[$i] = 'st-haid';
                    } elseif ($s === 'SAKIT') {
                        $cells[$i] = 'Sakit';
                        $classes[$i] = 'st-sakit';
                    } elseif ($s === 'BELUM PRESENSI') {
                        $cells[$i] = 'Belum';
                        $classes[$i] = 'st-belum';
                    } else {
                        $cells[$i] = $status;
                        $classes[$i] = 'st-belum';
                    }
                }
            }
        @endphp
        <tr>
            <td>{{ $idx + 1 }}</td>
            <td class="text-left">{{ $title }}</td>
            <td class="nowrap">{{ $nisVal }}</td>
            <td>{{ $unitVal }}</td>
            <td class="{{ $classes[1] }}">{{ $cells[1] }}</td>
            <td class="{{ $classes[2] }}">{{ $cells[2] }}</td>
            <td class="{{ $classes[3] }}">{{ $cells[3] }}</td>
            <td class="{{ $classes[4] }}">{{ $cells[4] }}</td>
            <td class="{{ $classes[5] }}">{{ $cells[5] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>

