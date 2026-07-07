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
        .filter-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .field label { display: block; font-size: .72rem; font-weight: 700; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .04em; }
        .field select, .field input { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 10px; font-size: .88rem; font-family: inherit; background: #fff; }
        .btn-row { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
        .btn { border: none; border-radius: 10px; padding: 10px 16px; font-size: .86rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-family: inherit; text-decoration: none; color: #fff; }
        .btn-primary { background: var(--accent); }
        .btn-excel { background: #0f766e; }
        .btn-pdf { background: #b91c1c; }
        .table-card { background: var(--card); border-radius: 14px; box-shadow: var(--shadow); border: 1px solid var(--border); overflow: hidden; }
        .table-toolbar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 14px 16px; border-bottom: 1px solid var(--border); background: #fafbfc; }
        .table-toolbar .info { font-size: .84rem; color: var(--muted); font-weight: 500; }
        .per-page { display: flex; align-items: center; gap: 8px; font-size: .84rem; color: var(--muted); }
        .per-page select { padding: 7px 10px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: .84rem; background: #fff; }
        .table-scroll { overflow-x: auto; position: relative; }
        .table-scroll.is-loading { pointer-events: none; }
        .table-scroll.is-loading::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,.7);
            z-index: 1;
        }
        .table-scroll.is-loading .loader {
            position: absolute;
            inset: 0;
            display: flex !important;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 2;
            background: transparent;
            padding: 0;
        }
        table { width: 100%; border-collapse: collapse; min-width: 1140px; }
        thead { background: #f1f5f9; }
        th { padding: 11px 12px; text-align: left; font-size: .78rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .03em; border-bottom: 2px solid var(--border); white-space: nowrap; }
        td { padding: 11px 12px; font-size: .84rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tbody tr.data-row:nth-child(4n+1) { background: #fff; }
        tbody tr.data-row:nth-child(4n+3) { background: #fafbfd; }
        tbody tr.data-row:hover { background: #f5f3ff !important; }
        tbody tr.data-row.expanded { background: #f5f3ff !important; border-left: 3px solid var(--accent); }
        th.sortable { cursor: pointer; user-select: none; white-space: nowrap; }
        th.sortable:hover { color: var(--accent); }
        th.sortable .sort-icon { margin-left: 4px; font-size: .7rem; opacity: .55; }
        th.sortable.active { color: var(--accent); }
        th.sortable.active .sort-icon { opacity: 1; }
        .col-no { width: 44px; text-align: center; color: var(--muted); font-weight: 600; }
        .col-jumlah { text-align: right; font-weight: 600; white-space: nowrap; }
        .col-action { width: 44px; text-align: center; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700; }
        .badge-lunas { background: var(--green-bg); color: var(--green-text); }
        .badge-belum { background: var(--red-bg); color: var(--red-text); }
        .btn-toggle { width: 28px; height: 28px; border: 1px solid #ddd6fe; border-radius: 8px; background: #f5f3ff; color: var(--accent); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all .15s; }
        .btn-toggle:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
        .btn-toggle.open { background: var(--accent); color: #fff; border-color: var(--accent); transform: rotate(45deg); }
        tr.detail-row td { padding: 20px 28px 24px; background: linear-gradient(180deg, #f5f3ff 0%, #f8fafc 100%); border-bottom: 2px solid #ddd6fe; }
        .detail-panel { padding: 8px 12px 12px 20px; margin: 0; }
        .detail-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; padding: 0 12px; }
        .detail-header-left { display: flex; align-items: center; gap: 12px; }
        .detail-icon { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0; box-shadow: 0 4px 12px rgba(109,40,217,.25); }
        .detail-title { font-size: .95rem; font-weight: 700; color: var(--text); margin: 0; }
        .detail-subtitle { font-size: .78rem; color: var(--muted); margin-top: 2px; }
        .detail-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; background: #fff; border: 1px solid #ddd6fe; font-size: .78rem; font-weight: 600; color: var(--accent); }
        .detail-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 16px rgba(15,23,42,.06); width: 100%; padding: 16px 24px 20px; }
        .detail-table-scroll { overflow-x: auto; padding: 0 8px; }
        .detail-table { width: 100%; border-collapse: collapse; font-size: .88rem; min-width: 520px; }
        .detail-table th { padding: 14px 20px; background: #f8fafc; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748b; text-align: left; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
        .detail-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; line-height: 1.5; }
        .detail-table th:first-child, .detail-table td:first-child { padding-left: 28px; }
        .detail-table th:last-child, .detail-table td:last-child { padding-right: 28px; }
        .detail-table thead th { padding-top: 18px; }
        .detail-table tbody tr:nth-child(even) { background: #fafbfd; }
        .detail-table tbody tr:hover { background: #f5f3ff; }
        .detail-table tbody tr:last-child td { border-bottom: none; padding-bottom: 18px; }
        .detail-table .col-akun { width: 100px; font-weight: 600; color: var(--accent); white-space: nowrap; }
        .detail-table .col-nama { min-width: 200px; }
        .detail-table .col-bta { width: 100px; text-align: center; }
        .detail-table .col-jumlah { width: 140px; text-align: right; font-weight: 600; white-space: nowrap; color: #0f172a; }
        .detail-table th.col-jumlah { text-align: right; }
        .detail-loading { padding: 40px 28px; color: var(--muted); font-size: .88rem; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; min-height: 120px; }
        .total-card { margin: 0; background: linear-gradient(135deg,#f5f3ff 0%,#ede9fe 100%); border-top: 2px solid #ddd6fe; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        .total-card .label { font-size: .88rem; font-weight: 700; color: #475569; }
        .total-card .value { font-size: 1.25rem; font-weight: 800; color: var(--accent); }
        .total-card .sub { font-size: .78rem; color: var(--muted); width: 100%; }
        .empty, .loader { text-align: center; padding: 48px 20px; color: var(--muted); }
        .spinner { width: 36px; height: 36px; border: 3px solid rgba(109,40,217,.15); border-top-color: var(--accent); border-radius: 50%; animation: spin .7s linear infinite; margin: 0 auto 12px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .pagination { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 12px 16px; border-top: 1px solid var(--border); background: #fafbfc; }
        .pagination-info { font-size: .84rem; color: var(--muted); font-weight: 500; }
        .pagination-btns { display: flex; gap: 8px; }
        .page-btn { border: 1px solid var(--border); background: #fff; color: var(--text); border-radius: 8px; padding: 8px 14px; font-size: .84rem; font-weight: 600; cursor: pointer; font-family: inherit; }
        .page-btn:hover:not(:disabled) { border-color: var(--accent); color: var(--accent); }
        .page-btn:disabled { opacity: .4; cursor: not-allowed; }
        .page-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }
        @media (max-width: 900px) { .filter-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 600px) { .filter-grid { grid-template-columns: 1fr; } .main { padding: 14px; } tr.detail-row td { padding: 14px 12px 16px; } .detail-panel { padding: 4px 4px 8px 8px; } .detail-table-wrap { padding: 12px 14px 14px; } .detail-table th:first-child, .detail-table td:first-child { padding-left: 16px; } .detail-table th:last-child, .detail-table td:last-child { padding-right: 16px; } }
        @media (min-width: 960px) {
            .app { margin-left: 280px; }
            .drawer { transform: translateX(0); }
            .drawer-backdrop { display: none; }
            .burger { display: none; }
            .main { padding: 28px 32px; max-width: 1280px; }
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
            <div class="filter-grid">
                <div class="field">
                    <label for="filterBta">Tahun Akademik (BTA)</label>
                    <select id="filterBta"><option value="">Semua BTA</option></select>
                </div>
                <div class="field">
                    <label for="filterKelas">Kelas</label>
                    <select id="filterKelas"><option value="">Semua Kelas</option></select>
                </div>
                <div class="field">
                    <label for="filterStatus">Status Bayar</label>
                    <select id="filterStatus">
                        <option value="">Semua</option>
                        <option value="1">Lunas</option>
                        <option value="0">Belum Lunas</option>
                    </select>
                </div>
                <div class="field">
                    <label for="filterSearch">Cari Nama / NIS</label>
                    <input type="text" id="filterSearch" placeholder="Ketik nama atau NIS...">
                </div>
            </div>
            <div class="btn-row">
                <button type="button" class="btn btn-primary" id="btnFilter"><i class="fas fa-filter"></i> Terapkan Filter</button>
                <a href="#" class="btn btn-excel" id="btnExportExcel"><i class="fas fa-file-excel"></i> Export Excel</a>
                <a href="#" class="btn btn-pdf" id="btnExportPdf"><i class="fas fa-file-pdf"></i> Export PDF</a>
            </div>
        </div>

        <div class="table-card">
            <div class="table-toolbar">
                <div class="info" id="tableInfo">Memuat data...</div>
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
                    Memuat data tagihan...
                </div>
                <table id="tagihanTable" style="display:none;">
                    <thead>
                        <tr>
                            <th class="col-action"></th>
                            <th class="col-no">No</th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Kode Tagihan</th>
                            <th>Nama Tagihan</th>
                            <th class="sortable active" id="sortFurutan" title="Klik untuk urutkan">Urutan <i class="fas fa-sort-up sort-icon" id="sortFurutanIcon"></i></th>
                            <th class="col-jumlah">Jumlah</th>
                            <th>BTA</th>
                            <th>Status</th>
                            <th>Tgl Tagihan</th>
                            <th>Tgl Bayar</th>
                        </tr>
                    </thead>
                    <tbody id="tagihanBody"></tbody>
                </table>
                <div class="empty" id="emptyState" style="display:none;">
                    <i class="fas fa-inbox" style="font-size:2.2rem;opacity:.35;display:block;margin-bottom:10px;"></i>
                    Tidak ada data tagihan
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

            <div class="total-card" id="totalCard" style="display:none;">
                <div>
                    <div class="label">Total Bayar</div>
                    <div class="sub" id="totalRowsInfo">Semua data sesuai filter</div>
                </div>
                <div class="value" id="totalTagihan">Menghitung...</div>
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
    <div class="drawer-footer">App Ver : 1.0.0</div>
</aside>

<script>
    const routes = {
        data: @json(route('kepsek.tagihan.data')),
        summary: @json(route('kepsek.tagihan.summary')),
        detail: @json(route('kepsek.tagihan.detail')),
        filters: @json(route('kepsek.tagihan.filters')),
        exportExcel: @json(route('kepsek.tagihan.export-excel')),
        exportPdf: @json(route('kepsek.tagihan.export-pdf')),
    };
    const csrf = @json(csrf_token());

    let currentPage = 1;
    let hasMore = false;
    let sortFurutanDir = 'asc';
    let filtersLoaded = false;
    let openDetailRow = null;
    let lastSummaryKey = null;
    let cachedSummary = null;
    const detailCache = new Map();

    function formatRupiah(val) {
        const n = Number(val) || 0;
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(n);
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function formatTglBayar(row) {
        const paid = Number(row.status_bayar) === 1;
        const tgl = row.tanggal_bayar;
        if (!paid || !tgl || String(tgl).trim() === '' || String(tgl).trim() === '-') {
            return '-';
        }
        return escapeHtml(tgl);
    }

    function getFilterParams() {
        return {
            bta: document.getElementById('filterBta').value,
            kelas: document.getElementById('filterKelas').value,
            paidst: document.getElementById('filterStatus').value,
            search: document.getElementById('filterSearch').value.trim(),
        };
    }

    function getDataParams() {
        return {
            ...getFilterParams(),
            limit: document.getElementById('perPage').value,
            page: currentPage,
            sort_dir: sortFurutanDir,
        };
    }

    function updateSortFurutanIcon() {
        const th = document.getElementById('sortFurutan');
        const icon = document.getElementById('sortFurutanIcon');
        if (!th || !icon) return;
        th.classList.add('active');
        if (sortFurutanDir === 'desc') {
            icon.className = 'fas fa-sort-down sort-icon';
        } else {
            icon.className = 'fas fa-sort-up sort-icon';
        }
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

    function updatePagination(pagination, rowCount) {
        const bar = document.getElementById('paginationBar');
        const info = document.getElementById('paginationInfo');
        const tableInfo = document.getElementById('tableInfo');
        const btnPrev = document.getElementById('btnPrev');
        const btnNext = document.getElementById('btnNext');
        const btnPageNum = document.getElementById('btnPageNum');
        const totalCard = document.getElementById('totalCard');

        if (!pagination || rowCount === 0) {
            bar.style.display = 'none';
            totalCard.style.display = 'none';
            cachedSummary = null;
            tableInfo.textContent = 'Tidak ada data';
            return;
        }

        hasMore = !!pagination.has_more;
        tableInfo.textContent = `Menampilkan ${pagination.from}–${pagination.to} data`;
        info.textContent = `Halaman ${pagination.page}`;
        btnPageNum.textContent = pagination.page;
        btnPrev.disabled = pagination.page <= 1;
        btnNext.disabled = !hasMore;
        bar.style.display = 'flex';
    }

    function applyGrandTotal(totalJumlah, totalRows) {
        const totalCard = document.getElementById('totalCard');
        const totalEl = document.getElementById('totalTagihan');
        const rowsInfo = document.getElementById('totalRowsInfo');
        totalCard.style.display = 'flex';
        totalEl.textContent = formatRupiah(totalJumlah || 0);
        rowsInfo.textContent = `${totalRows || 0} tagihan · total semua halaman`;
    }

    async function loadGrandTotal() {
        const totalEl = document.getElementById('totalTagihan');
        const rowsInfo = document.getElementById('totalRowsInfo');
        document.getElementById('totalCard').style.display = 'flex';
        totalEl.textContent = 'Menghitung...';
        rowsInfo.textContent = '';

        try {
            const q = buildQuery(getFilterParams());
            const res = await fetch(routes.summary + (q ? '?' + q : ''), { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            if (!json.success) {
                totalEl.textContent = '-';
                return;
            }
            cachedSummary = {
                total_jumlah: json.total_jumlah || 0,
                total_rows: json.total_rows || 0,
            };
            applyGrandTotal(cachedSummary.total_jumlah, cachedSummary.total_rows);
        } catch (e) {
            totalEl.textContent = '-';
        }
    }

    function closeAllDetails() {
        document.querySelectorAll('.detail-row').forEach(r => r.remove());
        document.querySelectorAll('.btn-toggle.open').forEach(b => {
            b.classList.remove('open');
            b.innerHTML = '<i class="fas fa-plus"></i>';
        });
        document.querySelectorAll('.data-row.expanded').forEach(r => r.classList.remove('expanded'));
        openDetailRow = null;
    }

    async function loadFilterOptionsLazy() {
        if (filtersLoaded) return;
        filtersLoaded = true;
        try {
            const res = await fetch(routes.filters, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            if (!json.success) return;
            const btaSel = document.getElementById('filterBta');
            const kelasSel = document.getElementById('filterKelas');
            (json.bta || []).forEach(v => {
                if ([...btaSel.options].some(o => o.value === v)) return;
                const opt = document.createElement('option');
                opt.value = v; opt.textContent = v;
                btaSel.appendChild(opt);
            });
            (json.kelas || []).forEach(v => {
                if ([...kelasSel.options].some(o => o.value === v)) return;
                const opt = document.createElement('option');
                opt.value = v; opt.textContent = v;
                kelasSel.appendChild(opt);
            });
        } catch (e) { filtersLoaded = false; }
    }

    function renderDetailPanel(details) {
        const items = details || [];

        const detailRows = items.map(d => `
            <tr>
                <td class="col-akun">${escapeHtml(d.kode_akun)}</td>
                <td class="col-nama">${escapeHtml(d.nama_akun)}</td>
                <td class="col-bta">${escapeHtml(d.bta)}</td>
                <td class="col-jumlah">${formatRupiah(d.jumlah)}</td>
            </tr>
        `).join('');

        const headerHtml = `
            <div class="detail-header">
                <div class="detail-header-left">
                    <div class="detail-icon"><i class="fas fa-receipt"></i></div>
                    <div>
                        <div class="detail-title">Rincian Tagihan</div>
                        <div class="detail-subtitle">Komponen biaya tagihan ini</div>
                    </div>
                </div>
                <span class="detail-badge"><i class="fas fa-list-ul"></i> ${items.length} item</span>
            </div>
        `;

        if (!items.length) {
            return `
                <div class="detail-panel">
                    ${headerHtml}
                    <div class="detail-table-wrap">
                        <div class="detail-table-scroll">
                            <table class="detail-table">
                                <tbody><tr><td colspan="4" style="text-align:center;padding:36px 28px;color:#64748b;">Tidak ada rincian</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        return `
            <div class="detail-panel">
                ${headerHtml}
                <div class="detail-table-wrap">
                    <div class="detail-table-scroll">
                        <table class="detail-table">
                            <thead>
                                <tr>
                                    <th class="col-akun">Kode Akun</th>
                                    <th class="col-nama">Nama Akun</th>
                                    <th class="col-bta">BTA</th>
                                    <th class="col-jumlah">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>${detailRows}</tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    async function toggleDetail(btn, dataRow) {
        const custid = btn.dataset.custid;
        const kode = btn.dataset.kode;
        const cacheKey = `${custid}|${kode}`;
        const existing = dataRow.nextElementSibling;

        if (existing && existing.classList.contains('detail-row')) {
            existing.remove();
            btn.classList.remove('open');
            btn.innerHTML = '<i class="fas fa-plus"></i>';
            dataRow.classList.remove('expanded');
            openDetailRow = null;
            return;
        }

        closeAllDetails();

        btn.classList.add('open');
        btn.innerHTML = '<i class="fas fa-plus"></i>';
        dataRow.classList.add('expanded');

        const detailTr = document.createElement('tr');
        detailTr.className = 'detail-row';

        if (detailCache.has(cacheKey)) {
            detailTr.innerHTML = `<td colspan="13">${renderDetailPanel(detailCache.get(cacheKey))}</td>`;
            dataRow.after(detailTr);
            openDetailRow = detailTr;
            return;
        }

        detailTr.innerHTML = `<td colspan="13"><div class="detail-loading"><div class="spinner"></div>Memuat rincian...</div></td>`;
        dataRow.after(detailTr);
        openDetailRow = detailTr;

        try {
            const res = await fetch(routes.detail, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ custid, kode_tagihan: kode }),
            });

            let json;
            try {
                json = await res.json();
            } catch (parseErr) {
                detailTr.innerHTML = `<td colspan="13"><div class="detail-panel" style="color:#991b1b;">Respons server tidak valid (HTTP ${res.status})</div></td>`;
                return;
            }

            const details = json.data?.detail || [];
            if (!res.ok || !json.success || !details.length) {
                detailTr.innerHTML = `<td colspan="13"><div class="detail-panel" style="color:#991b1b;">${escapeHtml(json.message || 'Rincian tagihan tidak ditemukan')}</div></td>`;
                return;
            }

            detailCache.set(cacheKey, details);
            detailTr.innerHTML = `<td colspan="13">${renderDetailPanel(details)}</td>`;
        } catch (e) {
            detailTr.innerHTML = `<td colspan="13"><div class="detail-panel" style="color:#991b1b;">Gagal memuat rincian — koneksi timeout. Coba lagi.</div></td>`;
        }
    }

    async function loadTagihan(resetPage = false) {
        if (resetPage) {
            currentPage = 1;
            closeAllDetails();
            lastSummaryKey = null;
            cachedSummary = null;
        }

        const loader = document.getElementById('tableLoader');
        const table = document.getElementById('tagihanTable');
        const empty = document.getElementById('emptyState');
        const tbody = document.getElementById('tagihanBody');
        const paginationBar = document.getElementById('paginationBar');
        const totalCard = document.getElementById('totalCard');
        const tableScroll = document.querySelector('.table-scroll');
        const softReload = !resetPage && table.style.display === 'table';

        if (softReload) {
            tableScroll.classList.add('is-loading');
            loader.style.display = 'block';
        } else {
            loader.style.display = 'block';
            table.style.display = 'none';
            empty.style.display = 'none';
            paginationBar.style.display = 'none';
            if (resetPage) {
                totalCard.style.display = 'none';
            }
            tbody.innerHTML = '';
        }
        document.getElementById('tableInfo').textContent = 'Memuat data...';
        updateExportLinks();

        try {
            const q = buildQuery(getDataParams());
            const res = await fetch(routes.data + '?' + q, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            loader.style.display = 'none';
            tableScroll.classList.remove('is-loading');

            if (!json.success) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: json.message || 'Gagal memuat data', confirmButtonColor: '#6d28d9' });
                if (!softReload) {
                    empty.style.display = 'block';
                }
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
                    <td class="col-action">
                        <button type="button" class="btn-toggle" title="Lihat rincian"
                            data-custid="${escapeHtml(row.custid)}" data-kode="${escapeHtml(row.kode_tagihan)}">
                            <i class="fas fa-plus"></i>
                        </button>
                    </td>
                    <td class="col-no">${startNo + i}</td>
                    <td>${escapeHtml(row.nis)}</td>
                    <td>${escapeHtml(row.nama)}</td>
                    <td>${escapeHtml(row.kelas)}</td>
                    <td>${escapeHtml(row.kode_tagihan)}</td>
                    <td>${escapeHtml(row.nama_tagihan)}</td>
                    <td>${escapeHtml(row.furutan ?? '-')}</td>
                    <td class="col-jumlah">${formatRupiah(row.jumlah)}</td>
                    <td>${escapeHtml(row.tahun_akademik)}</td>
                    <td><span class="badge ${paid ? 'badge-lunas' : 'badge-belum'}">${paid ? 'Lunas' : 'Belum Lunas'}</span></td>
                    <td>${escapeHtml(row.tanggal_tagihan)}</td>
                    <td>${formatTglBayar(row)}</td>
                `;
                tbody.appendChild(tr);

                tr.querySelector('.btn-toggle').addEventListener('click', function () {
                    toggleDetail(this, tr);
                });
            });

            table.style.display = 'table';
            empty.style.display = 'none';
            updatePagination(json.pagination, rows.length);

            const summaryKey = buildQuery(getFilterParams());
            if (lastSummaryKey === null || summaryKey !== lastSummaryKey) {
                lastSummaryKey = summaryKey;
                loadGrandTotal();
            } else if (cachedSummary) {
                applyGrandTotal(cachedSummary.total_jumlah, cachedSummary.total_rows);
            } else {
                loadGrandTotal();
            }
        } catch (e) {
            loader.style.display = 'none';
            tableScroll.classList.remove('is-loading');
            if (!softReload) {
                empty.style.display = 'block';
            }
            Swal.fire({ icon: 'error', title: 'Error', text: 'Tidak dapat terhubung ke server', confirmButtonColor: '#6d28d9' });
        }
    }

    document.getElementById('sortFurutan').addEventListener('click', () => {
        sortFurutanDir = sortFurutanDir === 'asc' ? 'desc' : 'asc';
        updateSortFurutanIcon();
        loadTagihan(true);
    });
    document.getElementById('btnFilter').addEventListener('click', () => { loadTagihan(true); });
    document.getElementById('filterSearch').addEventListener('keydown', e => { if (e.key === 'Enter') loadTagihan(true); });
    document.getElementById('perPage').addEventListener('change', () => loadTagihan(true));
    document.getElementById('btnPrev').addEventListener('click', () => { if (currentPage > 1) { currentPage--; loadTagihan(); } });
    document.getElementById('btnNext').addEventListener('click', () => { if (hasMore) { currentPage++; loadTagihan(); } });
    document.getElementById('filterBta').addEventListener('focus', loadFilterOptionsLazy);
    document.getElementById('filterKelas').addEventListener('focus', loadFilterOptionsLazy);

    const toggleBtn = document.getElementById('drawerToggle');
    const backdrop = document.getElementById('drawerBackdrop');
    const drawer = document.getElementById('drawer');
    toggleBtn.addEventListener('click', () => {
        const open = drawer.classList.toggle('open');
        backdrop.classList.toggle('open', open);
    });
    backdrop.addEventListener('click', () => { drawer.classList.remove('open'); backdrop.classList.remove('open'); });

    updateSortFurutanIcon();
    loadTagihan();
</script>
@if (session('error'))
<script>Swal.fire({ icon: 'warning', title: 'Perhatian', text: @json(session('error')), confirmButtonColor: '#6d28d9' });</script>
@endif
</body>
</html>
