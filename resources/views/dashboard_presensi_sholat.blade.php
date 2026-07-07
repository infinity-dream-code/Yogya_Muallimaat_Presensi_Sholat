<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Presensi Sholat</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
        .drawer.open {
            transform: translateX(0);
        }
        .drawer-backdrop.open {
            opacity: 1;
            pointer-events: auto;
        }
        .drawer-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .drawer-logo {
            width: 50px;
            height: 50px;
            border-radius: 999px;
            overflow: hidden;
            margin-right: 10px;
        }
        .drawer-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .drawer-user-name {
            font-size: 0.95rem;
            font-weight: 600;
        }
        .drawer-user-role {
            font-size: 0.78rem;
            opacity: 0.75;
        }
        .drawer-divider {
            height: 1px;
            background: #4b5563;
            margin: 16px 0;
        }
        .drawer-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            flex: 1;
        }
        .drawer-menu-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            margin-bottom: 10px;
            padding-left: 14px;
        }
        .drawer-item {
            margin-bottom: 12px;
        }
        .drawer-link {
            display: flex;
            align-items: center;
            padding: 12px 14px;
            border-radius: 999px;
            color: inherit;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.15s ease;
        }
        .drawer-link span.icon {
            width: 26px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin-right: 12px;
        }
        .drawer-link span.icon i {
            font-size: 1.05rem;
            color: #e2e8f0 !important;
        }
        .drawer-link:hover {
            background: rgba(148, 163, 184, 0.18);
        }
        .drawer-link.active {
            background: rgba(148, 163, 184, 0.15);
        }
        .drawer-link:hover span.icon i,
        .drawer-link:active span.icon i,
        .drawer-link.active span.icon i,
        .drawer-link:focus span.icon i,
        .drawer-link:visited span.icon i {
            color: #e2e8f0 !important;
        }
        .drawer-link a span.icon i,
        .drawer-link button span.icon i {
            color: #e2e8f0 !important;
        }
        .drawer-footer {
            font-size: 0.78rem;
            opacity: 0.7;
            margin-top: 12px;
        }
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
            padding: 40px 16px 32px;
        }
        .card {
            margin-top: 20px;
            background: #ffffff;
            border-radius: 22px;
            padding: 18px 16px 20px;
            box-shadow: 0 20px 40px rgba(109, 40, 217, 0.25);
        }
        .card h2 {
            margin: 0 0 8px;
            font-size: 1.1rem;
        }
        .card p {
            margin: 0;
            font-size: 0.9rem;
            color: #6b607f;
        }
        @media (min-width: 960px) {
            body {
                justify-content: flex-start;
            }
            .app {
                max-width: 1024px;
                margin-left: 272px;
            }
            .content {
                max-width: 520px;
                margin: 64px auto 32px;
                margin-left: max(0, calc(50vw - 532px));
            }
            .drawer {
                position: fixed;
                transform: translateX(0);
            }
            .drawer-backdrop {
                display: none;
            }
            .burger {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="app">
        <div class="header">
            <button class="burger" id="drawerToggle" type="button" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="title">Presensi Sholat</div>
        </div>

        <div class="content">
            <div class="card">
                <h2>Presensi Sholat</h2>
                <p>Pilih menu di sidebar (ikon burger) untuk navigasi ke halaman presensi sholat lainnya.</p>
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
    </script>
    @if (session('login_success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: @json(session('login_success')),
                    confirmButtonColor: '#a855f7'
                });
            });
        </script>
    @endif
</body>
</html>

