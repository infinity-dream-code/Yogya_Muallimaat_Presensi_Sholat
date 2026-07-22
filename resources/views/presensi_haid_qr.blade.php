@php
    // Reuse the same scanner UI as presensi_sholat_qr, but point to POSTHaid endpoint
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presensi Haid QR</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
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
            z-index: 30;
        }
        .drawer {
            position: fixed;
            top: 0; left: 0;
            width: 280px; height: 100%;
            background: linear-gradient(180deg, #1e1b4b 0%, #0f0d1e 100%);
            color: #e2e8f0;
            transform: translateX(-100%);
            transition: transform 0.2s ease;
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
        .drawer-link span.icon i { font-size: 1.05rem; color: inherit !important; }
        .drawer-link:hover { background: rgba(255,255,255,0.08); color: #f8fafc; }
        .drawer-link.active { background: linear-gradient(135deg, rgba(124,58,237,0.3) 0%, rgba(109,40,217,0.2) 100%); color: #e9d5ff; }
        .drawer-link.active span.icon { color: #c4b5fd; }
        .drawer-footer { font-size: 0.75rem; color: #64748b; margin-top: auto; padding-top: 16px; }
        .header { display: flex; align-items: center; padding: 16px 16px 10px; justify-content: space-between; }
        .left-header { display: flex; align-items: center; gap: 12px; }
        .burger { width: 28px; border: none; background: transparent; padding: 0; cursor: pointer; }
        .burger span { display: block; height: 3px; border-radius: 999px; background: #7c3aed; margin-bottom: 5px; }
        .title { font-size: 1.1rem; font-weight: 600; color: #a855f7; }
        .status { display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600; color: #64748b; }
        .dot { width: 9px; height: 9px; border-radius: 999px; background: #22c55e; box-shadow: 0 0 0 4px rgba(34,197,94,0.12); }
        .content { padding: 0; }
        .mode-switch {
            display: flex;
            gap: 8px;
            padding: 0 16px 12px;
        }
        .mode-btn {
            flex: 1;
            border: 1px solid #ddd6fe;
            background: #ffffff;
            color: #6d28d9;
            border-radius: 10px;
            padding: 9px 12px;
            font-size: 0.84rem;
            font-weight: 600;
            cursor: pointer;
        }
        .mode-btn.active {
            background: #7c3aed;
            border-color: #7c3aed;
            color: #ffffff;
        }
        .scanner-wrap {
            position: relative;
            width: 100%;
            height: calc(100vh - 92px);
            background: #000;
            overflow: hidden;
        }
        .scanner-wrap.hidden { display: none; }
        .pid-wrap {
            display: none;
            padding: 20px 16px 16px;
        }
        .pid-wrap.active { display: block; }
        .pid-card {
            background: #ffffff;
            border: 1px solid #e9d5ff;
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 8px 20px rgba(76, 29, 149, 0.08);
        }
        .pid-label {
            font-size: 0.82rem;
            color: #6b607f;
            margin-bottom: 8px;
        }
        .pid-input-row {
            display: flex;
            gap: 8px;
        }
        .pid-input {
            flex: 1;
            height: 42px;
            border: 1px solid #ddd6fe;
            border-radius: 10px;
            padding: 0 12px;
            font-size: 0.9rem;
            outline: none;
        }
        .pid-input:focus {
            border-color: #a855f7;
            box-shadow: 0 0 0 3px rgba(168,85,247,0.14);
        }
        .pid-submit {
            border: none;
            border-radius: 10px;
            background: #7c3aed;
            color: #fff;
            font-weight: 600;
            padding: 0 14px;
            cursor: pointer;
        }
        .pid-help {
            margin-top: 8px;
            font-size: 0.78rem;
            color: #7b748f;
        }
        #video {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
        }
        #canvas { display: none; }
        .scan-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        .scan-box {
            position: relative;
            width: min(78vw, 78vh, 400px);
            height: min(78vw, 78vh, 400px);
        }
        .scan-box::before,
        .scan-box::after,
        .scan-box .c3,
        .scan-box .c4 {
            content: '';
            position: absolute;
            width: 32px; height: 32px;
            border-color: #a855f7;
            border-style: solid;
        }
        .scan-box::before  { top: 0;    left: 0;    border-width: 4px 0 0 4px; }
        .scan-box::after   { top: 0;    right: 0;   border-width: 4px 4px 0 0; }
        .scan-box .c3      { bottom: 0; left: 0;    border-width: 0 0 4px 4px; }
        .scan-box .c4      { bottom: 0; right: 0;   border-width: 0 4px 4px 0; }
        .scan-line {
            position: absolute;
            left: 4px; right: 4px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #a855f7, transparent);
            animation: scanline 2s linear infinite;
        }
        @keyframes scanline {
            0%   { top: 4px;   opacity: 1; }
            90%  { top: calc(100% - 6px); opacity: 1; }
            100% { top: calc(100% - 6px); opacity: 0; }
        }
        .hint {
            padding: 10px 4px;
            font-size: 0.9rem;
            color: #6b607f;
            text-align: center;
            background: #fff7ff;
        }
        @media (min-width: 960px) {
            body { justify-content: flex-start; }
            .app { max-width: none; width: calc(100% - 280px); margin-left: 280px; }
            .drawer { position: fixed; transform: translateX(0); }
            .drawer-backdrop { display: none; }
            .burger { display: none; }
        }
    </style>
</head>
<body>
<div class="app">
    <div class="header">
        <div class="left-header">
            <button class="burger" id="drawerToggle" type="button" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
            <div class="title">Presensi Haid QR</div>
        </div>
        <div class="status" id="status">
            <span class="dot"></span>
            <span>Ready</span>
        </div>
    </div>

    <div class="content">
        <div class="mode-switch">
            <button class="mode-btn active" id="modeQrBtn" type="button">QR</button>
            <button class="mode-btn" id="modePidBtn" type="button">Kartu (PID)</button>
        </div>
        <div class="scanner-wrap">
            <video id="video" autoplay playsinline muted></video>
            <canvas id="canvas"></canvas>
            <div class="scan-overlay">
                <div class="scan-box">
                    <div class="c3"></div>
                    <div class="c4"></div>
                    <div class="scan-line"></div>
                </div>
            </div>
        </div>
        <div class="pid-wrap" id="pidWrap">
            <div class="pid-card">
                <div class="pid-label">Tap kartu atau input nomor kartu/PID</div>
                <div class="pid-input-row">
                    <input type="text" id="pidInput" class="pid-input" placeholder="Nomor kartu / PID" autocomplete="off">
                    <button type="button" id="pidSubmit" class="pid-submit">Kirim</button>
                </div>
                <div class="pid-help">Tip: jika reader kartu bertipe keyboard, cukup tap kartu lalu Enter.</div>
            </div>
        </div>
        <div class="hint" id="hint">Arahkan kamera ke QR Code</div>
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
            <a href="{{ route('presensi-haid.qr') }}" class="drawer-link active">
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
    const backdrop  = document.getElementById('drawerBackdrop');
    const drawer    = document.getElementById('drawer');
    function closeDrawer() { drawer.classList.remove('open'); backdrop.classList.remove('open'); }
    toggleBtn.addEventListener('click', function() {
        drawer.classList.contains('open') ? closeDrawer() : (drawer.classList.add('open'), backdrop.classList.add('open'));
    });
    backdrop.addEventListener('click', closeDrawer);

    const statusEl = document.getElementById('status');
    const hintEl   = document.getElementById('hint');
    const video    = document.getElementById('video');
    const canvas   = document.getElementById('canvas');
    const scannerWrap = document.querySelector('.scanner-wrap');
    const pidWrap = document.getElementById('pidWrap');
    const modeQrBtn = document.getElementById('modeQrBtn');
    const modePidBtn = document.getElementById('modePidBtn');
    const pidInput = document.getElementById('pidInput');
    const pidSubmit = document.getElementById('pidSubmit');

    let stream    = null;
    let rafId     = null;
    let isPosting = false;
    let isScanning = false;
    let mode = 'qr';
    let pidBuffer = '';
    let lastPidKeyAt = 0;

    function setStatus(text, isReady) {
        var c = isReady ? '#22c55e' : '#f59e0b';
        var s = isReady ? '34,197,94' : '245,158,11';
        statusEl.innerHTML = '<span class="dot" style="background:' + c + ';box-shadow:0 0 0 4px rgba(' + s + ',0.12)"></span><span>' + text + '</span>';
    }

    function tick() {
        if (!isScanning) return;
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width  = video.videoWidth;
            canvas.height = video.videoHeight;
            var ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            var code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' });
            if (code && code.data) {
                onScanSuccess(code.data);
                return;
            }
        }
        rafId = requestAnimationFrame(tick);
    }

    async function startScanner() {
        if (stream) return;
        setStatus('Starting...', false);
        hintEl.textContent = 'Meminta izin kamera...';

        var constraints = {
            video: {
                facingMode: { ideal: 'environment' },
                width:  { ideal: 1280 },
                height: { ideal: 720 }
            }
        };

        try {
            stream = await navigator.mediaDevices.getUserMedia(constraints);
        } catch(err1) {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: true });
            } catch(err2) {
                setStatus('Camera blocked', false);
                hintEl.textContent = 'Izin kamera ditolak.';
                Swal.fire({
                    icon: 'error',
                    title: 'Izin Kamera Ditolak',
                    html: 'Klik ikon <b>kunci/kamera</b> di address bar, pilih <b>Izinkan</b>, lalu refresh.',
                    confirmButtonColor: '#a855f7'
                });
                return;
            }
        }

        video.srcObject = stream;
        video.play();
        isScanning = true;
        setStatus('Ready', true);
        hintEl.textContent = 'Arahkan kamera ke QR Code';
        rafId = requestAnimationFrame(tick);
    }

    function stopScanner() {
        isScanning = false;
        if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
        if (stream) {
            stream.getTracks().forEach(function(t){ t.stop(); });
            stream = null;
        }
        video.srcObject = null;
    }

    async function submitNokartu(rawValue) {
        if (isPosting) return;
        var nokartu = (rawValue || '').trim();
        if (!nokartu) return;

        isPosting = true;
        isScanning = false;
        if (rafId) { cancelAnimationFrame(rafId); rafId = null; }

        setStatus('Processing...', false);
        hintEl.textContent = 'Terbaca: ' + nokartu + ' (mengirim...)';

        try {
            var res = await fetch(@json(route('presensi-haid.post-haid')), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ nokartu: nokartu })
            });

            var json = {};
            try { json = await res.json(); } catch(e) {}

            if (res.ok && json.ok) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: json.message || 'Presensi haid berhasil.',
                    confirmButtonColor: '#a855f7',
                    timer: 2000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            } else {
                await Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: (json && json.message) ? json.message : 'Presensi gagal (HTTP ' + res.status + ').',
                    confirmButtonColor: '#a855f7',
                    timer: 2000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            }
        } catch(e) {
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Gagal mengirim data. Coba lagi.',
                confirmButtonColor: '#a855f7',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false
            });
        } finally {
            isPosting = false;
            setStatus('Ready', true);
            if (mode === 'qr') {
                hintEl.textContent = 'Arahkan kamera ke QR Code';
                isScanning = true;
                rafId = requestAnimationFrame(tick);
            } else {
                hintEl.textContent = 'Tempel kartu PID atau input manual.';
                pidInput.value = '';
                pidInput.focus();
            }
        }
    }

    async function onScanSuccess(decodedText) {
        await submitNokartu(decodedText);
    }

    function setMode(nextMode) {
        mode = nextMode === 'pid' ? 'pid' : 'qr';
        const isQr = mode === 'qr';
        modeQrBtn.classList.toggle('active', isQr);
        modePidBtn.classList.toggle('active', !isQr);
        scannerWrap.classList.toggle('hidden', !isQr);
        pidWrap.classList.toggle('active', !isQr);
        if (isQr) {
            hintEl.textContent = 'Arahkan kamera ke QR Code';
            startScanner();
        } else {
            stopScanner();
            hintEl.textContent = 'Tempel kartu PID atau input manual.';
            setStatus('PID Ready', true);
            pidInput.focus();
        }
    }

    async function submitPidInput() {
        await submitNokartu(pidInput.value);
    }

    document.addEventListener('DOMContentLoaded', function() {
        startScanner();
        modeQrBtn.addEventListener('click', function(){ setMode('qr'); });
        modePidBtn.addEventListener('click', function(){ setMode('pid'); });
        pidSubmit.addEventListener('click', submitPidInput);
        pidInput.addEventListener('keydown', function(e){
            if (e.key === 'Enter') {
                e.preventDefault();
                submitPidInput();
            }
        });
        document.addEventListener('keydown', function(e){
            if (mode !== 'pid' || isPosting) return;
            const now = Date.now();
            if (now - lastPidKeyAt > 150) {
                pidBuffer = '';
            }
            lastPidKeyAt = now;
            if (e.key === 'Enter') {
                if (pidBuffer.trim()) {
                    pidInput.value = pidBuffer;
                    submitPidInput();
                    pidBuffer = '';
                }
                return;
            }
            if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                pidBuffer += e.key;
            }
        });
    });
</script>
</body>
</html>

