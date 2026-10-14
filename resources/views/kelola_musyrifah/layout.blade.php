<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Kelola Musyrifah</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: 'Poppins', system-ui, sans-serif; background: #fff7ff; color: #2b2340; }
        .app { min-height: 100vh; }
        .drawer-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); opacity: 0; pointer-events: none; transition: opacity 0.2s ease; z-index: 30; }
        .drawer {
            position: fixed; top: 0; left: 0; width: 272px; height: 100%;
            background: linear-gradient(180deg, #0f172a 0%, #020617 100%); color: #f9fafb;
            transform: translateX(-100%); transition: transform 0.2s ease;
            padding: 24px 20px; display: flex; flex-direction: column; z-index: 40;
            border-right: 1px solid rgba(255,255,255,0.06); box-shadow: 4px 0 24px rgba(0,0,0,0.2);
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
        .drawer-link { display: flex; align-items: center; padding: 12px 14px; border-radius: 999px; color: inherit; text-decoration: none; font-size: 0.9rem; transition: background 0.15s ease; }
        .drawer-link span.icon { width: 26px; display: inline-flex; justify-content: center; align-items: center; margin-right: 12px; }
        .drawer-link span.icon i { font-size: 1.05rem; color: #e2e8f0 !important; }
        .drawer-link:hover { background: rgba(148,163,184,0.18); }
        .drawer-link.active { background: rgba(148,163,184,0.15); }
        .drawer-footer { font-size: 0.78rem; opacity: 0.7; margin-top: 12px; }

        .header { display: flex; align-items: center; padding: 16px 16px 10px; }
        .burger { width: 28px; margin-right: 12px; border: none; background: transparent; padding: 0; cursor: pointer; }
        .burger span { display: block; height: 3px; border-radius: 999px; background: #7c3aed; margin-bottom: 5px; }
        .title { font-size: 1.25rem; font-weight: 600; color: #a855f7; }
        .content { padding: 16px; max-width: 1100px; }

        .card { background: #fff; border-radius: 18px; padding: 16px; box-shadow: 0 16px 32px rgba(109, 40, 217, 0.12); }
        .alert { margin-bottom: 14px; padding: 10px 12px; font-size: 0.85rem; border-radius: 10px; }
        .alert-success { background: #ecfdf3; color: #14532d; }
        .alert-error { background: #fef2f2; color: #b91c1c; }
        .muted { color: #9ca3af; font-size: 0.82rem; }
        .btn { border: none; border-radius: 999px; padding: 9px 16px; font-family: inherit; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: #7c3aed; color: #fff; }
        .btn-light { background: #f5f3ff; color: #7c3aed; }
        .btn-sm { padding: 6px 12px; font-size: 0.78rem; }
        .input, .select { padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 12px; font-family: inherit; font-size: 0.88rem; background: #fff; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 14px; }
        .toolbar .input { flex: 1; min-width: 200px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.86rem; }
        th { text-align: left; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b21a8; background: #faf5ff; padding: 10px; white-space: nowrap; }
        td { padding: 10px; border-bottom: 1px solid #f3e8ff; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        .chip { display: inline-block; font-size: 0.75rem; padding: 3px 9px; border-radius: 999px; background: #f5f3ff; color: #6b21a8; white-space: nowrap; }
        .chip-empty { background: #fef3c7; color: #92400e; }
        .pagination { display: flex; flex-wrap: wrap; gap: 6px; justify-content: space-between; align-items: center; margin-top: 14px; }

        @media (min-width: 960px) {
            .app { margin-left: 272px; }
            .content { padding: 24px 32px; }
            .drawer { transform: translateX(0); }
            .drawer-backdrop { display: none; }
            .burger { display: none; }
            .header { padding: 24px 32px 0; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="app">
    <div class="header">
        <button class="burger" id="drawerToggle" type="button" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
        <div class="title">@yield('title')</div>
    </div>

    <div class="content">
        @if(session('success'))
            <div class="alert alert-success"><i class="fas fa-circle-check" style="margin-right:6px;"></i>{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error"><i class="fas fa-circle-exclamation" style="margin-right:6px;"></i>{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error"><i class="fas fa-circle-exclamation" style="margin-right:6px;"></i>{{ $errors->first() }}</div>
        @endif

        @yield('content')
    </div>
</div>

<div class="drawer-backdrop" id="drawerBackdrop"></div>
<aside class="drawer" id="drawer">
    <div class="drawer-header">
        <div class="drawer-logo">
            <img src="{{ asset('logo.png') }}" alt="Logo">
        </div>
        <div>
            <div class="drawer-user-name">{{ session('user.nama', session('user.username', '-')) }}</div>
            <div class="drawer-user-role">Super Admin</div>
        </div>
    </div>

    <div class="drawer-divider"></div>

    <div class="drawer-menu-label">Kelola Musyrifah</div>
    <ul class="drawer-menu">
        <li class="drawer-item">
            <a href="{{ route('kelola-musyrifah.musyrifah') }}" class="drawer-link {{ request()->routeIs('kelola-musyrifah.musyrifah') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-user-tie"></i></span>
                <span>Data Musyrifah</span>
            </a>
        </li>
        <li class="drawer-item">
            <a href="{{ route('kelola-musyrifah.siswa') }}" class="drawer-link {{ request()->routeIs('kelola-musyrifah.siswa') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-users"></i></span>
                <span>Data Siswa</span>
            </a>
        </li>
        <li class="drawer-item">
            <a href="{{ route('kelola-musyrifah.upload') }}" class="drawer-link {{ request()->routeIs('kelola-musyrifah.upload') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-file-arrow-up"></i></span>
                <span>Upload Excel</span>
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
    (function () {
        var toggleBtn = document.getElementById('drawerToggle');
        var backdrop = document.getElementById('drawerBackdrop');
        var drawer = document.getElementById('drawer');

        function closeDrawer() {
            drawer.classList.remove('open');
            backdrop.classList.remove('open');
        }

        toggleBtn.addEventListener('click', function () {
            if (drawer.classList.contains('open')) {
                closeDrawer();
            } else {
                drawer.classList.add('open');
                backdrop.classList.add('open');
            }
        });
        backdrop.addEventListener('click', closeDrawer);
    })();
</script>
@stack('scripts')
</body>
</html>
