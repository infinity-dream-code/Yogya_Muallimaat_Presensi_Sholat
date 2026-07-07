<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Presensi</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-main: linear-gradient(135deg, #faf8fc 0%, #f3eff9 50%, #ebe5f5 100%);
            --card-bg: #ffffff;
            --card-shadow: 0 4px 20px rgba(124, 58, 237, 0.08);
            --card-radius: 16px;
            --accent: #6d28d9;
            --accent-light: #8b5cf6;
            --text: #1e1b2e;
            --text-muted: #64748b;
            --green: #059669;
            --green-bg: #d1fae5;
            --yellow: #b45309;
            --yellow-bg: #fef3c7;
            --red: #dc2626;
            --red-bg: #fee2e2;
            --gray: #64748b;
            --gray-bg: #f1f5f9;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: var(--bg-main);
            display: flex;
            justify-content: center;
        }
        .app {
            width: 100%;
            max-width: 600px;
            min-height: 100vh;
            color: var(--text);
            position: relative;
        }
        .drawer-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
            backdrop-filter: blur(2px);
        }
        .drawer {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100%;
            background: linear-gradient(180deg, #1e1b4b 0%, #0f0d1e 100%);
            color: #e2e8f0;
            transform: translateX(-100%);
            transition: transform 0.25s ease;
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
            z-index: 40;
            border-right: 1px solid rgba(255,255,255,0.08);
            box-shadow: 8px 0 32px rgba(0,0,0,0.2);
        }
        .drawer.open { transform: translateX(0); }
        .drawer-backdrop.open { opacity: 1; pointer-events: auto; }
        .drawer-header { display: flex; align-items: center; margin-bottom: 20px; }
        .drawer-logo { width: 48px; height: 48px; border-radius: 12px; overflow: hidden; margin-right: 12px; flex-shrink: 0; }
        .drawer-logo img { width: 100%; height: 100%; object-fit: contain; }
        .drawer-user-name { font-size: 0.95rem; font-weight: 600; color: #f8fafc; }
        .drawer-user-role { font-size: 0.78rem; color: #94a3b8; }
        .drawer-divider { height: 1px; background: rgba(255,255,255,0.1); margin: 16px 0; }
        .drawer-menu { list-style: none; padding: 0; margin: 0; flex: 1; }
        .drawer-menu-label { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.12em; color: #64748b; margin-bottom: 10px; padding-left: 14px; font-weight: 600; }
        .drawer-item { margin-bottom: 4px; }
        .drawer-link {
            display: flex; align-items: center;
            padding: 12px 14px; border-radius: 12px;
            color: #cbd5e1; text-decoration: none;
            font-size: 0.9rem; font-weight: 500; transition: all 0.2s ease;
        }
        .drawer-link span.icon { width: 24px; display: inline-flex; justify-content: center; align-items: center; margin-right: 12px; color: #94a3b8; }
        .drawer-link:hover { background: rgba(255,255,255,0.08); color: #f8fafc; }
        .drawer-link.active { background: linear-gradient(135deg, rgba(124,58,237,0.3) 0%, rgba(109,40,217,0.2) 100%); color: #e9d5ff; }
        .drawer-link.active span.icon { color: #c4b5fd; }
        .drawer-footer { font-size: 0.75rem; color: #64748b; margin-top: auto; padding-top: 16px; }

        .header {
            display: flex;
            align-items: center;
            padding: 20px 20px 16px;
        }
        .burger {
            width: 36px;
            height: 36px;
            margin-right: 12px;
            border: none;
            border-radius: 10px;
            background: rgba(109, 40, 217, 0.08);
            padding: 0;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .burger span {
            display: block;
            width: 18px;
            height: 2.5px;
            border-radius: 2px;
            background: var(--accent);
        }
        .title {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.02em;
        }

        .content {
            padding: 0 20px 32px;
        }
        .filter-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            padding: 18px 20px;
            margin-bottom: 24px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }
        .filter-left {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .date-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
        }
        .date-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text);
        }
        .btn-date {
            border: none;
            border-radius: 12px;
            padding: 12px 18px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 14px rgba(109, 40, 217, 0.35);
        }
        .btn-date:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(109, 40, 217, 0.4); }
        .btn-date:active { transform: translateY(0); }
        .btn-date i { font-size: 1rem; opacity: 0.9; }

        .section-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            margin-bottom: 12px;
        }
        .list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            padding: 20px 20px 18px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(109, 40, 217, 0.06);
        }
        .card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f1f5f9;
        }
        .card-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1.35;
        }
        .card-sub {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .chip-date {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 10px;
            background: var(--gray-bg);
            color: var(--text-muted);
            white-space: nowrap;
        }
        .sholat-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .sholat-item {
            border-radius: 12px;
            padding: 10px 12px;
        }
        .sholat-label {
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .sholat-sub {
            font-size: 0.72rem;
            line-height: 1.4;
            opacity: 0.95;
        }
        .sholat-sholat {
            background: #86efac;
            color: #065f46;
        }
        .sholat-izin {
            background: #fef08a;
            color: #854d0e;
        }
        .sholat-alpa {
            background: #fca5a5;
            color: #991b1b;
        }
        .sholat-haid {
            background: #fbcfe8;
            color: #9d174d;
        }
        .sholat-sakit {
            background: #bae6fd;
            color: #0369a1;
        }
        .sholat-belum {
            background: #e5e7eb;
            color: var(--gray);
        }
        .empty {
            text-align: center;
            padding: 48px 24px;
            font-size: 0.95rem;
            color: var(--text-muted);
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
        }
        .empty i { font-size: 2.5rem; opacity: 0.4; margin-bottom: 12px; display: block; }
        .error {
            margin-bottom: 20px;
            padding: 14px 16px;
            font-size: 0.9rem;
            border-radius: 12px;
            background: var(--red-bg);
            color: var(--red);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        @media (min-width: 960px) {
            body { justify-content: flex-start; }
            .app { max-width: 1024px; margin-left: 280px; }
            .content { max-width: 560px; margin: 48px auto 24px; margin-left: max(0, calc(50vw - 560px)); padding: 0 24px 48px; }
            .drawer { position: fixed; transform: translateX(0); }
            .drawer-backdrop { display: none; }
            .burger { display: none; }
        }
    </style>
</head>
<body>
<div class="app">
    <div class="header">
        <button class="burger" id="drawerToggle" type="button" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
        <div class="title">Log Presensi</div>
    </div>

    <div class="content">
        @if($error)
            <div class="error"><i class="fas fa-circle-exclamation"></i>{{ $error }}</div>
        @endif

        <form method="GET" action="{{ route('presensi.log-presensi') }}">
            <div class="filter-card">
                <div class="filter-left">
                    <span class="date-label">Tanggal</span>
                    <span class="date-value">{{ $tanggal === $today ? 'Hari ini' : $tanggal }}</span>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:flex-end;">
                    <input type="date" name="tanggal" value="{{ $tanggal }}" max="{{ $today }}" style="display:none;" id="tanggalPicker">
                    <button type="button" class="btn-date" onclick="document.getElementById('tanggalPicker').showPicker && document.getElementById('tanggalPicker').showPicker();">
                        <i class="fas fa-calendar-days"></i>
                        Pilih tanggal
                    </button>
                    <a href="{{ route('presensi.log-presensi.export-excel', ['tanggal' => $tanggal]) }}" class="btn-date" style="background:#0f766e;">
                        <i class="fas fa-file-excel"></i>
                        Excel
                    </a>
                    <a href="{{ route('presensi.log-presensi.export-pdf', ['tanggal' => $tanggal]) }}" class="btn-date" style="background:#1f2937;">
                        <i class="fas fa-file-pdf"></i>
                        PDF
                    </a>
                </div>
            </div>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var picker = document.getElementById('tanggalPicker');
                if (picker) {
                    picker.addEventListener('change', function () {
                        this.form.submit();
                    });
                }
            });
        </script>

        <div class="search-wrap" style="margin-bottom:16px;position:relative;">
            <i class="fas fa-search" style="position:absolute;left:16px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:1rem;"></i>
            <input type="text" class="search-input" id="searchInput" placeholder="Cari nama atau NIS" autocomplete="off" style="width:100%;padding:14px 18px 14px 44px;border:1px solid #e2e8f0;border-radius:12px;font-size:0.95rem;font-family:inherit;background:var(--card-bg);box-shadow:var(--card-shadow);">
        </div>

        <div class="section-title">Siswa</div>

        <div class="list">
            @forelse($entries as $entry)
                @php
                    $title = $entry['NamaCust'] ?? $entry['NAMA'] ?? $entry['NAMASISWA'] ?? 'Siswa';
                    $unitVal = $entry['UNIT'] ?? $entry['Unit'] ?? null;
                    $subtitle = $unitVal ? 'Unit: ' . $unitVal : null;
                    $tanggalItem = $entry['TRXDATE'] ?? $entry['TANGGAL'] ?? $entry['DATE'] ?? $tanggal;
                    $nisVal = $entry['NIS'] ?? $entry['NOKARTU'] ?? '';
                    $searchText = strtolower($title . ' ' . $nisVal);
                @endphp
                <div class="card card-student" data-search="{{ $searchText }}">
                    <div class="card-header">
                        <div>
                            <div class="card-title">{{ $title }}</div>
                            @if($subtitle)
                                <div class="card-sub">{{ $subtitle }}</div>
                            @endif
                        </div>
                        <div class="chip-date">{{ $tanggalItem }}</div>
                    </div>
                    @php
                        $sholatNames = ['', 'Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'];
                        $sholatRows = [];
                        for ($i = 1; $i <= 5; $i++) {
                            $jadwalKey = 'JADWAL_' . $i;
                            $jamKey    = 'JAM_' . $i;
                            $userKey   = 'USER_' . $i;
                            $statusVal = $entry[$jadwalKey] ?? null;
                            $jamVal    = $entry[$jamKey] ?? null;
                            $userVal   = $entry[$userKey] ?? null;
                            if ($statusVal !== null) {
                                $sholatRows[] = [
                                    'index'  => $i,
                                    'name'   => $sholatNames[$i],
                                    'status' => $statusVal,
                                    'jam'    => $jamVal,
                                    'user'   => $userVal,
                                ];
                            }
                        }
                    @endphp
                    @if(count($sholatRows))
                        <div class="sholat-list">
                            @foreach($sholatRows as $row)
                                @php
                                    $s = strtoupper((string) $row['status']);
                                    if ($s === 'SHOLAT') {
                                        $cls = 'sholat-item sholat-sholat';
                                        $statusLabel = 'Sholat';
                                    } elseif ($s === 'HAID') {
                                        $cls = 'sholat-item sholat-haid';
                                        $statusLabel = 'Haid';
                                    } elseif ($s === 'IZIN' || $s === 'TIDAK HADIR') {
                                        $cls = 'sholat-item sholat-izin';
                                        $statusLabel = 'Izin';
                                    } elseif ($s === 'ALPA') {
                                        $cls = 'sholat-item sholat-alpa';
                                        $statusLabel = 'Alpa';
                                    } elseif ($s === 'SAKIT') {
                                        $cls = 'sholat-item sholat-sakit';
                                        $statusLabel = 'Sakit';
                                    } elseif ($s === 'BELUM PRESENSI') {
                                        $cls = 'sholat-item sholat-belum';
                                        $statusLabel = 'Belum';
                                    } else {
                                        $cls = 'sholat-item sholat-sholat';
                                        $statusLabel = $row['status'];
                                    }
                                @endphp
                                <div class="{{ $cls }}">
                                    <div class="sholat-label">{{ $row['name'] }}</div>
                                    <div class="sholat-sub">{{ $statusLabel }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="empty"><i class="fas fa-clipboard-list"></i>Tidak ada data log presensi untuk tanggal ini.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="drawer-backdrop" id="drawerBackdrop"></div>
<aside class="drawer" id="drawer">
    <div class="drawer-header">
        <div class="drawer-logo">
            <img src="{{ asset('logo.png') }}" alt="Logo">
        </div>
        <div>
            <div class="drawer-user-name">{{ session('user.username', '-') }}</div>
            <div class="drawer-user-role">User</div>
        </div>
    </div>

    <div class="drawer-divider"></div>

    <div class="drawer-menu-label">Presensi Sholat</div>
    <ul class="drawer-menu">
        <li class="drawer-item">
            <a href="{{ route('presensi-sholat.qr') }}" class="drawer-link">
                <span class="icon"><i class="fas fa-qrcode"></i></span>
                <span>Presensi Sholat</span>
            </a>
        </li>
        <li class="drawer-item">
            <a href="{{ route('presensi-haid.qr') }}" class="drawer-link">
                <span class="icon"><i class="fas fa-qrcode"></i></span>
                <span>Presensi Haid</span>
            </a>
        </li>
        <li class="drawer-item">
            <a href="{{ route('presensi.log-marifah') }}" class="drawer-link">
                <span class="icon"><i class="fas fa-rectangle-list"></i></span>
                <span>Log Marifah</span>
            </a>
        </li>
        <li class="drawer-item">
            <a href="{{ route('presensi.log-presensi') }}" class="drawer-link active">
                <span class="icon"><i class="fas fa-square-check"></i></span>
                <span>Log Presensi</span>
            </a>
        </li>
        <li class="drawer-item">
            <a href="{{ route('presensi.kelola') }}" class="drawer-link">
                <span class="icon"><i class="fas fa-arrows-rotate"></i></span>
                <span>Kelola Presensi</span>
            </a>
        </li>
        <li class="drawer-item">
            <a href="{{ route('presensi.rekap-sholat') }}" class="drawer-link">
                <span class="icon"><i class="fas fa-chart-simple"></i></span>
                <span>Rekap Sholat</span>
            </a>
        </li>
        <li class="drawer-item">
            <a href="{{ route('presensi.account.ganti-password') }}" class="drawer-link">
                <span class="icon"><i class="fas fa-gear"></i></span>
                <span>Account Controls</span>
            </a>
        </li>
        <li class="drawer-item">
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="drawer-link" style="width: 100%; text-align: left; border: none; background: transparent; color: inherit; cursor: pointer; font-size: inherit; font-family: inherit; padding: 12px 14px;">
                    <span class="icon"><i class="fas fa-right-from-bracket"></i></span>
                    <span>Log Out</span>
                </button>
            </form>
        </li>
    </ul>

    <div class="drawer-footer">
        App Ver : 1.2.1
    </div>
</aside>

<script>
    const toggleBtn = document.getElementById('drawerToggle');
    const backdrop = document.getElementById('drawerBackdrop');
    const drawer = document.getElementById('drawer');

    function closeDrawer() {
        drawer.classList.remove('open');
        backdrop.classList.remove('open');
    }

    toggleBtn.addEventListener('click', function () {
        const isOpen = drawer.classList.contains('open');
        if (isOpen) {
            closeDrawer();
        } else {
            drawer.classList.add('open');
            backdrop.classList.add('open');
        }
    });

    backdrop.addEventListener('click', closeDrawer);

    var searchInput = document.getElementById('searchInput');
    var searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function () {
                var q = searchInput.value.trim().toLowerCase();
                var cards = document.querySelectorAll('.card-student');
                for (var i = 0; i < cards.length; i++) {
                    var card = cards[i];
                    card.style.display = (q === '' || (card.dataset.search || '').indexOf(q) >= 0) ? '' : 'none';
                }
            }, 120);
        });
    }
</script>
</body>
</html>
