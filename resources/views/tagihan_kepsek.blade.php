<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagihan - Monitoring Kepsek</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg: #f4f2f8;
            --card: #ffffff;
            --shadow: 0 2px 12px rgba(15, 23, 42, 0.06);
            --accent: #6d28d9;
            --accent-light: #8b5cf6;
            --text: #1e1b2e;
            --muted: #64748b;
            --border: #e8ecf1;
            --green-bg: #dcfce7; --green-text: #166534;
            --red-bg: #fee2e2; --red-text: #991b1b;
            --yellow-bg: #fef9c3;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: var(--bg); }
        .app { min-height: 100vh; color: var(--text); }
        .drawer-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.5); opacity: 0; pointer-events: none; transition: opacity .25s; z-index: 50; }
        .drawer-backdrop.open { opacity: 1; pointer-events: auto; }
        .drawer { position: fixed; top: 0; left: 0; width: 280px; height: 100%; background: linear-gradient(180deg,#1e1b4b 0%,#0f0d1e 100%); color: #e2e8f0; transform: translateX(-100%); transition: transform .25s; padding: 24px 20px; display: flex; flex-direction: column; z-index: 51; }
        .drawer.open { transform: translateX(0); }
        .drawer-header { display: flex; align-items: center; margin-bottom: 20px; }
        .drawer-logo { width: 48px; height: 48px; border-radius: 12px; overflow: hidden; margin-right: 12px; }
        .drawer-logo img { width: 100%; height: 100%; object-fit: contain; }
        .drawer-user-name { font-size: .95rem; font-weight: 600; color: #f8fafc; }
        .drawer-user-role { font-size: .78rem; color: #94a3b8; }
        .drawer-divider { height: 1px; background: rgba(255,255,255,.1); margin: 16px 0; }
        .drawer-menu-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .12em; color: #64748b; margin-bottom: 10px; padding-left: 14px; font-weight: 600; }
        .drawer-menu { list-style: none; padding: 0; margin: 0; flex: 1; }
        .drawer-item { margin-bottom: 4px; }
        .drawer-link { display: flex; align-items: center; padding: 12px 14px; border-radius: 12px; color: #cbd5e1; text-decoration: none; font-size: .9rem; font-weight: 500; border: none; background: transparent; width: 100%; cursor: pointer; font-family: inherit; }
        .drawer-link span.icon { width: 24px; display: inline-flex; justify-content: center; margin-right: 12px; color: #94a3b8; }
        .drawer-link:hover, .drawer-link.active { background: linear-gradient(135deg,rgba(124,58,237,.3) 0%,rgba(109,40,217,.2) 100%); color: #e9d5ff; }
        .drawer-link.active span.icon { color: #c4b5fd; }
        .drawer-footer { font-size: .75rem; color: #64748b; margin-top: auto; padding-top: 16px; }
        .main { padding: 20px; }
        .header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .burger { width: 36px; height: 36px; border: none; border-radius: 10px; background: #fff; padding: 0; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; box-shadow: var(--shadow); }
        .burger span { display: block; width: 18px; height: 2.5px; border-radius: 2px; background: var(--accent); }
        .title { font-size: 1.4rem; font-weight: 700; margin: 0; }
        .filter-card { background: var(--card); border-radius: 14px; padding: 18px; margin-bottom: 16px; box-shadow: var(--shadow); border: 1px solid var(--border); }
        .filter-head { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; gap: 12px; }
        .filter-head h3 { margin: 0; font-size: 1rem; font-weight: 700; }
        .filter-head p { margin: 4px 0 0; font-size: .8rem; color: var(--muted); }
        .filter-toggle { border: none; background: none; color: var(--muted); font-size: .82rem; font-weight: 600; cursor: pointer; font-family: inherit; }
        .filter-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .field label { display: block; font-size: .72rem; font-weight: 700; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .04em; }
        .field select, .field input { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 10px; font-size: .88rem; font-family: inherit; background: #fff; }
        .field select:disabled { background: #f1f5f9; color: var(--muted); cursor: not-allowed; }
        .multiselect { position: relative; }
        .multiselect-trigger { width: 100%; text-align: left; padding: 10px 12px; border: 1px solid var(--border); border-radius: 10px; font-size: .88rem; font-family: inherit; background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .multiselect-trigger span.count { background: var(--accent); color: #fff; font-size: .68rem; font-weight: 700; border-radius: 999px; padding: 2px 8px; }
        .multiselect-panel { display: none; position: absolute; top: calc(100% + 6px); left: 0; right: 0; background: #fff; border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 8px 24px rgba(15,23,42,.12); z-index: 20; max-height: 240px; overflow-y: auto; padding: 8px; }
        .multiselect-panel.open { display: block; }
        .multiselect-item { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 8px; font-size: .85rem; cursor: pointer; }
        .multiselect-item:hover { background: #f5f3ff; }
        .btn-row { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; justify-content: flex-end; }
        .btn { border: none; border-radius: 10px; padding: 10px 16px; font-size: .86rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-family: inherit; text-decoration: none; color: #fff; }
        .btn-primary { background: var(--accent); }
        .btn-reset { background: #fff; color: var(--text); border: 1px solid var(--border); }
        .btn-excel { background: #0f766e; }
        .btn-pdf { background: #b91c1c; }
        .table-card { background: var(--card); border-radius: 14px; box-shadow: var(--shadow); border: 1px solid var(--border); overflow: hidden; }
        .table-toolbar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 14px 16px; border-bottom: 1px solid var(--border); background: #fafbfc; }
        .table-toolbar .info { font-size: .84rem; color: var(--muted); font-weight: 600; }
        .per-page { display: flex; align-items: center; gap: 8px; font-size: .84rem; color: var(--muted); }
        .per-page select { padding: 7px 10px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: .84rem; background: #fff; }
        .table-scroll { overflow-x: auto; position: relative; }
        .table-scroll.is-loading { pointer-events: none; }
        .table-scroll.is-loading::after { content: ''; position: absolute; inset: 0; background: rgba(255,255,255,.7); z-index: 1; }
        .table-scroll.is-loading .loader { position: absolute; inset: 0; display: flex !important; flex-direction: column; align-items: center; justify-content: center; z-index: 2; background: transparent; padding: 0; }
        table { width: 100%; border-collapse: collapse; min-width: 1080px; }
        thead { background: #fef9c3; }
        th { padding: 11px 12px; text-align: left; font-size: .74rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .03em; border-bottom: 2px solid var(--border); white-space: nowrap; }
        td { padding: 11px 12px; font-size: .84rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tbody tr.data-row:hover { background: #f5f3ff !important; }
        tbody tr.data-row.expanded { background: #f5f3ff !important; border-left: 3px solid var(--accent); }
        .col-no { width: 44px; text-align: center; color: var(--muted); font-weight: 600; }
        .col-jumlah { text-align: right; font-weight: 600; white-space: nowrap; }
        .col-action { width: 90px; text-align: center; }
        .nama-cell .nama { font-weight: 600; }
        .nama-cell .sekolah { display: block; font-size: .72rem; color: var(--muted); text-transform: uppercase; letter-spacing: .03em; margin-top: 2px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700; }
        .badge-lunas { background: var(--green-bg); color: var(--green-text); }
        .badge-belum { background: var(--red-bg); color: var(--red-text); }
        .btn-detail { border: none; border-radius: 8px; background: #eef2ff; color: var(--accent); font-weight: 600; font-size: .8rem; padding: 7px 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-family: inherit; }
        .btn-detail:hover { background: var(--accent); color: #fff; }
        .btn-mini-toggle { width: 26px; height: 26px; border: 1px solid #ddd6fe; border-radius: 7px; background: #f5f3ff; color: var(--accent); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: .72rem; }
        .btn-mini-toggle.open { background: var(--accent); color: #fff; transform: rotate(45deg); }
        tr.detail-row td { padding: 18px 22px 22px; background: linear-gradient(180deg, #f5f3ff 0%, #f8fafc 100%); border-bottom: 2px solid #ddd6fe; }
        .siswa-panel-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; }
        .siswa-panel-head .t { font-size: .9rem; font-weight: 700; }
        .inner-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .inner-table { width: 100%; border-collapse: collapse; font-size: .83rem; }
        .inner-table th { padding: 10px 14px; background: #f8fafc; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #64748b; text-align: left; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
        .inner-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .inner-table tbody tr:last-child td { border-bottom: none; }
        .inner-table tbody tr:hover { background: #f5f3ff; }
        tr.komponen-row td { padding: 14px 18px; background: #fafbff; }
        .komponen-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
        .komponen-table th { padding: 8px 12px; background: #f1f5f9; font-size: .66rem; font-weight: 700; text-transform: uppercase; color: #64748b; text-align: left; }
        .komponen-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; }
        .mini-pagination { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; font-size: .78rem; color: var(--muted); border-top: 1px solid var(--border); background: #fafbfc; }
        .mini-pagination .btns { display: flex; gap: 6px; }
        .mini-page-btn { border: 1px solid var(--border); background: #fff; border-radius: 7px; padding: 5px 10px; font-size: .78rem; font-weight: 600; cursor: pointer; font-family: inherit; }
        .mini-page-btn:disabled { opacity: .4; cursor: not-allowed; }
        .detail-loading, .komponen-loading { padding: 24px; color: var(--muted); font-size: .85rem; display: flex; align-items: center; gap: 10px; }
        .spinner-sm { width: 18px; height: 18px; border: 2.5px solid rgba(109,40,217,.15); border-top-color: var(--accent); border-radius: 50%; animation: spin .7s linear infinite; }
        .empty, .loader { text-align: center; padding: 48px 20px; color: var(--muted); }
        .spinner { width: 36px; height: 36px; border: 3px solid rgba(109,40,217,.15); border-top-color: var(--accent); border-radius: 50%; animation: spin .7s linear infinite; margin: 0 auto 12px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .pagination { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 12px 16px; border-top: 1px solid var(--border); background: #fafbfc; }
        .pagination-info { font-size: .84rem; color: var(--muted); font-weight: 500; }
        .pagination-btns { display: flex; gap: 8px; }
        .page-btn { border: 1px solid var(--border); background: #fff; color: var(--text); border-radius: 8px; padding: 8px 14px; font-size: .84rem; font-weight: 600; cursor: pointer; font-family: inherit; }
        .page-btn:hover:not(:disabled) { border-color: var(--accent); color: var(--accent); }
        .page-btn:disabled { opacity: .4; cursor: not-allowed; }
        .summary-bar { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 16px; }
        .summary-box { background: var(--card); border-radius: 14px; padding: 18px 20px; box-shadow: var(--shadow); border: 1px solid var(--border); }
        .summary-box .label { font-size: .78rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .03em; }
        .summary-box .value { font-size: 1.3rem; font-weight: 800; margin-top: 6px; }
        .summary-box.terbayar .value { color: var(--green-text); }
        .summary-box.piutang .value { color: var(--red-text); }
        .summary-box.tagihan .value { color: var(--accent); }
        @media (max-width: 900px) { .filter-grid { grid-template-columns: 1fr 1fr; } .summary-bar { grid-template-columns: 1fr; } }
        @media (max-width: 600px) { .filter-grid { grid-template-columns: 1fr; } .main { padding: 14px; } }
        @media (min-width: 960px) {
            .app { margin-left: 280px; }
            .drawer { transform: translateX(0); }
            .drawer-backdrop { display: none; }
            .burger { display: none; }
            .main { padding: 28px 32px; max-width: 1320px; }
        }
    </style>
</head>
<body>
<div class="app">
    <div class="main">
        <header class="header">
            <button class="burger" id="drawerToggle" type="button"><span></span><span></span><span></span></button>
            <h1 class="title">Tagihan</h1>
        </header>

        <div class="filter-card">
            <div class="filter-head">
                <div>
                    <h3>Filter</h3>
                    <p>Sesuaikan sekolah, kelas, dan tagihan.</p>
                </div>
                <button type="button" class="filter-toggle" id="filterToggle">Sembunyikan <i class="fas fa-chevron-up"></i></button>
            </div>
            <div id="filterBody">
                <div class="filter-grid">
                    <div class="field">
                        <label for="filterSekolah">Sekolah</label>
                        <select id="filterSekolah"><option value="">Semua sekolah</option></select>
                    </div>
                    <div class="field">
                        <label for="filterBta">Tahun Ajaran</label>
                        <select id="filterBta"><option value="">Semua tahun ajaran</option></select>
                    </div>
                    <div class="field">
                        <label for="filterKelas">Kelas / Program</label>
                        <select id="filterKelas" disabled><option value="">Pilih sekolah dulu</option></select>
                    </div>
                    <div class="field">
                        <label>Tagihan</label>
                        <div class="multiselect" id="tagihanMultiselect">
                            <button type="button" class="multiselect-trigger" id="tagihanTrigger">
                                <span id="tagihanTriggerLabel">Semua tagihan</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="multiselect-panel" id="tagihanPanel"></div>
                        </div>
                    </div>
                    <div class="field">
                        <label for="filterStatus">Status</label>
                        <select id="filterStatus">
                            <option value="">Semua status</option>
                            <option value="1">Lunas</option>
                            <option value="0">Belum Lunas</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="filterSearch">Cari nama</label>
                        <input type="text" id="filterSearch" placeholder="Nama siswa...">
                    </div>
                </div>
                <div class="btn-row">
                    <button type="button" class="btn btn-reset" id="btnReset">Reset</button>
                    <button type="button" class="btn btn-primary" id="btnFilter"><i class="fas fa-filter"></i> Terapkan</button>
                    <a href="#" class="btn btn-excel" id="btnExportExcel"><i class="fas fa-file-excel"></i> Excel</a>
                    <a href="#" class="btn btn-pdf" id="btnExportPdf"><i class="fas fa-file-pdf"></i> PDF</a>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-toolbar">
                <div class="info" id="tableInfo">Daftar Piutang per Siswa</div>
                <div class="per-page">
                    <label for="perPage">Tampilkan</label>
                    <select id="perPage">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>data / halaman</span>
                </div>
            </div>

            <div class="table-scroll">
                <div class="loader" id="tableLoader">
                    <div class="spinner"></div>
                    Memuat data siswa...
                </div>
                <table id="siswaTable" style="display:none;">
                    <thead>
                        <tr>
                            <th class="col-no">No</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Jenis Kelamin</th>
                            <th class="col-jumlah">Total Tagihan</th>
                            <th class="col-jumlah">Total Terbayar</th>
                            <th class="col-jumlah">Sisa Tagihan</th>
                            <th>Status</th>
                            <th class="col-action">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="siswaBody"></tbody>
                </table>
                <div class="empty" id="emptyState" style="display:none;">
                    <i class="fas fa-inbox" style="font-size:2.2rem;opacity:.35;display:block;margin-bottom:10px;"></i>
                    Tidak ada data siswa
                </div>
            </div>

            <div class="pagination" id="paginationBar" style="display:none;">
                <div class="pagination-info" id="paginationInfo"></div>
                <div class="pagination-btns">
                    <button type="button" class="page-btn" id="btnPrev"><i class="fas fa-chevron-left"></i> Sebelumnya</button>
                    <button type="button" class="page-btn active" id="btnPageNum" disabled>1</button>
                    <button type="button" class="page-btn" id="btnNext">Berikutnya <i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>

        <div class="summary-bar" id="summaryBar" style="display:none;">
            <div class="summary-box terbayar">
                <div class="label">Total Terbayar</div>
                <div class="value" id="sumTerbayar">-</div>
            </div>
            <div class="summary-box piutang">
                <div class="label">Total Piutang</div>
                <div class="value" id="sumPiutang">-</div>
            </div>
            <div class="summary-box tagihan">
                <div class="label">Total Tagihan</div>
                <div class="value" id="sumTagihan">-</div>
            </div>
        </div>
    </div>
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
        <li class="drawer-item"><a href="{{ route('dashboard.monitoring-kepsek') }}" class="drawer-link"><span class="icon"><i class="fas fa-house"></i></span><span>Dashboard</span></a></li>
        <li class="drawer-item"><a href="{{ route('kepsek.tagihan') }}" class="drawer-link active"><span class="icon"><i class="fas fa-file-invoice-dollar"></i></span><span>Tagihan</span></a></li>
        <li class="drawer-item">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="drawer-link"><span class="icon"><i class="fas fa-right-from-bracket"></i></span><span>Log Out</span></button>
            </form>
        </li>
    </ul>
    <div class="drawer-footer">App Ver : 2.0.0</div>
</aside>

<script>
    const routes = {
        filters: @json(route('kepsek.tagihan.filters')),
        siswa: @json(route('kepsek.tagihan.siswa')),
        summary: @json(route('kepsek.tagihan.summary')),
        exportExcel: @json(route('kepsek.tagihan.export-excel')),
        exportPdf: @json(route('kepsek.tagihan.export-pdf')),
    };
    const routeSiswaDetail = @json(route('kepsek.tagihan.siswa.detail', ['custid' => '__CUSTID__']));
    const routeKomponen = @json(route('kepsek.tagihan.komponen', ['custid' => '__CUSTID__', 'billcd' => '__BILLCD__']));

    let currentPage = 1;
    let hasMore = false;
    let selectedTagihan = [];
    let openStudentRow = null;

    function formatRupiah(val) {
        const n = Number(val) || 0;
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(n);
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function getFilterParams() {
        return {
            sekolah: document.getElementById('filterSekolah').value,
            bta: document.getElementById('filterBta').value,
            kelas: document.getElementById('filterKelas').value,
            status: document.getElementById('filterStatus').value,
            search: document.getElementById('filterSearch').value.trim(),
            tagihan: selectedTagihan.join(','),
        };
    }

    function getDataParams() {
        return { ...getFilterParams(), limit: document.getElementById('perPage').value, page: currentPage };
    }

    function buildQuery(params) {
        const q = new URLSearchParams();
        Object.entries(params).forEach(([k, v]) => { if (v !== '' && v != null) q.set(k, v); });
        return q.toString();
    }

    function updateExportLinks() {
        const q = buildQuery(getFilterParams());
        document.getElementById('btnExportExcel').href = routes.exportExcel + (q ? '?' + q : '');
        document.getElementById('btnExportPdf').href = routes.exportPdf + (q ? '?' + q : '');
    }

    async function loadFilterOptions(sekolah) {
        const q = buildQuery({ sekolah: sekolah || '' });
        const res = await fetch(routes.filters + (q ? '?' + q : ''), { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        if (!json.success) return json;
        return json;
    }

    function fillSelect(select, options, valueKey, labelKey, placeholder) {
        const current = select.value;
        select.innerHTML = `<option value="">${placeholder}</option>`;
        options.forEach(opt => {
            const el = document.createElement('option');
            el.value = opt[valueKey];
            el.textContent = opt[labelKey];
            select.appendChild(el);
        });
        if ([...select.options].some(o => o.value === current)) {
            select.value = current;
        }
    }

    function renderTagihanPanel(names) {
        const panel = document.getElementById('tagihanPanel');
        panel.innerHTML = '';
        names.forEach(name => {
            const id = 'tg_' + btoa(unescape(encodeURIComponent(name))).replace(/=/g, '');
            const row = document.createElement('label');
            row.className = 'multiselect-item';
            row.innerHTML = `<input type="checkbox" value="${escapeHtml(name)}" id="${id}"> <span>${escapeHtml(name)}</span>`;
            row.querySelector('input').addEventListener('change', e => {
                if (e.target.checked) {
                    if (!selectedTagihan.includes(name)) selectedTagihan.push(name);
                } else {
                    selectedTagihan = selectedTagihan.filter(v => v !== name);
                }
                updateTagihanTrigger();
            });
            panel.appendChild(row);
        });
    }

    function updateTagihanTrigger() {
        const label = document.getElementById('tagihanTriggerLabel');
        label.textContent = selectedTagihan.length ? `${selectedTagihan.length} tagihan dipilih` : 'Semua tagihan';
    }

    async function initFilters() {
        const json = await loadFilterOptions('');
        if (!json.success) return;
        fillSelect(document.getElementById('filterSekolah'), json.sekolah, 'value', 'label', 'Semua sekolah');
        fillSelect(document.getElementById('filterBta'), (json.bta || []).map(v => ({ value: v, label: v })), 'value', 'label', 'Semua tahun ajaran');
        renderTagihanPanel(json.tagihan || []);
    }

    document.getElementById('filterSekolah').addEventListener('change', async function () {
        const kelasSel = document.getElementById('filterKelas');
        if (!this.value) {
            kelasSel.disabled = true;
            kelasSel.innerHTML = '<option value="">Pilih sekolah dulu</option>';
            return;
        }
        kelasSel.disabled = true;
        kelasSel.innerHTML = '<option value="">Memuat...</option>';
        const json = await loadFilterOptions(this.value);
        if (!json.success) return;
        kelasSel.disabled = false;
        fillSelect(kelasSel, json.kelas || [], 'value', 'label', 'Semua kelas');
    });

    document.getElementById('tagihanTrigger').addEventListener('click', function (e) {
        e.stopPropagation();
        document.getElementById('tagihanPanel').classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
        if (!document.getElementById('tagihanMultiselect').contains(e.target)) {
            document.getElementById('tagihanPanel').classList.remove('open');
        }
    });

    document.getElementById('filterToggle').addEventListener('click', function () {
        const body = document.getElementById('filterBody');
        const show = body.style.display !== 'none';
        body.style.display = show ? 'none' : 'block';
        this.innerHTML = show ? 'Tampilkan <i class="fas fa-chevron-down"></i>' : 'Sembunyikan <i class="fas fa-chevron-up"></i>';
    });

    document.getElementById('btnReset').addEventListener('click', function () {
        document.getElementById('filterSekolah').value = '';
        document.getElementById('filterKelas').disabled = true;
        document.getElementById('filterKelas').innerHTML = '<option value="">Pilih sekolah dulu</option>';
        document.getElementById('filterBta').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterSearch').value = '';
        selectedTagihan = [];
        document.querySelectorAll('#tagihanPanel input[type="checkbox"]').forEach(c => c.checked = false);
        updateTagihanTrigger();
        loadSiswa(true);
    });

    function closeStudentDetail() {
        document.querySelectorAll('.detail-row').forEach(r => r.remove());
        document.querySelectorAll('.btn-detail.open').forEach(b => b.classList.remove('open'));
        document.querySelectorAll('.data-row.expanded').forEach(r => r.classList.remove('expanded'));
        openStudentRow = null;
    }

    async function toggleStudentDetail(btn, tr, custid) {
        const existing = tr.nextElementSibling;
        if (existing && existing.classList.contains('detail-row')) {
            closeStudentDetail();
            return;
        }
        closeStudentDetail();

        btn.classList.add('open');
        tr.classList.add('expanded');

        const detailTr = document.createElement('tr');
        detailTr.className = 'detail-row';
        detailTr.innerHTML = `<td colspan="9"><div class="detail-loading"><div class="spinner-sm"></div>Memuat tagihan siswa...</div></td>`;
        tr.after(detailTr);
        openStudentRow = detailTr;

        await loadTagihanSiswa(detailTr, custid, 1);
    }

    async function loadTagihanSiswa(detailTr, custid, page) {
        try {
            const url = routeSiswaDetail.replace('__CUSTID__', custid);
            const q = buildQuery({ bta: document.getElementById('filterBta').value, tagihan: selectedTagihan.join(','), page, limit: 20 });
            const res = await fetch(url + '?' + q, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();

            if (!json.success) {
                detailTr.innerHTML = `<td colspan="9"><div class="detail-loading" style="color:#991b1b;">${escapeHtml(json.message || 'Gagal memuat tagihan')}</div></td>`;
                return;
            }

            const rows = json.data || [];
            const pagination = json.pagination || {};

            const rowsHtml = rows.map(r => `
                <tr class="tagihan-row" data-billcd="${escapeHtml(r.billcd)}">
                    <td class="col-action">
                        <button type="button" class="btn-mini-toggle" data-custid="${escapeHtml(custid)}" data-billcd="${escapeHtml(r.billcd)}">
                            <i class="fas fa-plus"></i>
                        </button>
                    </td>
                    <td>${escapeHtml(r.nama_tagihan)}</td>
                    <td>${escapeHtml(r.bta)}</td>
                    <td class="col-jumlah">${formatRupiah(r.total_tagihan)}</td>
                    <td class="col-jumlah">${formatRupiah(r.total_terbayar)}</td>
                    <td class="col-jumlah">${formatRupiah(r.sisa_tagihan)}</td>
                    <td><span class="badge ${r.status_bayar ? 'badge-lunas' : 'badge-belum'}">${r.status_bayar ? 'Lunas' : 'Belum Lunas'}</span></td>
                </tr>
            `).join('');

            const emptyHtml = rows.length ? '' : `<tr><td colspan="7" style="text-align:center;padding:24px;color:#64748b;">Tidak ada tagihan</td></tr>`;

            detailTr.innerHTML = `
                <td colspan="9">
                    <div class="siswa-panel-head">
                        <div class="t"><i class="fas fa-file-invoice-dollar"></i> Tagihan Siswa</div>
                    </div>
                    <div class="inner-table-wrap">
                        <table class="inner-table">
                            <thead>
                                <tr>
                                    <th class="col-action"></th>
                                    <th>Nama Tagihan</th>
                                    <th>BTA</th>
                                    <th class="col-jumlah">Total Tagihan</th>
                                    <th class="col-jumlah">Total Bayar</th>
                                    <th class="col-jumlah">Kurang</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>${rowsHtml || emptyHtml}</tbody>
                        </table>
                        <div class="mini-pagination">
                            <span>Halaman ${pagination.page || 1} dari ${pagination.last_page || 1}</span>
                            <div class="btns">
                                <button type="button" class="mini-page-btn" data-dir="prev" ${((pagination.page || 1) <= 1) ? 'disabled' : ''}>Sebelumnya</button>
                                <button type="button" class="mini-page-btn" data-dir="next" ${!((pagination.page || 1) < (pagination.last_page || 1)) ? 'disabled' : ''}>Berikutnya</button>
                            </div>
                        </div>
                    </div>
                </td>
            `;

            detailTr.querySelectorAll('.btn-mini-toggle').forEach(b => {
                b.addEventListener('click', function () { toggleKomponen(this); });
            });
            detailTr.querySelectorAll('.mini-page-btn').forEach(b => {
                b.addEventListener('click', function () {
                    if (this.disabled) return;
                    const dir = this.dataset.dir;
                    const nextPage = dir === 'next' ? (pagination.page || 1) + 1 : (pagination.page || 1) - 1;
                    detailTr.innerHTML = `<td colspan="9"><div class="detail-loading"><div class="spinner-sm"></div>Memuat tagihan siswa...</div></td>`;
                    loadTagihanSiswa(detailTr, custid, nextPage);
                });
            });
        } catch (e) {
            detailTr.innerHTML = `<td colspan="9"><div class="detail-loading" style="color:#991b1b;">Gagal memuat tagihan — koneksi timeout.</div></td>`;
        }
    }

    async function toggleKomponen(btn) {
        const row = btn.closest('tr');
        const existing = row.nextElementSibling;
        if (existing && existing.classList.contains('komponen-row')) {
            existing.remove();
            btn.classList.remove('open');
            return;
        }

        row.parentElement.querySelectorAll('.komponen-row').forEach(r => r.remove());
        row.parentElement.querySelectorAll('.btn-mini-toggle.open').forEach(b => b.classList.remove('open'));

        btn.classList.add('open');
        const custid = btn.dataset.custid;
        const billcd = btn.dataset.billcd;

        const komponenTr = document.createElement('tr');
        komponenTr.className = 'komponen-row';
        komponenTr.innerHTML = `<td colspan="7"><div class="komponen-loading"><div class="spinner-sm"></div>Memuat detail komponen...</div></td>`;
        row.after(komponenTr);

        try {
            const url = routeKomponen.replace('__CUSTID__', custid).replace('__BILLCD__', billcd);
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();

            if (!json.success || !(json.data || []).length) {
                komponenTr.innerHTML = `<td colspan="7"><div class="komponen-loading" style="color:#991b1b;">${escapeHtml(json.message || 'Rincian tidak ditemukan')}</div></td>`;
                return;
            }

            const rows = json.data.map(d => `
                <tr>
                    <td>${escapeHtml(d.kode_akun)}</td>
                    <td>${escapeHtml(d.nama_akun)}</td>
                    <td>${escapeHtml(d.bta)}</td>
                    <td class="col-jumlah">${formatRupiah(d.jumlah)}</td>
                    <td><span class="badge ${d.status_bayar ? 'badge-lunas' : 'badge-belum'}">${d.status_bayar ? 'Lunas' : 'Belum Lunas'}</span></td>
                </tr>
            `).join('');

            komponenTr.innerHTML = `
                <td colspan="7">
                    <table class="komponen-table">
                        <thead>
                            <tr><th>Kode Akun</th><th>Nama Akun</th><th>BTA</th><th class="col-jumlah">Jumlah</th><th>Status</th></tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </td>
            `;
        } catch (e) {
            komponenTr.innerHTML = `<td colspan="7"><div class="komponen-loading" style="color:#991b1b;">Gagal memuat komponen — koneksi timeout.</div></td>`;
        }
    }

    function updatePagination(pagination, rowCount) {
        const bar = document.getElementById('paginationBar');
        const info = document.getElementById('paginationInfo');
        const tableInfo = document.getElementById('tableInfo');
        const btnPrev = document.getElementById('btnPrev');
        const btnNext = document.getElementById('btnNext');
        const btnPageNum = document.getElementById('btnPageNum');

        if (!pagination || rowCount === 0) {
            bar.style.display = 'none';
            tableInfo.textContent = 'Tidak ada data';
            return;
        }

        hasMore = (pagination.page || 1) < (pagination.last_page || 1);
        tableInfo.textContent = `${pagination.total || 0} siswa`;
        info.textContent = `Menampilkan ${pagination.from}–${pagination.to} dari ${pagination.total} · Halaman ${pagination.page} dari ${pagination.last_page}`;
        btnPageNum.textContent = pagination.page;
        btnPrev.disabled = (pagination.page || 1) <= 1;
        btnNext.disabled = !hasMore;
        bar.style.display = 'flex';
    }

    async function loadSummary() {
        const box = document.getElementById('summaryBar');
        box.style.display = 'grid';
        document.getElementById('sumTerbayar').textContent = '...';
        document.getElementById('sumPiutang').textContent = '...';
        document.getElementById('sumTagihan').textContent = '...';
        try {
            const q = buildQuery(getFilterParams());
            const res = await fetch(routes.summary + (q ? '?' + q : ''), { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            if (!json.success) return;
            document.getElementById('sumTerbayar').textContent = formatRupiah(json.total_terbayar);
            document.getElementById('sumPiutang').textContent = formatRupiah(json.total_piutang);
            document.getElementById('sumTagihan').textContent = formatRupiah(json.total_tagihan);
        } catch (e) {
            document.getElementById('sumTerbayar').textContent = '-';
            document.getElementById('sumPiutang').textContent = '-';
            document.getElementById('sumTagihan').textContent = '-';
        }
    }

    async function loadSiswa(resetPage = false) {
        if (resetPage) {
            currentPage = 1;
            closeStudentDetail();
        }

        const loader = document.getElementById('tableLoader');
        const table = document.getElementById('siswaTable');
        const empty = document.getElementById('emptyState');
        const tbody = document.getElementById('siswaBody');
        const paginationBar = document.getElementById('paginationBar');
        const tableScroll = document.querySelector('.table-scroll');
        const softReload = !resetPage && table.style.display === 'table';

        if (softReload) {
            tableScroll.classList.add('is-loading');
        } else {
            loader.style.display = 'block';
            table.style.display = 'none';
            empty.style.display = 'none';
            paginationBar.style.display = 'none';
            tbody.innerHTML = '';
        }
        updateExportLinks();

        try {
            const q = buildQuery(getDataParams());
            const res = await fetch(routes.siswa + '?' + q, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            loader.style.display = 'none';
            tableScroll.classList.remove('is-loading');

            if (!json.success) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: json.message || 'Gagal memuat data', confirmButtonColor: '#6d28d9' });
                if (!softReload) empty.style.display = 'block';
                return;
            }

            const rows = json.data || [];
            if (!rows.length) {
                table.style.display = 'none';
                empty.style.display = 'block';
                tbody.innerHTML = '';
                updatePagination(json.pagination, 0);
                return;
            }

            tbody.innerHTML = '';
            const startNo = ((json.pagination?.page || 1) - 1) * (json.pagination?.limit || 10) + 1;

            rows.forEach((row, i) => {
                const paid = Number(row.status_bayar) === 1;
                const tr = document.createElement('tr');
                tr.className = 'data-row';
                tr.innerHTML = `
                    <td class="col-no">${startNo + i}</td>
                    <td class="nama-cell">
                        <span class="nama">${escapeHtml(row.nama)}</span>
                        <span class="sekolah">${escapeHtml(row.sekolah || '-')}</span>
                    </td>
                    <td>${escapeHtml(row.kelas || '-')}</td>
                    <td>${escapeHtml(row.gender || '-')}</td>
                    <td class="col-jumlah">${formatRupiah(row.total_tagihan)}</td>
                    <td class="col-jumlah">${formatRupiah(row.total_terbayar)}</td>
                    <td class="col-jumlah">${formatRupiah(row.sisa_tagihan)}</td>
                    <td><span class="badge ${paid ? 'badge-lunas' : 'badge-belum'}">${paid ? 'Lunas' : 'Belum Lunas'}</span></td>
                    <td class="col-action"><button type="button" class="btn-detail"><i class="fas fa-eye"></i> Detail</button></td>
                `;
                tbody.appendChild(tr);
                tr.querySelector('.btn-detail').addEventListener('click', function () {
                    toggleStudentDetail(this, tr, row.custid);
                });
            });

            table.style.display = 'table';
            empty.style.display = 'none';
            updatePagination(json.pagination, rows.length);
            loadSummary();
        } catch (e) {
            loader.style.display = 'none';
            tableScroll.classList.remove('is-loading');
            if (!softReload) empty.style.display = 'block';
            Swal.fire({ icon: 'error', title: 'Error', text: 'Tidak dapat terhubung ke server', confirmButtonColor: '#6d28d9' });
        }
    }

    document.getElementById('btnFilter').addEventListener('click', () => loadSiswa(true));
    document.getElementById('filterSearch').addEventListener('keydown', e => { if (e.key === 'Enter') loadSiswa(true); });
    document.getElementById('perPage').addEventListener('change', () => loadSiswa(true));
    document.getElementById('btnPrev').addEventListener('click', () => { if (currentPage > 1) { currentPage--; loadSiswa(); } });
    document.getElementById('btnNext').addEventListener('click', () => { if (hasMore) { currentPage++; loadSiswa(); } });

    const toggleBtn = document.getElementById('drawerToggle');
    const backdrop = document.getElementById('drawerBackdrop');
    const drawer = document.getElementById('drawer');
    toggleBtn.addEventListener('click', () => {
        const open = drawer.classList.toggle('open');
        backdrop.classList.toggle('open', open);
    });
    backdrop.addEventListener('click', () => { drawer.classList.remove('open'); backdrop.classList.remove('open'); });

    initFilters();
    loadSiswa();
</script>
@if (session('error'))
<script>Swal.fire({ icon: 'warning', title: 'Perhatian', text: @json(session('error')), confirmButtonColor: '#6d28d9' });</script>
@endif
</body>
</html>