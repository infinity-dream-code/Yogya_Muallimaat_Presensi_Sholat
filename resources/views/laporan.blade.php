<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Perizinan</title>
    @php $pageTitle = 'Laporan Perizinan'; @endphp
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;font-family:'Poppins',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#fff7ff;display:flex;justify-content:center}
        .app{width:100%;max-width:520px;min-height:100vh;background:#fff7ff;color:#2b2340;position:relative}
        .drawer-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.6);opacity:0;pointer-events:none;transition:opacity .2s ease;z-index:30}
        .drawer{position:fixed;top:0;left:0;width:272px;height:100%;background:linear-gradient(180deg,#0f172a 0%,#020617 100%);color:#f9fafb;transform:translateX(-100%);transition:transform .2s ease;padding:24px 20px;display:flex;flex-direction:column;z-index:40;border-right:1px solid rgba(255,255,255,.06);box-shadow:4px 0 24px rgba(0,0,0,.2)}
        .drawer.open{transform:translateX(0)}
        .drawer-backdrop.open{opacity:1;pointer-events:auto}
        .drawer-header{display:flex;align-items:center;margin-bottom:20px}
        .drawer-logo{width:50px;height:50px;border-radius:999px;overflow:hidden;margin-right:10px}
        .drawer-logo img{width:100%;height:100%;object-fit:contain}
        .drawer-user-name{font-size:.95rem;font-weight:600}
        .drawer-user-role{font-size:.78rem;opacity:.75}
        .drawer-divider{height:1px;background:#4b5563;margin:16px 0}
        .drawer-menu-label{font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;margin-bottom:10px;padding-left:14px}
        .drawer-menu{list-style:none;padding:0;margin:0;flex:1}
        .drawer-item{margin-bottom:12px}
        .drawer-link{display:flex;align-items:center;padding:12px 14px;border-radius:999px;color:inherit;text-decoration:none;font-size:.9rem;transition:background .15s ease}
        .drawer-link span.icon{width:26px;display:inline-flex;justify-content:center;align-items:center;margin-right:12px}
        .drawer-link span.icon i{font-size:1.05rem;color:#e2e8f0!important}
        .drawer-link:hover{background:rgba(148,163,184,.18)}
        .drawer-link.active{background:rgba(148,163,184,.15)}
        .drawer-footer{font-size:.78rem;opacity:.7;margin-top:12px}
        .header{display:flex;align-items:center;padding:16px 16px 10px}
        .burger{width:28px;margin-right:12px;border:none;background:transparent;padding:0;cursor:pointer}
        .burger span{display:block;height:3px;border-radius:999px;background:#7c3aed;margin-bottom:5px}
        .title{font-size:1.25rem;font-weight:600;color:#a855f7}
        .content{padding:24px 16px}
        .info-text{font-size:.85rem;color:#64748b;margin-bottom:16px;text-align:center}
        .alert{padding:12px 14px;border-radius:12px;margin-bottom:14px;font-size:.9rem}
        .alert.error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
        .table-wrapper{overflow-x:auto;-webkit-overflow-scrolling:touch;background:#fff;border-radius:14px;box-shadow:0 4px 12px rgba(0,0,0,.08);max-height:calc(100vh - 200px);overflow-y:auto}
        .table-wrapper::-webkit-scrollbar{width:8px;height:8px}
        .table-wrapper::-webkit-scrollbar-track{background:#f1f1f1;border-radius:4px}
        .table-wrapper::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}
        .table-wrapper::-webkit-scrollbar-thumb:hover{background:#94a3b8}
        table{width:100%;border-collapse:collapse;min-width:1100px;background:#fff}
        thead{background:#f8fafc;position:sticky;top:0;z-index:10}
        th{padding:14px 16px;text-align:left;font-size:.875rem;font-weight:600;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;background:#f8fafc}
        td{padding:12px 16px;font-size:.9rem;color:#1e293b;border-bottom:1px solid #f1f5f9;word-break:break-word;vertical-align:top}
        tbody tr{transition:background .15s ease}
        tbody tr:nth-child(even){background:#fafafa}
        tbody tr:hover{background:#f5f5f5}
        tbody tr:last-child td{border-bottom:none}
        .badge{display:inline-block;padding:3px 10px;border-radius:999px;font-size:.78rem;font-weight:600;white-space:nowrap}
        .badge.ok{background:#dcfce7;color:#166534}
        .badge.warn{background:#fef9c3;color:#854d0e}
        .badge.err{background:#fee2e2;color:#991b1b}
        .empty-state{text-align:center;padding:40px 20px;color:#94a3b8}
        .empty-state i{font-size:3rem;margin-bottom:12px;opacity:.5}
        @media (max-width:768px){th,td{padding:10px 12px;font-size:.85rem}}
        @media (min-width:960px){
            body{justify-content:flex-start}
            .app{max-width:100%;margin-left:272px}
            .drawer{transform:translateX(0)}
            .drawer-backdrop{display:none}
            .header{padding-left:32px}
            .content{padding:48px 32px;max-width:1200px;margin:0 auto;margin-left:max(0,calc(50vw - 872px));margin-right:auto}
        }
    </style>
</head>
<body>
<div class="app">
    <header class="header">
        <button class="burger" id="drawerToggle">
            <span></span><span></span><span></span>
        </button>
        <h1 class="title">{{ $pageTitle }} {{ session('user.username', '') }} (Superadmin)</h1>
    </header>

    <main class="content">
        <div class="info-text">Hanya Menampilkan 100 Pencatatan Terakhir</div>

        @if(!empty($error))
            <div class="alert error">{{ $error }}</div>
        @endif

        <div class="table-wrapper">
            @php
                $rows = $rows ?? [];
                $fmt = function ($v) {
                    if ($v === null) return '-';
                    $v = trim((string)$v);
                    if ($v === '' || $v === '-') return '-';
                    return $v;
                };
                $badgeClass = function ($v) {
                    $t = strtoupper((string)$v);
                    if (str_contains($t, 'OK') || $t === 'DONE') return 'ok';
                    if (str_contains($t, 'PENALTY') || str_contains($t, 'NOTOK') || str_contains($t, 'ERROR')) return 'err';
                    return 'warn';
                };
            @endphp

            @if(empty($rows))
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Tidak ada data laporan</p>
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Keterangan</th>
                            <th>Tanggal Keluar</th>
                            <th>Batas Masuk</th>
                            <th>Tanggal Masuk</th>
                            <th>Jenis Izin</th>
                            <th>Hasil Izin</th>
                            <th>Status Izin</th>
                            <th>User Pemberi Izin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @php $row = is_array($row) ? $row : (array)$row; @endphp
                            <tr>
                                <td>{{ $fmt($row['NamaCust'] ?? '-') }}</td>
                                <td>{{ $fmt($row['KETERANGAN'] ?? '-') }}</td>
                                <td>{{ $fmt($row['DATEKELUAR'] ?? '-') }}</td>
                                <td>{{ $fmt($row['DATEBATAS'] ?? '-') }}</td>
                                <td>{{ $fmt($row['DATEMASUK'] ?? '-') }}</td>
                                <td><span class="badge {{ $badgeClass($row['JENIS'] ?? '') }}">{{ $fmt($row['JENIS'] ?? '-') }}</span></td>
                                <td><span class="badge {{ $badgeClass($row['HASIL'] ?? '') }}">{{ $fmt($row['HASIL'] ?? '-') }}</span></td>
                                <td><span class="badge {{ $badgeClass($row['STATUS'] ?? '') }}">{{ $fmt($row['STATUS'] ?? '-') }}</span></td>
                                <td>{{ $fmt($row['USER'] ?? '-') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </main>
</div>

<div class="drawer-backdrop" id="drawerBackdrop"></div>
<aside class="drawer" id="drawer">
    <div class="drawer-header">
        <div class="drawer-logo">
            <img src="{{ asset('logo.png') }}" alt="Logo">
        </div>
        <div>
            <div class="drawer-user-name">{{ session('user.username', 'admin') }}</div>
            <div class="drawer-user-role">Superadmin</div>
        </div>
    </div>

    <div class="drawer-divider"></div>

    <div class="drawer-menu-label">Perizinan</div>
    <ul class="drawer-menu">
        <li class="drawer-item">
            <a href="{{ route('perizinan.kedatangan') }}" class="drawer-link">
                <span class="icon"><i class="fas fa-user-clock"></i></span>
                <span>Kedatangan/Kepulangan</span>
            </a>
        </li>
        <li class="drawer-item">
            <a href="{{ route('perizinan.umum') }}" class="drawer-link">
                <span class="icon"><i class="fas fa-pen-to-square"></i></span>
                <span>Perizinan Umum</span>
            </a>
        </li>
        <li class="drawer-item">
            <a href="{{ route('perizinan.khusus') }}" class="drawer-link">
                <span class="icon"><i class="fas fa-user-group"></i></span>
                <span>Perizinan Khusus</span>
            </a>
        </li>
        <li class="drawer-item">
            <a href="{{ route('laporan') }}" class="drawer-link active">
                <span class="icon"><i class="fas fa-file-lines"></i></span>
                <span>Laporan Perizinan</span>
            </a>
        </li>
        <li class="drawer-item">
            <a href="{{ route('account.ganti-password') }}" class="drawer-link">
                <span class="icon"><i class="fas fa-gear"></i></span>
                <span>Account Controls</span>
            </a>
        </li>
        <li class="drawer-item">
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="drawer-link" style="width:100%;text-align:left;border:none;background:transparent;color:inherit;cursor:pointer;font-size:inherit;font-family:inherit;padding:12px 14px;">
                    <span class="icon"><i class="fas fa-right-from-bracket"></i></span>
                    <span>Log Out</span>
                </button>
            </form>
        </li>
    </ul>

    <div class="drawer-footer">App Ver : 1.2.1</div>
</aside>

<script>
    const toggleBtn = document.getElementById('drawerToggle');
    const backdrop = document.getElementById('drawerBackdrop');
    const drawer = document.getElementById('drawer');
    function closeDrawer(){drawer.classList.remove('open');backdrop.classList.remove('open')}
    toggleBtn.addEventListener('click',function(){const isOpen=drawer.classList.contains('open');if(isOpen){closeDrawer()}else{drawer.classList.add('open');backdrop.classList.add('open')}})
    backdrop.addEventListener('click',closeDrawer)
</script>
</body>
</html>
