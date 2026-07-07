<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Marifah</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', system-ui, sans-serif;
            background: #fff7ff;
            display: flex;
            justify-content: center;
        }
        .app {
            width: 100%;
            max-width: 520px;
            min-height: 100vh;
            background: #fff7ff;
            color: #2b2340;
            position: relative;
        }
        .drawer-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }
        .drawer {
            position: fixed;
            top: 0;
            left: 0;
            width: 272px;
            height: 100%;
            background: linear-gradient(180deg, #0f172a 0%, #020617 100%);
            color: #f9fafb;
            transform: translateX(-100%);
            transition: transform 0.2s ease;
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
            z-index: 40;
            border-right: 1px solid rgba(255,255,255,0.06);
            box-shadow: 4px 0 24px rgba(0,0,0,0.2);
        }
        .drawer.open { transform: translateX(0); }
        .drawer-backdrop.open { opacity: 1; pointer-events: auto; }
        .drawer-header { display: flex; align-items: center; margin-bottom: 20px; }
        .drawer-logo { width: 50px; height: 50px; border-radius: 999px; overflow: hidden; margin-right: 10px; }
        .drawer-logo img { width: 100%; height: 100%; object-fit: contain; }
        .drawer-user-name { font-size: 0.95rem; font-weight: 600; }
        .drawer-user-role { font-size: 0.78rem; opacity: 0.75; }
        .drawer-divider { height: 1px; background: #4b5563; margin: 16px 0; }
        .drawer-menu { list-style: none; padding: 0; margin: 0; flex: 1; }
        .drawer-menu-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; color: #94a3b8; margin-bottom: 10px; padding-left: 14px; }
        .drawer-item { margin-bottom: 12px; }
        .drawer-link {
            display: flex; align-items: center;
            padding: 12px 14px; border-radius: 999px;
            color: inherit; text-decoration: none;
            font-size: 0.9rem; transition: background 0.15s ease;
        }
        .drawer-link span.icon { width: 26px; display: inline-flex; justify-content: center; align-items: center; margin-right: 12px; }
        .drawer-link span.icon i { font-size: 1.05rem; color: #e2e8f0 !important; }
        .drawer-link:hover { background: rgba(148,163,184,0.18); }
        .drawer-link.active { background: rgba(148,163,184,0.15); }
        .drawer-footer { font-size: 0.78rem; opacity: 0.7; margin-top: 12px; }

        .header {
            display: flex;
            align-items: center;
            padding: 16px 16px 10px;
        }
        .burger {
            width: 28px;
            margin-right: 12px;
            border: none;
            background: transparent;
            padding: 0;
            cursor: pointer;
        }
        .burger span {
            display: block;
            height: 3px;
            border-radius: 999px;
            background: #7c3aed;
            margin-bottom: 5px;
        }
        .title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #a855f7;
        }
        .content {
            padding: 32px 16px 24px;
        }
        .filter-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }
        .date-label {
            font-size: 0.85rem;
            color: #6b7280;
        }
        .date-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1f2933;
        }
        .btn-date {
            border: none;
            border-radius: 999px;
            padding: 10px 14px;
            background: #f5f3ff;
            color: #7c3aed;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-date i { font-size: 0.95rem; }
        .section-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #6b21a8;
            margin-bottom: 8px;
        }
        .list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .card {
            background: #ffffff;
            border-radius: 18px;
            padding: 14px 14px 12px;
            box-shadow: 0 16px 32px rgba(109, 40, 217, 0.15);
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .card-title {
            font-size: 0.96rem;
            font-weight: 600;
        }
        .card-sub {
            font-size: 0.8rem;
            color: #9ca3af;
        }
        .chip-date {
            font-size: 0.78rem;
            padding: 4px 8px;
            border-radius: 999px;
            background: #f5f3ff;
            color: #6b21a8;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .sholat-list {
            margin-top: 6px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .sholat-item {
            border-radius: 14px;
            padding: 8px 10px;
        }
        .sholat-label {
            font-size: 0.86rem;
            font-weight: 600;
        }
        .sholat-sub {
            font-size: 0.8rem;
            margin-top: 2px;
        }
        .sholat-ok {
            background: #ecfdf3;
            color: #14532d;
        }
        .sholat-alpa {
            background: #fee2e2;
            color: #991b1b;
        }
        .sholat-belum {
            background: #f3f4f6;
            color: #374151;
        }
        .empty {
            margin-top: 20px;
            font-size: 0.9rem;
            color: #9ca3af;
            text-align: center;
        }
        .error {
            margin-bottom: 16px;
            padding: 10px 12px;
            font-size: 0.85rem;
            border-radius: 10px;
            background: #fef2f2;
            color: #b91c1c;
        }
        @media (min-width: 960px) {
            body { justify-content: flex-start; }
            .app { max-width: 1024px; margin-left: 272px; }
            .content { max-width: 520px; margin: 48px auto 24px; margin-left: max(0, calc(50vw - 532px)); }
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
        <div class="title">Log Marifah</div>
    </div>

    <div class="content">
        @if($error)
            <div class="error">
                <i class="fas fa-circle-exclamation" style="margin-right:6px;"></i>{{ $error }}
            </div>
        @endif

        <form method="GET" action="{{ route('presensi.log-marifah') }}" class="filter-row">
            <div>
                <div class="date-label">Tanggal</div>
                <div class="date-value">{{ $tanggal }}</div>
            </div>
            <div>
                <input type="date" name="tanggal" value="{{ $tanggal }}" max="{{ $today }}" style="display:none;" id="tanggalPicker">
                <button type="button" class="btn-date" onclick="document.getElementById('tanggalPicker').showPicker && document.getElementById('tanggalPicker').showPicker();">
                    <i class="fas fa-calendar-day"></i>
                    Pilih tanggal
                </button>
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

        <div class="search-wrap" style="margin-bottom:16px;">
            
            <input type="text" class="search-input" id="searchInput" placeholder="Cari nama atau NIS" autocomplete="off" style="width:100%;padding:14px 18px 14px 44px;border:1px solid #e2e8f0;border-radius:12px;font-size:0.95rem;font-family:inherit;background:#fff;box-shadow:0 4px 20px rgba(109,40,217,0.08);">
        </div>

        <div class="section-title">Siswa</div>

        <div class="list">
            @forelse($entries as $entry)
                @php
                    $title = $entry['NamaCust'] ?? $entry['NAMA'] ?? $entry['NAMASISWA'] ?? 'Siswa';
                    $subtitle = $entry['UNIT'] ?? $entry['Unit'] ?? null;
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
                        $sholatRows = [];
                        for ($i = 1; $i <= 5; $i++) {
                            $jadwalKey = 'JADWAL_' . $i;
                            $userKey   = 'USER_' . $i;
                            $statusVal = $entry[$jadwalKey] ?? null;
                            $userVal   = $entry[$userKey] ?? null;
                            if ($statusVal !== null) {
                                $sholatRows[] = [
                                    'index'  => $i,
                                    'status' => $statusVal,
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
                                    $cls = $s === 'ALPA' ? 'sholat-item sholat-alpa'
                                        : ($s === 'BELUM PRESENSI' ? 'sholat-item sholat-belum' : 'sholat-item sholat-ok');
                                @endphp
                                <div class="{{ $cls }}">
                                    <div class="sholat-label">Sholat {{ $row['index'] }}</div>
                                    <div class="sholat-sub">
                                        {{ $row['status'] ?: '-' }}
                                        @if($row['user'])
                                            <span style="opacity:0.75;">&nbsp;{{ $row['user'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="empty">Tidak ada data log marifah untuk tanggal ini.</div>
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
            <a href="{{ route('presensi.log-marifah') }}" class="drawer-link active">
                <span class="icon"><i class="fas fa-rectangle-list"></i></span>
                <span>Log Marifah</span>
            </a>
        </li>
        <li class="drawer-item">
            <a href="{{ route('presensi.log-presensi') }}" class="drawer-link">
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

