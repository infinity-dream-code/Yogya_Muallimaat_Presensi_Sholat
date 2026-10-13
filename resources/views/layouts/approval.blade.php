<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Approval Prestasi')</title>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('approval-theme');
                if (t !== 'dark' && t !== 'light') {
                    t = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        :root {
            --bg: #f2efe6;
            --surface: #fbfaf4;
            --surface-2: #eeeae0;
            --border: #d5cfc0;
            --t1: #1c2118;
            --t2: #5d6458;
            --t3: #8a9084;
            --accent: #1f6b3a;
            --accent-2: #16552d;
            --accent-soft: #e4efe6;
            --ok: #1f6b3a;
            --ok-bg: #dceee1;
            --danger: #9b2c2c;
            --danger-bg: #f3d9d6;
            --warn: #8a5a12;
            --warn-bg: #f3e6c8;
            --sidebar: #18241c;
            --sidebar-text: #d7ddd4;
            --sidebar-muted: #8fa089;
            --row-hover: #eef4ee;
            --shadow: 0 1px 0 rgba(28,33,24,.06);
            --sw: 248px;
            --rad: 6px;
        }
        html[data-theme="dark"] {
            --bg: #121512;
            --surface: #1b1f1b;
            --surface-2: #232823;
            --border: #323932;
            --t1: #e7ebe3;
            --t2: #a3aa9c;
            --t3: #7a8276;
            --accent: #6fbf86;
            --accent-2: #4e9a64;
            --accent-soft: #243328;
            --ok: #6fbf86;
            --ok-bg: #1e3326;
            --danger: #e07a72;
            --danger-bg: #3a2220;
            --warn: #d4a65a;
            --warn-bg: #3a2f1a;
            --sidebar: #0e130f;
            --sidebar-text: #d5dbd2;
            --sidebar-muted: #7d877a;
            --row-hover: #232b24;
            --shadow: none;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            font-family: "Source Sans 3", "Segoe UI", system-ui, sans-serif;
            background: var(--bg);
            color: var(--t1);
            letter-spacing: .01em;
        }
        a { color: inherit; }

        .sidebar {
            width: var(--sw); position: fixed; inset: 0 auto 0 0;
            background: var(--sidebar);
            color: var(--sidebar-text);
            display: flex; flex-direction: column; z-index: 40;
            overflow-y: auto;
            border-right: 1px solid rgba(255,255,255,.04);
        }
        .sb-brand { padding: 18px 16px 14px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,.06); }
        .sb-logo { width: 40px; height: 40px; border-radius: 4px; overflow: hidden; background: #fff; flex-shrink: 0; }
        .sb-logo img { width: 100%; height: 100%; object-fit: contain; }
        .sb-name { font-size: .9rem; font-weight: 700; color: #f4f7f2; }
        .sb-role { font-size: .74rem; color: var(--sidebar-muted); margin-top: 1px; }
        .sb-section { padding: 14px 12px 16px; flex: 1; }
        .sb-lbl { font-size: .68rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--sidebar-muted); padding: 0 8px; margin: 0 0 8px; }
        .sb-nav { list-style: none; }
        .sb-nav li { margin-bottom: 2px; }
        .sb-link {
            display: flex; align-items: center; gap: 10px; width: 100%;
            padding: 9px 10px; border-radius: 4px; border: 0; background: transparent;
            color: var(--sidebar-text); font: inherit; font-size: .88rem; font-weight: 500;
            text-decoration: none; cursor: pointer; text-align: left;
        }
        .sb-link i { width: 16px; text-align: center; color: var(--sidebar-muted); }
        .sb-link:hover { background: rgba(255,255,255,.06); }
        .sb-link.active { background: var(--accent); color: #fff; }
        .sb-link.active i { color: #fff; }
        .sb-overlay { display: none; position: fixed; inset: 0; background: rgba(10,14,11,.5); z-index: 39; }

        .main { margin-left: var(--sw); min-height: 100vh; display: flex; flex-direction: column; }
        .topbar {
            height: 52px; background: var(--surface); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 10px; padding: 0 18px;
            position: sticky; top: 0; z-index: 20;
        }
        .tb-burger { display: none; width: 34px; height: 34px; border: 1px solid var(--border); border-radius: 4px; background: var(--surface); color: var(--t1); cursor: pointer; }
        .tb-title { font-size: 1.02rem; font-weight: 700; flex: 1; }
        .scope-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 8px; border-radius: 4px; background: var(--surface-2);
            color: var(--t2); font-size: .74rem; font-weight: 600;
            border: 1px solid var(--border);
        }
        .theme-btn {
            width: 34px; height: 34px; border: 1px solid var(--border); border-radius: 4px;
            background: var(--surface); color: var(--t1); cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .theme-btn:hover { background: var(--surface-2); }
        html[data-theme="dark"] .theme-btn .fa-moon { display: none; }
        html[data-theme="light"] .theme-btn .fa-sun,
        html:not([data-theme]) .theme-btn .fa-sun { display: none; }
        html[data-theme="dark"] .theme-btn .fa-sun { display: inline; }

        .body { padding: 16px 18px 40px; }
        .panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--rad); box-shadow: var(--shadow); overflow: hidden; }
        .filters { padding: 10px 12px; overflow: visible; border-bottom: 1px solid var(--border); }
        .filters-grid { display: grid; grid-template-columns: 1.3fr .9fr .9fr .8fr .8fr auto; gap: 8px; align-items: end; }
        .field label { display: block; font-size: .68rem; font-weight: 700; color: var(--t2); margin-bottom: 4px; }
        .field input, .field select, .field textarea {
            width: 100%; border: 1px solid var(--border); border-radius: 4px; padding: 7px 9px;
            font: inherit; font-size: .84rem; background: var(--bg); color: var(--t1); outline: none; height: 34px;
        }
        .field textarea { height: auto; min-height: 72px; resize: vertical; }
        .field input:focus, .field select:focus, .field textarea:focus { border-color: var(--accent); background: var(--surface); }
        .tabs { display: flex; gap: 0; margin-bottom: 10px; border-bottom: 1px solid var(--border); }
        .chip {
            display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px;
            border: 0; background: transparent; color: var(--t2);
            text-decoration: none; font-size: .82rem; font-weight: 600;
            border-bottom: 2px solid transparent; margin-bottom: -1px;
        }
        .chip.active { color: var(--accent); border-bottom-color: var(--accent); }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            border: 0; border-radius: 4px; padding: 7px 11px; font: inherit; font-size: .8rem; font-weight: 650;
            cursor: pointer; text-decoration: none; white-space: nowrap; height: 34px;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        html[data-theme="dark"] .btn-primary { color: #102016; }
        .btn-ghost { background: var(--surface); color: var(--t1); border: 1px solid var(--border); }
        .btn-ok { background: var(--ok); color: #fff; }
        html[data-theme="dark"] .btn-ok { color: #102016; }
        .btn-danger { background: var(--danger); color: #fff; }
        html[data-theme="dark"] .btn-danger { color: #2a1210; }
        .btn-sm { padding: 4px 8px; font-size: .74rem; height: 28px; }

        .table-card { margin-top: 0; }
        .table-toolbar {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            padding: 8px 12px; border-bottom: 1px solid var(--border); background: var(--surface-2);
        }
        .count-pill {
            display: inline-flex; align-items: center; gap: 6px;
            color: var(--t2); font-size: .76rem; font-weight: 600;
        }
        .table-wrap { overflow: auto; max-height: calc(100vh - 220px); }
        table.data { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 920px; }
        table.data th, table.data td { padding: 8px 11px; text-align: left; border-bottom: 1px solid var(--border); font-size: .82rem; vertical-align: middle; }
        table.data th {
            font-size: .68rem; letter-spacing: .04em; text-transform: uppercase; color: var(--t2);
            background: var(--surface-2); font-weight: 700; position: sticky; top: 0; z-index: 2;
        }
        table.data th.sortable { cursor: pointer; user-select: none; white-space: nowrap; }
        table.data th.sortable:hover { color: var(--accent); }
        table.data tbody tr:hover td { background: var(--row-hover); }
        table.data tbody tr:last-child td { border-bottom: 0; }
        .group-row td {
            background: var(--surface-2) !important; color: var(--t1); font-weight: 700; font-size: .75rem;
            cursor: pointer; padding: 6px 11px; border-bottom: 1px solid var(--border);
        }
        .group-row td i { margin-right: 6px; width: 12px; font-size: .7rem; color: var(--t2); }
        .group-count {
            display: inline-flex; margin-left: 8px; padding: 0 6px; border-radius: 3px; height: 18px;
            align-items: center; background: var(--surface); color: var(--t2); font-weight: 700; font-size: .68rem;
            border: 1px solid var(--border);
        }
        table.data tr.group-row:hover td { background: var(--row-hover) !important; }
        .cell-user { display: flex; align-items: center; gap: 8px; }
        .avatar {
            width: 28px; height: 28px; border-radius: 3px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--accent-soft); color: var(--accent); font-size: .7rem; font-weight: 700;
        }
        .cell-main { font-weight: 650; }
        .cell-sub { color: var(--t2); font-size: .74rem; margin-top: 1px; }
        .badge { display: inline-flex; align-items: center; padding: 1px 7px; border-radius: 3px; font-size: .68rem; font-weight: 700; letter-spacing: .02em; }
        .badge.pending { background: var(--warn-bg); color: var(--warn); }
        .badge.approved { background: var(--ok-bg); color: var(--ok); }
        .badge.canceled { background: var(--danger-bg); color: var(--danger); }
        .badge.role-sa { background: var(--accent-soft); color: var(--accent); }
        .badge.role-ms { background: var(--surface-2); color: var(--t1); border: 1px solid var(--border); }
        table.data td form { display: inline; }
        .actions { display: flex; gap: 4px; flex-wrap: nowrap; }
        .empty { text-align: center; color: var(--t2); padding: 32px 16px; font-size: .86rem; }
        .empty i { font-size: 1.1rem; color: var(--t3); margin-bottom: 8px; }
        .link { color: var(--accent); font-weight: 650; text-decoration: none; }
        .link:hover { text-decoration: underline; }
        .mono { font-variant-numeric: tabular-nums; color: var(--t1); }

        .app-toastify { background: transparent !important; box-shadow: none !important; padding: 0 !important; }
        .app-toastify .toast-close {
            color: var(--t3) !important; opacity: .85; padding: 6px 8px 0 4px; font-weight: 400;
        }
        .app-toast {
            min-width: 240px; max-width: 360px; display: flex; gap: 8px; align-items: center;
            background: var(--surface); border-radius: 4px; padding: 10px 12px;
            box-shadow: 0 8px 24px rgba(18,21,18,.18); border: 1px solid var(--border);
        }
        .app-toast i { color: var(--ok); }
        .app-toast.is-err i { color: var(--danger); }
        .app-toast-msg { font-size: .84rem; color: var(--t1); line-height: 1.35; font-weight: 500; }
        .toastify { font-family: "Source Sans 3", "Segoe UI", system-ui, sans-serif; }
        .toastify.on { opacity: 1; }
        .confirm-text { color: var(--t2); font-size: .86rem; line-height: 1.5; }

        .modal-bg { display: none; position: fixed; inset: 0; background: rgba(12,16,12,.48); z-index: 50; align-items: center; justify-content: center; padding: 16px; }
        .modal-bg.open { display: flex; }
        .modal { width: 100%; max-width: 500px; background: var(--surface); border: 1px solid var(--border); border-radius: 6px; padding: 18px; }
        .modal h3 { font-size: 1rem; margin-bottom: 12px; }
        .modal-grid { display: grid; gap: 10px; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px; }

        .cat-block { border-bottom: 1px solid var(--border); }
        .cat-block:last-child { border-bottom: 0; }
        .cat-head, .lvl-head {
            display: flex; align-items: center; gap: 8px; padding: 10px 12px;
        }
        .cat-head { background: var(--surface-2); }
        .cat-kode {
            font-size: .72rem; font-weight: 700; min-width: 28px; text-align: center;
            border: 1px solid var(--border); background: var(--surface); padding: 2px 6px; border-radius: 3px;
        }
        .cat-title { font-weight: 700; flex: 1; }
        .lvl-block { padding: 0 12px 10px 28px; }
        .lvl-head { padding: 8px 0 6px; }
        .lvl-title { font-weight: 650; flex: 1; }
        table.poin-table { min-width: 0; }
        table.poin-table th, table.poin-table td { padding: 6px 8px; }

        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); transition: transform .2s ease; }
            .sidebar.open { transform: translateX(0); }
            .sb-overlay.open { display: block; }
            .main { margin-left: 0; }
            .tb-burger { display: inline-flex; align-items: center; justify-content: center; }
            .filters-grid { grid-template-columns: 1fr; }
            .body { padding: 12px 12px 32px; }
            .table-wrap { max-height: none; }
            .table-toolbar { flex-wrap: wrap; }
        }
    </style>
    @yield('head')
</head>
<body>
@php
    $navActive = $navActive ?? 'approval';
    $isSuperadmin = !empty($isSuperadmin);
    $roleLabel = (string) session('user.role', '-');
    $scopeCode01 = $scopeCode01 ?? '';
    $scopeSekolah = $scopeSekolah ?? '';
    $currentApp = (string) session('user.app', '');
@endphp
<aside class="sidebar" id="sidebar">
    <div class="sb-brand">
        <div class="sb-logo"><img src="{{ asset('logo.png') }}" alt="Logo"></div>
        <div>
            <div class="sb-name">{{ session('user.nama', session('user.username')) }}</div>
            <div class="sb-role">{{ $roleLabel }}</div>
        </div>
    </div>
    <div class="sb-section">
        <div class="sb-lbl">Menu</div>
        <ul class="sb-nav">
            @if($currentApp === 'catatan-kepribadian')
            <li>
                <a href="{{ route('catatan.kepribadian.index') }}" class="sb-link {{ $navActive === 'catatan' ? 'active' : '' }}">
                    <i class="fas fa-book"></i> Daftar catatan
                </a>
            </li>
            <li>
                <a href="{{ route('catatan.kepribadian.create') }}" class="sb-link {{ $navActive === 'catatan-tambah' ? 'active' : '' }}">
                    <i class="fas fa-plus"></i> Tambah catatan
                </a>
            </li>
            @if($isSuperadmin)
            <li>
                <a href="{{ route('catatan.admin.index') }}" class="sb-link {{ $navActive === 'admin' ? 'active' : '' }}">
                    <i class="fas fa-user-shield"></i> Kelola Admin
                </a>
            </li>
            @endif
            @elseif($currentApp === 'tahfid')
            @if(strtolower((string) session('user.role')) === 'siswa')
            <li>
                <a href="{{ route('tahfid.siswa.index') }}" class="sb-link {{ $navActive === 'siswa' ? 'active' : '' }}">
                    <i class="fas fa-quran"></i> Jadwal hafalan
                </a>
            </li>
            @else
            <li>
                <a href="{{ route('tahfid.jadwal.index') }}" class="sb-link {{ $navActive === 'jadwal' ? 'active' : '' }}">
                    <i class="fas fa-calendar-alt"></i> Jadwal unit
                </a>
            </li>
            <li>
                <a href="{{ route('tahfid.jadwal.create') }}" class="sb-link {{ $navActive === 'jadwal-tambah' ? 'active' : '' }}">
                    <i class="fas fa-plus"></i> Buat jadwal
                </a>
            </li>
            <li>
                <a href="{{ route('tahfid.percepatan.index') }}" class="sb-link {{ $navActive === 'percepatan' ? 'active' : '' }}">
                    <i class="fas fa-forward"></i> Percepatan
                </a>
            </li>
            <li>
                <a href="{{ route('tahfid.progress.index') }}" class="sb-link {{ $navActive === 'progress' ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list"></i> Progress siswi
                </a>
            </li>
            @if($isSuperadmin)
            <li>
                <a href="{{ route('tahfid.admin.index') }}" class="sb-link {{ $navActive === 'admin' ? 'active' : '' }}">
                    <i class="fas fa-user-shield"></i> Kelola Admin
                </a>
            </li>
            @endif
            @endif
            @else
            <li>
                <a href="{{ route('approval.prestasi.index') }}" class="sb-link {{ $navActive === 'approval' ? 'active' : '' }}">
                    <i class="fas fa-clipboard-check"></i> Approval Prestasi
                </a>
            </li>
            @if($isSuperadmin)
            <li>
                <a href="{{ route('approval.katalog.index') }}" class="sb-link {{ $navActive === 'katalog' ? 'active' : '' }}">
                    <i class="fas fa-list-ol"></i> Katalog Prestasi
                </a>
            </li>
            <li>
                <a href="{{ route('approval.admin.index') }}" class="sb-link {{ $navActive === 'admin' ? 'active' : '' }}">
                    <i class="fas fa-user-shield"></i> Kelola Admin
                </a>
            </li>
            @endif
            @endif
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sb-link"><i class="fas fa-right-from-bracket"></i> Logout</button>
                </form>
            </li>
        </ul>
    </div>
</aside>
<div class="sb-overlay" id="sbOverlay"></div>

<div class="main">
    <header class="topbar">
        <button class="tb-burger" id="sbToggle" type="button"><i class="fas fa-bars"></i></button>
        <div class="tb-title">@yield('heading')</div>
        <button type="button" class="theme-btn" id="themeToggle" title="Ubah tema" aria-label="Ubah tema">
            <i class="fas fa-moon"></i>
            <i class="fas fa-sun"></i>
        </button>
        <div class="scope-pill">
            <i class="fas fa-school"></i>
            @if(strtolower((string) session('user.role')) === 'siswa')
                {{ $scopeSekolah !== '' ? $scopeSekolah : 'Unit' }}{{ session('user.kelas') ? ' · '.session('user.kelas') : '' }}
            @elseif($isSuperadmin || $scopeCode01 === '')
                Semua sekolah
            @else
                {{ $scopeSekolah !== '' ? $scopeSekolah : 'Scope sekolah' }}
            @endif
        </div>
    </header>
    <div class="body">
        @yield('content')
    </div>
</div>
<div class="modal-bg" id="confirmModal">
    <div class="modal" style="max-width:420px">
        <h3 id="confirmTitle">Konfirmasi</h3>
        <p class="confirm-text" id="confirmText"></p>
        <div class="field" id="confirmNoteField" style="display:none;margin-top:10px">
            <label for="confirmNote">Catatan admin</label>
            <textarea id="confirmNote" maxlength="500" placeholder="Alasan ditolak"></textarea>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" id="confirmCancel">Batal</button>
            <button type="button" class="btn btn-primary" id="confirmOk">Ya</button>
        </div>
    </div>
</div>
<script>
    window.showToast = function (message, type) {
        if (!message) return;
        var ok = type === 'success';
        var node = document.createElement('div');
        node.className = 'app-toast ' + (ok ? 'is-ok' : 'is-err');
        node.innerHTML =
            '<i class="fas ' + (ok ? 'fa-check' : 'fa-exclamation-circle') + '"></i>' +
            '<div class="app-toast-msg"></div>';
        node.querySelector('.app-toast-msg').textContent = String(message);
        Toastify({
            node: node,
            duration: 2800,
            gravity: 'top',
            position: 'right',
            close: true,
            stopOnFocus: true,
            className: 'app-toastify'
        }).showToast();
    };

    (function () {
        var html = document.documentElement;
        var themeBtn = document.getElementById('themeToggle');
        if (themeBtn) {
            themeBtn.addEventListener('click', function () {
                var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-theme', next);
                try { localStorage.setItem('approval-theme', next); } catch (e) {}
            });
        }

        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sbOverlay');
        var toggle = document.getElementById('sbToggle');
        function closeSb() { sidebar.classList.remove('open'); overlay.classList.remove('open'); }
        if (toggle) toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open'); overlay.classList.toggle('open');
        });
        if (overlay) overlay.addEventListener('click', closeSb);

        @if(session('success'))
            showToast(@json(session('success')), 'success');
        @endif
        @if(session('error'))
            showToast(@json(session('error')), 'error');
        @endif
        @if($errors->any())
            showToast(@json($errors->first()), 'error');
        @endif
        @if(!empty($errorMessage))
            showToast(@json($errorMessage), 'error');
        @endif

        var confirmModal = document.getElementById('confirmModal');
        var confirmTitle = document.getElementById('confirmTitle');
        var confirmText = document.getElementById('confirmText');
        var confirmNoteField = document.getElementById('confirmNoteField');
        var confirmNote = document.getElementById('confirmNote');
        var confirmOk = document.getElementById('confirmOk');
        var confirmCancel = document.getElementById('confirmCancel');
        var pendingForm = null;
        function needConfirmNote(form) {
            return !!(form && form.getAttribute('data-note') === 'required');
        }
        function closeConfirm() {
            confirmModal.classList.remove('open');
            pendingForm = null;
            if (confirmNoteField) confirmNoteField.style.display = 'none';
            if (confirmNote) confirmNote.value = '';
        }
        window.askConfirm = function (title, text, form) {
            pendingForm = form || null;
            confirmTitle.textContent = title || 'Lanjutkan?';
            confirmText.textContent = text || '';
            if (confirmNoteField) {
                var showNote = needConfirmNote(form);
                confirmNoteField.style.display = showNote ? 'block' : 'none';
                if (confirmNote) confirmNote.value = '';
                if (showNote && confirmNote) setTimeout(function () { confirmNote.focus(); }, 50);
            }
            confirmModal.classList.add('open');
        };
        if (confirmOk) confirmOk.addEventListener('click', function () {
            var form = pendingForm;
            if (needConfirmNote(form)) {
                var note = (confirmNote && confirmNote.value || '').trim();
                if (note.length < 3) {
                    if (window.showToast) showToast('Catatan admin wajib diisi (minimal 3 karakter).', 'error');
                    if (confirmNote) confirmNote.focus();
                    return;
                }
                var noteInput = form.querySelector('.js-catatan-admin');
                if (noteInput) noteInput.value = note;
            }
            closeConfirm();
            if (form) form.submit();
        });
        if (confirmCancel) confirmCancel.addEventListener('click', closeConfirm);
        if (confirmModal) confirmModal.addEventListener('click', function (e) {
            if (e.target === confirmModal) closeConfirm();
        });
        document.querySelectorAll('form.js-confirm').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                window.askConfirm(
                    form.getAttribute('data-title') || 'Lanjutkan?',
                    form.getAttribute('data-text') || '',
                    form
                );
            });
        });
    })();
</script>
@yield('scripts')
</body>
</html>
