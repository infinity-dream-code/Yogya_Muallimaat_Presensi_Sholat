<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Yogya Muallimaat</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if(config('services.cloudflare_turnstile.site_key'))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(145deg, #faf5ff 0%, #f5ebff 35%, #ede9fe 100%);
            color: #2b2340;
            padding: 20px;
        }

        .page-wrapper {
            width: 100%;
            max-width: 440px;
            display: flex;
            justify-content: center;
        }

        .card {
            width: 100%;
            background: #ffffff;
            border-radius: 28px;
            padding: 36px 28px 32px;
            box-shadow: 0 25px 50px -12px rgba(109, 40, 217, 0.2), 0 0 0 1px rgba(255,255,255,0.8) inset;
            position: relative;
            border: 1px solid rgba(168, 85, 247, 0.12);
        }

        .logo-wrapper {
            width: 88px;
            height: 88px;
            margin: -52px auto 0;
            border-radius: 22px;
            background: linear-gradient(145deg, #fef3ff 0%, #f5ebff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 32px rgba(88, 28, 135, 0.22);
            border: 3px solid #fff;
        }

        .logo-wrapper img {
            max-width: 58px;
            max-height: 58px;
            object-fit: contain;
        }

        .welcome-text {
            margin-top: 28px;
            margin-bottom: 26px;
            text-align: center;
        }

        .welcome-text h1 {
            font-size: 1.85rem;
            line-height: 1.2;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #1e1b2e;
        }

        .welcome-text h1 span {
            display: block;
        }

        .welcome-text p {
            margin-top: 10px;
            font-size: 0.9rem;
            font-weight: 400;
            color: #64748b;
            line-height: 1.45;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #6d5a8a;
            margin-bottom: 8px;
            letter-spacing: 0.02em;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap .icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: #94a3b8;
            pointer-events: none;
        }

        .input-wrap input,
        .input-wrap select {
            padding-left: 44px;
        }

        .app-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            border-radius: 14px;
            border: 1.5px solid rgba(168, 85, 247, 0.25);
            padding: 14px 16px;
            font-size: 0.95rem;
            font-family: inherit;
            color: #332847;
            background: #faf5ff;
            font-weight: 500;
        }

        .app-badge i {
            color: #a855f7;
            font-size: 1.1rem;
        }

        select,
        input {
            width: 100%;
            border-radius: 14px;
            border: 1.5px solid #e2e8f0;
            padding: 12px 16px;
            font-size: 0.95rem;
            font-family: inherit;
            color: #332847;
            background: #fafafa;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        input::placeholder {
            color: #94a3b8;
        }

        select:focus,
        input:focus {
            border-color: #a855f7;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.18);
        }

        .btn-submit {
            width: 100%;
            border: none;
            border-radius: 14px;
            margin-top: 22px;
            padding: 14px 18px;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            color: #ffffff;
            background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 50%, #c026d3 100%);
            cursor: pointer;
            box-shadow: 0 14px 28px rgba(124, 58, 237, 0.4);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 36px rgba(124, 58, 237, 0.45);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .captcha-wrap {
            margin-top: 14px;
            margin-bottom: 8px;
            display: flex;
            justify-content: center;
        }

        @media (max-width: 480px) {
            .card {
                border-radius: 24px;
                padding: 32px 22px 28px;
            }

            .welcome-text h1 {
                font-size: 1.65rem;
            }

            .welcome-text p {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="card">
            <div class="logo-wrapper">
                <img src="{{ asset('logo.png') }}" alt="Logo Yogya Muallimaat">
            </div>

            <div class="welcome-text">
                <h1>
                    <span>Selamat Datang.</span>
                </h1>
                <p>Silahkan Masukan Username dan Password untuk masuk ke App Yogya Muallimaat</p>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label for="app">Aplikasi</label>
                    <div class="input-wrap">
                        <span class="icon"><i class="fas fa-layer-group"></i></span>
                        <select id="app" name="app" required>
                            <option value="presensi-sholat" {{ old('app', 'presensi-sholat') === 'presensi-sholat' ? 'selected' : '' }}>
                                Aplikasi Presensi Sholat
                            </option>
                            {{-- Aplikasi Laporan sementara disembunyikan (belum dipakai) --}}
                            <option value="approval-prestasi" {{ old('app') === 'approval-prestasi' ? 'selected' : '' }}>
                                Aplikasi Approval Prestasi
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrap">
                        <span class="icon"><i class="fas fa-user"></i></span>
                        <input id="username" type="text" name="username" placeholder="Masukan username Anda" autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <span class="icon"><i class="fas fa-lock"></i></span>
                        <input id="password" type="password" name="password" placeholder="Masukan password" autocomplete="current-password">
                    </div>
                </div>

                @if(config('services.cloudflare_turnstile.site_key'))
                    <div class="captcha-wrap">
                        <div
                            class="cf-turnstile"
                            data-sitekey="{{ config('services.cloudflare_turnstile.site_key') }}"
                            data-theme="light"
                            data-language="id">
                        </div>
                    </div>
                @endif

                <button type="submit" class="btn-submit">
                    <i class="fas fa-right-to-bracket"></i>
                    Masuk
                </button>
            </form>
        </div>
    </div>
</body>
@if (session('login_error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'error',
                title: 'Login Gagal',
                text: @json(session('login_error')),
                confirmButtonColor: '#a855f7'
            });
        });
    </script>
@endif
</html>
