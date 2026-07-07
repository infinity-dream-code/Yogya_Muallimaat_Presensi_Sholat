<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Monitoring Kepsek</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .app { width: 100%; max-width: 520px; min-height: 100vh; background: #fff7ff; color: #2b2340; position: relative; }
        .drawer-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.6); opacity: 0; pointer-events: none; transition: opacity .2s; z-index: 30; }
        .drawer { position: fixed; top: 0; left: 0; width: 272px; height: 100%; background: linear-gradient(180deg,#0f172a 0%,#020617 100%); color: #f9fafb; transform: translateX(-100%); transition: transform .2s; padding: 24px 20px; display: flex; flex-direction: column; z-index: 40; }
        .drawer.open { transform: translateX(0); }
        .drawer-backdrop.open { opacity: 1; pointer-events: auto; }
        .drawer-header { display: flex; align-items: center; margin-bottom: 20px; }
        .drawer-logo { width: 50px; height: 50px; border-radius: 999px; overflow: hidden; margin-right: 10px; }
        .drawer-logo img { width: 100%; height: 100%; object-fit: contain; }
        .drawer-user-name { font-size: .95rem; font-weight: 600; }
        .drawer-user-role { font-size: .78rem; opacity: .75; }
        .drawer-divider { height: 1px; background: #4b5563; margin: 16px 0; }
        .drawer-menu-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .1em; color: #94a3b8; margin-bottom: 10px; padding-left: 14px; }
        .drawer-menu { list-style: none; padding: 0; margin: 0; flex: 1; }
        .drawer-item { margin-bottom: 12px; }
        .drawer-link { display: flex; align-items: center; padding: 12px 14px; border-radius: 999px; color: inherit; text-decoration: none; font-size: .9rem; border: none; background: transparent; width: 100%; cursor: pointer; font-family: inherit; }
        .drawer-link span.icon { width: 26px; display: inline-flex; justify-content: center; margin-right: 12px; }
        .drawer-link:hover, .drawer-link.active { background: rgba(148,163,184,.18); }
        .drawer-footer { font-size: .78rem; opacity: .7; margin-top: 12px; }
        .header { display: flex; align-items: center; padding: 16px; }
        .burger { width: 28px; margin-right: 12px; border: none; background: transparent; padding: 0; cursor: pointer; }
        .burger span { display: block; height: 3px; border-radius: 999px; background: #7c3aed; margin-bottom: 5px; }
        .title { font-size: 1.25rem; font-weight: 600; color: #a855f7; }
        .content { padding: 24px 16px 32px; }
        .card { margin-top: 20px; background: #fff; border-radius: 22px; padding: 24px 20px; box-shadow: 0 20px 40px rgba(109,40,217,.25); }
        .card h2 { margin: 0 0 8px; font-size: 1.1rem; }
        .card p { margin: 0; font-size: .9rem; color: #6b607f; line-height: 1.5; }
        .menu-card { display: block; margin-top: 20px; background: linear-gradient(135deg,#8b5cf6 0%,#a855f7 100%); border-radius: 18px; padding: 20px; color: #fff; text-decoration: none; box-shadow: 0 12px 28px rgba(124,58,237,.35); }
        .menu-card i { font-size: 1.5rem; margin-bottom: 10px; display: block; }
        .menu-card strong { display: block; font-size: 1rem; margin-bottom: 4px; }
        .menu-card span { font-size: .85rem; opacity: .9; }
        @media (min-width: 960px) {
            body { justify-content: flex-start; }
            .app { max-width: 1024px; margin-left: 272px; }
            .content { max-width: 520px; margin: 64px auto 32px; margin-left: max(0,calc(50vw - 532px)); }
            .drawer { transform: translateX(0); }
            .drawer-backdrop { display: none; }
            .burger { display: none; }
        }
    </style>
</head>
<body>
<div class="app">
    <header class="header">
        <button class="burger" id="drawerToggle" type="button"><span></span><span></span><span></span></button>
        <h1 class="title">Monitoring Kepsek</h1>
    </header>
    <main class="content">
        <div class="card">
            <h2>Selamat datang, {{ session('user.nama', session('user.username')) }}</h2>
            <p>Aplikasi Monitoring Kepala Sekolah. Pilih menu Tagihan untuk melihat data tagihan siswa.</p>
        </div>
        <a href="{{ route('kepsek.tagihan') }}" class="menu-card">
            <i class="fas fa-file-invoice-dollar"></i>
            <strong>Tagihan</strong>
            <span>Lihat tagihan siswa, filter, dan export data</span>
        </a>
    </main>
</div>

<div class="drawer-backdrop" id="drawerBackdrop"></div>
<aside class="drawer" id="drawer">
    <div class="drawer-header">
        <div class="drawer-logo"><img src="{{ asset('logo.png') }}" alt="Logo"></div>
        <div>
            <div class="drawer-user-name">{{ session('user.nama', session('user.username')) }}</div>
            <div class="drawer-user-role">Kepala Sekolah</div>
        </div>
    </div>
    <div class="drawer-divider"></div>
    <div class="drawer-menu-label">Menu</div>
    <ul class="drawer-menu">
        <li class="drawer-item">
            <a href="{{ route('kepsek.tagihan') }}" class="drawer-link">
                <span class="icon"><i class="fas fa-file-invoice-dollar"></i></span>
                <span>Tagihan</span>
            </a>
        </li>
        <li class="drawer-item">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="drawer-link">
                    <span class="icon"><i class="fas fa-right-from-bracket"></i></span>
                    <span>Log Out</span>
                </button>
            </form>
        </li>
    </ul>
    <div class="drawer-footer">App Ver : 1.0.0</div>
</aside>

<script>
    const toggleBtn = document.getElementById('drawerToggle');
    const backdrop = document.getElementById('drawerBackdrop');
    const drawer = document.getElementById('drawer');
    function closeDrawer(){ drawer.classList.remove('open'); backdrop.classList.remove('open'); }
    toggleBtn.addEventListener('click', function(){
        const isOpen = drawer.classList.contains('open');
        if (isOpen) closeDrawer(); else { drawer.classList.add('open'); backdrop.classList.add('open'); }
    });
    backdrop.addEventListener('click', closeDrawer);
</script>
@if (session('login_success'))
<script>
    Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('login_success')), confirmButtonColor: '#a855f7' });
</script>
@endif
</body>
</html>
