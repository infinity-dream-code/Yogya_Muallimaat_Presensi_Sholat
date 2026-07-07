<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password - Presensi Sholat</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: linear-gradient(135deg, #faf8fc 0%, #f3eff9 50%, #ebe5f5 100%); display: flex; justify-content: center; }
        .app { width: 100%; max-width: 600px; min-height: 100vh; color: #1e1b2e; position: relative; }
        .drawer-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); opacity: 0; pointer-events: none; transition: opacity 0.25s; z-index: 50; }
        .drawer-backdrop.open { opacity: 1; pointer-events: auto; }
        .drawer { position: fixed; top: 0; left: 0; width: 280px; height: 100%; background: linear-gradient(180deg, #1e1b4b 0%, #0f0d1e 100%); color: #e2e8f0; transform: translateX(-100%); transition: transform 0.25s; padding: 24px 20px; display: flex; flex-direction: column; z-index: 51; border-right: 1px solid rgba(255,255,255,0.08); box-shadow: 8px 0 32px rgba(0,0,0,0.2); }
        .drawer.open { transform: translateX(0); }
        .drawer-header { display: flex; align-items: center; margin-bottom: 20px; }
        .drawer-logo { width: 48px; height: 48px; border-radius: 12px; overflow: hidden; margin-right: 12px; flex-shrink: 0; }
        .drawer-logo img { width: 100%; height: 100%; object-fit: contain; }
        .drawer-user-name { font-size: 0.95rem; font-weight: 600; color: #f8fafc; }
        .drawer-user-role { font-size: 0.78rem; color: #94a3b8; }
        .drawer-divider { height: 1px; background: rgba(255,255,255,0.1); margin: 16px 0; }
        .drawer-menu { list-style: none; padding: 0; margin: 0; flex: 1; }
        .drawer-menu-label { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.12em; color: #64748b; margin-bottom: 10px; padding-left: 14px; font-weight: 600; }
        .drawer-item { margin-bottom: 4px; }
        .drawer-link { display: flex; align-items: center; padding: 12px 14px; border-radius: 12px; color: #cbd5e1; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: all 0.2s; }
        .drawer-link span.icon { width: 24px; display: inline-flex; justify-content: center; align-items: center; margin-right: 12px; color: #94a3b8; }
        .drawer-link:hover { background: rgba(255,255,255,0.08); color: #f8fafc; }
        .drawer-link.active { background: linear-gradient(135deg, rgba(124,58,237,0.3) 0%, rgba(109,40,217,0.2) 100%); color: #e9d5ff; }
        .drawer-link.active span.icon { color: #c4b5fd; }
        .drawer-footer { font-size: 0.75rem; color: #64748b; margin-top: auto; padding-top: 16px; }
        .header { display: flex; align-items: center; padding: 20px 20px 16px; }
        .burger { width: 36px; height: 36px; margin-right: 12px; border: none; border-radius: 10px; background: rgba(109, 40, 217, 0.08); padding: 0; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; }
        .burger span { display: block; width: 18px; height: 2.5px; border-radius: 2px; background: #6d28d9; }
        .title { font-size: 1.35rem; font-weight: 700; color: #1e1b2e; }
        .content { padding: 40px 20px 32px; }
        .card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(124, 58, 237, 0.08); border: 1px solid rgba(109, 40, 217, 0.06); }
        .form-group { margin-bottom: 16px; }
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-icon { position: absolute; left: 14px; font-size: 1rem; color: #94a3b8; }
        input[type="password"] { width: 100%; border-radius: 12px; border: 1px solid #e2e8f0; padding: 12px 14px 12px 42px; font-size: 0.95rem; font-family: inherit; background: #fff; outline: none; }
        input[type="password"]:focus { border-color: #6d28d9; }
        .btn-submit { margin-top: 18px; width: 100%; border-radius: 12px; border: none; padding: 14px 18px; font-size: 0.98rem; font-weight: 600; color: #fff; background: linear-gradient(135deg, #6d28d9 0%, #8b5cf6 100%); box-shadow: 0 4px 14px rgba(109, 40, 217, 0.35); cursor: pointer; }
        .btn-submit:hover { transform: translateY(-1px); }
        @media (min-width: 960px) {
            body { justify-content: flex-start; }
            .app { max-width: 1024px; margin-left: 280px; }
            .content { max-width: 480px; margin: 48px auto; margin-left: max(0, calc(50vw - 560px)); }
            .drawer { transform: translateX(0); }
            .drawer-backdrop { display: none; }
            .burger { display: none; }
        }
    </style>
</head>
<body>
    <div class="app">
        <div class="header">
            <button class="burger" id="drawerToggle" type="button" aria-label="Menu"><span></span><span></span><span></span></button>
            <div class="title">Ganti Password</div>
        </div>

        <main class="content">
            <div class="card">
                <form method="POST" action="{{ route('presensi.account.ganti-password.post') }}">
                    @csrf

                    <div class="form-group">
                        <div class="input-wrapper">
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                            <input type="password" name="old_password" id="old_password" placeholder="Password Lama" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="input-wrapper">
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                            <input type="password" name="new_password" id="new_password" placeholder="Password Baru" required minlength="3">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="input-wrapper">
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="Konfirmasi Password Baru" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Ganti Password</button>
                </form>
            </div>
        </main>
    </div>

    <div class="drawer-backdrop" id="drawerBackdrop"></div>
    <aside class="drawer" id="drawer">
        <div class="drawer-header">
            <div class="drawer-logo"><img src="{{ asset('logo.png') }}" alt="Logo"></div>
            <div>
                <div class="drawer-user-name">{{ session('user.username', '-') }}</div>
                <div class="drawer-user-role">User</div>
            </div>
        </div>
        <div class="drawer-divider"></div>
        <div class="drawer-menu-label">Presensi Sholat</div>
        <ul class="drawer-menu">
            <li class="drawer-item"><a href="{{ route('presensi-sholat.qr') }}" class="drawer-link"><span class="icon"><i class="fas fa-qrcode"></i></span><span>Presensi Sholat</span></a></li>
            <li class="drawer-item"><a href="{{ route('presensi-haid.qr') }}" class="drawer-link"><span class="icon"><i class="fas fa-qrcode"></i></span><span>Presensi Haid</span></a></li>
            <li class="drawer-item"><a href="{{ route('presensi.log-marifah') }}" class="drawer-link"><span class="icon"><i class="fas fa-rectangle-list"></i></span><span>Log Marifah</span></a></li>
            <li class="drawer-item"><a href="{{ route('presensi.log-presensi') }}" class="drawer-link"><span class="icon"><i class="fas fa-square-check"></i></span><span>Log Presensi</span></a></li>
            <li class="drawer-item"><a href="{{ route('presensi.kelola') }}" class="drawer-link"><span class="icon"><i class="fas fa-arrows-rotate"></i></span><span>Kelola Presensi</span></a></li>
            <li class="drawer-item"><a href="{{ route('presensi.rekap-sholat') }}" class="drawer-link"><span class="icon"><i class="fas fa-chart-simple"></i></span><span>Rekap Sholat</span></a></li>
            <li class="drawer-item"><a href="{{ route('presensi.account.ganti-password') }}" class="drawer-link active"><span class="icon"><i class="fas fa-gear"></i></span><span>Account Controls</span></a></li>
            <li class="drawer-item">
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="drawer-link" style="width: 100%; text-align: left; border: none; background: transparent; color: inherit; cursor: pointer; font-size: inherit; font-family: inherit; padding: 12px 14px;">
                        <span class="icon"><i class="fas fa-right-from-bracket"></i></span><span>Log Out</span>
                    </button>
                </form>
            </li>
        </ul>
        <div class="drawer-footer">App Ver : 1.2.1</div>
    </aside>

    <script>
        var toggleBtn = document.getElementById('drawerToggle');
        var backdrop = document.getElementById('drawerBackdrop');
        var drawer = document.getElementById('drawer');
        function closeDrawer() { drawer.classList.remove('open'); backdrop.classList.remove('open'); }
        toggleBtn.addEventListener('click', function () {
            if (drawer.classList.contains('open')) closeDrawer();
            else { drawer.classList.add('open'); backdrop.classList.add('open'); }
        });
        backdrop.addEventListener('click', closeDrawer);
    </script>

    @if (session('password_error'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({ icon: 'error', title: 'Gagal Mengubah Password', text: @json(session('password_error')), confirmButtonColor: '#6d28d9' });
            });
        </script>
    @endif
    @if (session('password_success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('password_success')), confirmButtonColor: '#6d28d9' }).then(function () {
                    window.location.href = @json(route('dashboard.presensi-sholat'));
                });
            });
        </script>
    @endif
</body>
</html>
