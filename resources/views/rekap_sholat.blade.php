<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Sholat</title>
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
            --green-bg: #d1fae5;
            --green-text: #065f46;
            --yellow-bg: #fef3c7;
            --yellow-text: #92400e;
            --red-bg: #fee2e2;
            --red-text: #991b1b;
            --blue-bg: #dbeafe;
            --blue-text: #1e40af;
            --pink-bg: #fce7f3;
            --pink-text: #9d174d;
            --gray-bg: #f1f5f9;
            --gray-text: #64748b;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: var(--bg-main); display: flex; justify-content: center; }
        .app { width: 100%; max-width: 600px; min-height: 100vh; color: var(--text); position: relative; }
        .drawer-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); opacity: 0; pointer-events: none; transition: opacity 0.25s; backdrop-filter: blur(2px); z-index: 50; }
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
        .burger span { display: block; width: 18px; height: 2.5px; border-radius: 2px; background: var(--accent); }
        .title { font-size: 1.35rem; font-weight: 700; color: var(--text); letter-spacing: -0.02em; flex: 1; }
        .btn-refresh { width: 40px; height: 40px; border: none; border-radius: 10px; background: rgba(109, 40, 217, 0.1); color: var(--accent); cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .btn-refresh:hover { background: rgba(109, 40, 217, 0.2); }

        .content { padding: 0 20px 32px; }
        .filter-card { background: var(--card-bg); border-radius: var(--card-radius); padding: 18px 20px; margin-bottom: 16px; box-shadow: var(--card-shadow); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; }
        .filter-left { display: flex; flex-direction: column; gap: 2px; }
        .date-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); }
        .date-value { font-size: 1.1rem; font-weight: 700; color: var(--text); }
        .btn-date { border: none; border-radius: 12px; padding: 12px 18px; background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%); color: #fff; display: inline-flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 600; cursor: pointer; box-shadow: 0 4px 14px rgba(109, 40, 217, 0.35); }
        .btn-date:hover { transform: translateY(-1px); }
        .btn-date i { font-size: 1rem; opacity: 0.9; }

        .search-wrap { margin-bottom: 16px; position: relative; }
        .search-input { width: 100%; padding: 14px 18px 14px 44px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 0.95rem; font-family: inherit; background: var(--card-bg); box-shadow: var(--card-shadow); }
        .search-input::placeholder { color: var(--text-muted); }
        .search-wrap i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 1rem; }

        .unit-wrap { margin-bottom: 16px; }
        .unit-label { font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; }
        .unit-select { width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 0.95rem; font-family: inherit; background: var(--card-bg); }

        .section-title { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); margin-bottom: 12px; }
        .list { display: flex; flex-direction: column; gap: 16px; }
        .card { background: var(--card-bg); border-radius: var(--card-radius); padding: 20px; box-shadow: var(--card-shadow); border: 1px solid rgba(109, 40, 217, 0.06); }
        .card-rekap { background: linear-gradient(135deg, #faf5ff 0%, #f5f3ff 100%); }
        .card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
        .card-title { font-size: 1rem; font-weight: 700; color: var(--text); line-height: 1.35; }
        .card-sub { font-size: 0.82rem; color: var(--text-muted); margin-top: 4px; }
        .chip-date { font-size: 0.75rem; font-weight: 600; padding: 6px 12px; border-radius: 10px; background: var(--gray-bg); color: var(--text-muted); white-space: nowrap; }

        .badge-wrap { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
        .badge { display: inline-flex; padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 600; }
        .badge-total { background: var(--gray-bg); color: var(--gray-text); }
        .badge-sholat { background: var(--green-bg); color: var(--green-text); }
        .badge-sakit { background: var(--blue-bg); color: var(--blue-text); }
        .badge-izin { background: var(--yellow-bg); color: var(--yellow-text); }
        .badge-alpa { background: var(--red-bg); color: var(--red-text); }
        .badge-haid { background: var(--pink-bg); color: var(--pink-text); }
        .badge-belum { background: var(--gray-bg); color: var(--gray-text); }

        .sholat-section { margin-top: 14px; }
        .sholat-section-title { font-size: 0.85rem; font-weight: 700; color: var(--text); margin-bottom: 8px; }
        .sholat-section .badge-wrap { margin-bottom: 8px; }

        .empty { text-align: center; padding: 48px 24px; font-size: 0.95rem; color: var(--text-muted); background: var(--card-bg); border-radius: var(--card-radius); box-shadow: var(--card-shadow); }
        .empty i { font-size: 2.5rem; opacity: 0.4; margin-bottom: 12px; display: block; }
        .error { margin-bottom: 20px; padding: 14px 16px; font-size: 0.9rem; border-radius: 12px; background: var(--red-bg); color: #991b1b; display: flex; align-items: center; gap: 10px; }

        .content-wrap { position: relative; }
        .list-wrap { position: relative; min-height: 160px; }
        .page-loader { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: var(--bg-main); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 5; transition: opacity 0.2s ease; border-radius: var(--card-radius); }
        .page-loader.hidden { opacity: 0; visibility: hidden; pointer-events: none; }
        .page-loader .spinner { width: 48px; height: 48px; border: 4px solid rgba(109, 40, 217, 0.2); border-top-color: var(--accent); border-radius: 50%; animation: spin 0.8s linear infinite; }
        .page-loader .text { margin-top: 16px; font-size: 0.9rem; color: var(--text-muted); }
        @keyframes spin { to { transform: rotate(360deg); } }

        .input-month { padding: 12px 18px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 0.9rem; font-weight: 600; font-family: inherit; background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%); color: #fff; cursor: pointer; box-shadow: 0 4px 14px rgba(109, 40, 217, 0.35); }
        .btn-load-more { width: 100%; padding: 14px; border: 2px dashed #e2e8f0; border-radius: 12px; background: #fafafa; color: var(--accent); font-size: 0.9rem; font-weight: 600; cursor: pointer; }
        .btn-load-more:hover { background: rgba(109, 40, 217, 0.06); border-color: var(--accent); }
        .load-more-wrap { margin-top: 8px; }
        .input-month::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; opacity: 0.9; }

        @media (min-width: 960px) {
            body { justify-content: flex-start; }
            .app { max-width: 1024px; margin-left: 280px; }
            .content { max-width: 560px; margin: 48px auto 24px; margin-left: max(0, calc(50vw - 560px)); padding: 0 24px 48px; }
            .drawer { position: fixed; transform: translateX(0); }
            .drawer-backdrop:not(.modal-open) { display: none; }
        }
    </style>
</head>
<body>
<div class="app">
    <div class="header">
        <button class="burger" id="drawerToggle" type="button" aria-label="Menu"><span></span><span></span><span></span></button>
        <div class="title">Rekap Sholat</div>
        <a href="{{ route('presensi.rekap-sholat', ['bulan' => $bulan]) }}" class="btn-refresh" title="Refresh"><i class="fas fa-rotate-right"></i></a>
    </div>

    <div class="content content-wrap">

        <form method="GET" action="{{ route('presensi.rekap-sholat') }}" id="filterForm">
            <div class="filter-card">
                <div class="filter-left">
                    <span class="date-label">Bulan &amp; Tahun</span>
                    <span class="date-value" id="bulanDisplay">{{ $bulan ?: 'Pilih bulan' }}</span>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
                    <input type="month" name="bulan" id="bulanPicker" value="{{ $bulan }}" class="input-month" title="Pilih bulan dan tahun">
                    <a href="#"
                       id="btnExportExcel"
                       data-export-base="{{ route('presensi.rekap-sholat.export-excel') }}"
                       class="btn-date"
                       style="background:#0f766e;box-shadow:0 4px 14px rgba(15,118,110,0.35);"
                       title="Export Excel untuk bulan terpilih">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                </div>
            </div>
        </form>

        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" class="search-input" id="searchInput" placeholder="Cari nama / NIS" autocomplete="off">
        </div>

        <div class="unit-wrap">
            <label class="unit-label" for="unitSelect">Unit</label>
            <select class="unit-select" id="unitSelect">
                <option value="">Semua unit</option>
            </select>
        </div>

        <div class="section-title">Rekap Siswa</div>
        <div class="list-wrap">
            <div class="page-loader {{ $bulan ? '' : 'hidden' }}" id="pageLoader">
                <div class="spinner"></div>
                <span class="text">Memuat data...</span>
            </div>
            <div class="list" id="rekapList"></div>
        </div>
    </div>
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
        <li class="drawer-item"><a href="{{ route('presensi.rekap-sholat') }}" class="drawer-link active"><span class="icon"><i class="fas fa-chart-simple"></i></span><span>Rekap Sholat</span></a></li>
        <li class="drawer-item"><a href="{{ route('presensi.account.ganti-password') }}" class="drawer-link"><span class="icon"><i class="fas fa-gear"></i></span><span>Account Controls</span></a></li>
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
(function () {
    var currentBulan = '{{ $bulan }}';
    var dataUrl = '{{ route("presensi.rekap-sholat.data") }}';

    function showLoader() {
        var loader = document.getElementById('pageLoader');
        if (loader) loader.classList.remove('hidden');
    }
    function hideLoader() {
        var loader = document.getElementById('pageLoader');
        if (loader) loader.classList.add('hidden');
    }
    function updateExportLinks() {
        var bulan = currentBulan || (document.getElementById('bulanPicker') || {}).value || '';
        var btnExcel = document.getElementById('btnExportExcel');
        var btnPdf   = document.getElementById('btnExportPdf');
        if (!btnExcel || !btnPdf) return;
        var baseExcel = btnExcel.getAttribute('data-export-base') || '';
        var basePdf   = btnPdf.getAttribute('data-export-base') || '';
        if (bulan && baseExcel && basePdf) {
            btnExcel.href = baseExcel + '?bulan=' + encodeURIComponent(bulan);
            btnPdf.href   = basePdf + '?bulan=' + encodeURIComponent(bulan);
            btnExcel.style.pointerEvents = 'auto';
            btnPdf.style.pointerEvents   = 'auto';
            btnExcel.style.opacity = '1';
            btnPdf.style.opacity   = '1';
        } else {
            btnExcel.href = '#';
            btnPdf.href   = '#';
            btnExcel.style.pointerEvents = 'none';
            btnPdf.style.pointerEvents   = 'none';
            btnExcel.style.opacity = '0.5';
            btnPdf.style.opacity   = '0.5';
        }
    }

    function loadData(bulan, page, append, search) {
        page = page || 1;
        append = !!append;
        search = (typeof search !== 'undefined' ? search : (document.getElementById('searchInput') || {}).value || '').trim();
        if (!append) showLoader();
        var url = dataUrl + '?bulan=' + encodeURIComponent(bulan) + '&page=' + page + '&per_page=50';
        if (search) url += '&search=' + encodeURIComponent(search);
        fetch(url)
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var list = document.getElementById('rekapList');
                if (!list) return;
                if (append) {
                    var tmp = document.createElement('div');
                    tmp.innerHTML = html;
                    var cards = tmp.querySelectorAll('.card-rekap-student');
                    var loadMore = tmp.querySelector('.load-more-wrap');
                    cards.forEach(function (c) { list.appendChild(c.cloneNode(true)); });
                    var oldLm = list.querySelector('.load-more-wrap');
                    if (oldLm) oldLm.remove();
                    if (loadMore) list.appendChild(loadMore.cloneNode(true));
                } else {
                    list.innerHTML = html;
                }
                currentBulan = bulan;
                if (!append) {
                    document.getElementById('bulanDisplay').textContent = bulan;
                    document.getElementById('bulanPicker').value = bulan;
                }
                updateExportLinks();
                history.replaceState(null, '', '?bulan=' + encodeURIComponent(bulan));
                updateUnitOptions();
            })
            .catch(function () {
                var list = document.getElementById('rekapList');
                if (append) {
                    var btn = list && list.querySelector('.btn-load-more');
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-chevron-down"></i> Muat lebih banyak'; }
                } else if (list) {
                    list.innerHTML = '<div class="error"><i class="fas fa-circle-exclamation"></i>Gagal memuat data. Silakan refresh halaman.</div>';
                }
            })
            .finally(function () { if (!append) hideLoader(); });
    }
    function updateUnitOptions() {
        var units = new Set();
        document.querySelectorAll('.card-rekap-student[data-unit]').forEach(function (c) {
            var u = (c.dataset.unit || '').trim();
            if (u) units.add(u);
        });
        var sel = document.getElementById('unitSelect');
        if (!sel) return;
        var idx = sel.selectedIndex;
        var opts = sel.querySelectorAll('option');
        for (var i = opts.length - 1; i >= 1; i--) opts[i].remove();
        var arr = Array.from(units).sort();
        arr.forEach(function (u) {
            var opt = document.createElement('option');
            opt.value = u;
            opt.textContent = u;
            sel.appendChild(opt);
        });
        sel.selectedIndex = 0;
    }
    function doFilter() {
        var unit = (document.getElementById('unitSelect') || {}).value || '';
        var cards = document.querySelectorAll('.card-rekap-student');
        for (var i = 0; i < cards.length; i++) {
            var card = cards[i];
            card.style.display = (unit === '' || (card.dataset.unit || '') === unit) ? '' : 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var picker = document.getElementById('bulanPicker');
        if (picker) {
            picker.addEventListener('change', function () {
                var b = this.value;
                if (b) {
                    currentBulan = b;
                    loadData(b);
                }
            });
        }
        if (currentBulan) {
            loadData(currentBulan);
        } else {
            hideLoader();
            var list = document.getElementById('rekapList');
            if (list) list.innerHTML = '<div class="empty"><i class="fas fa-calendar-days"></i>Pilih bulan dan tahun di atas untuk melihat rekap sholat.</div>';
        }
        updateExportLinks();

        var refreshBtn = document.querySelector('.btn-refresh');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function (e) {
                e.preventDefault();
                loadData(currentBulan);
            });
            refreshBtn.href = '#';
        }

        var searchInput = document.getElementById('searchInput');
        var unitSelect = document.getElementById('unitSelect');
        if (searchInput) {
            var searchTimeout;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    if (currentBulan) loadData(currentBulan, 1, false);
                }, 300);
            });
        }
        if (unitSelect) unitSelect.addEventListener('change', doFilter);

        var listWrap = document.querySelector('.list-wrap');
        if (listWrap) {
            listWrap.addEventListener('click', function (e) {
                var btn = e.target.closest('.btn-load-more');
                if (btn && !btn.disabled) {
                    var wrap = btn.closest('.load-more-wrap');
                    if (wrap) {
                        var nextPage = parseInt(wrap.dataset.page || '1', 10) + 1;
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...';
                        loadData(currentBulan, nextPage, true);
                    }
                }
            });
        }

        var toggleBtn = document.getElementById('drawerToggle');
        var backdrop = document.getElementById('drawerBackdrop');
        var drawer = document.getElementById('drawer');
        function closeDrawer() { drawer.classList.remove('open'); backdrop.classList.remove('open'); }
        toggleBtn.addEventListener('click', function () {
            if (drawer.classList.contains('open')) closeDrawer();
            else { drawer.classList.add('open'); backdrop.classList.add('open'); }
        });
        backdrop.addEventListener('click', closeDrawer);
    });
})();
</script>
</body>
</html>
