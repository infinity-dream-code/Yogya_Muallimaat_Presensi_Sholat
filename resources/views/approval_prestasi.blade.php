<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Prestasi</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --purple: #5b21b6;
            --purple-soft: #f3eefc;
            --purple-border: #ddd4f5;
            --text: #1f2431;
            --muted: #64748b;
            --bg: #f5f2fb;
            --card: #ffffff;
            --ok: #16a34a;
            --danger: #dc2626;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: var(--bg); color: var(--text); }
        .top {
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
            padding: 16px 20px; background: #fff; border-bottom: 1px solid var(--purple-border);
            position: sticky; top: 0; z-index: 20;
            box-shadow: 0 1px 0 rgba(91, 33, 182, 0.04);
        }
        .title { font-size: 1.12rem; font-weight: 700; color: var(--purple); }
        .meta { font-size: 0.84rem; color: var(--muted); margin-top: 3px; }
        .scope-pill {
            display: inline-flex; align-items: center; gap: 6px;
            margin-top: 6px; padding: 4px 10px; border-radius: 999px;
            background: var(--purple-soft); color: var(--purple); font-size: 0.75rem; font-weight: 700;
        }
        .wrap { max-width: 920px; margin: 0 auto; padding: 16px 16px 36px; }
        .toolbar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
        .chip {
            border: 1px solid var(--purple-border); border-radius: 999px; padding: 8px 14px;
            text-decoration: none; color: var(--purple); background: #fff;
            font-weight: 700; font-size: 0.84rem; display: inline-flex; align-items: center; gap: 6px;
            transition: .15s ease;
        }
        .chip:hover { background: var(--purple-soft); }
        .chip.active { background: var(--purple); color: #fff; border-color: var(--purple); }
        .btn-logout { cursor: pointer; }
        .filters {
            background: var(--card); border: 1px solid var(--purple-border); border-radius: 16px;
            padding: 14px; margin-bottom: 14px;
            box-shadow: 0 8px 24px rgba(91, 33, 182, 0.05);
        }
        .filters-grid {
            display: grid; grid-template-columns: 1.5fr 1fr auto auto; gap: 10px; align-items: end;
        }
        .field label {
            display: block; font-size: 0.75rem; font-weight: 700; color: var(--muted);
            margin-bottom: 6px; text-transform: uppercase; letter-spacing: .03em;
        }
        .field input {
            width: 100%; border: 1px solid #e4dcf7; border-radius: 12px; padding: 10px 12px;
            font: inherit; font-size: 0.9rem; background: #fbfaff; color: var(--text);
            outline: none; transition: .15s ease;
        }
        .field input:focus { border-color: #a78bfa; box-shadow: 0 0 0 3px rgba(167, 139, 250, .25); background: #fff; }
        .btn {
            border: none; border-radius: 12px; padding: 10px 14px; font-size: 0.84rem;
            font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            text-decoration: none; white-space: nowrap;
        }
        .btn-primary { background: var(--purple); color: #fff; }
        .btn-ghost { background: #fff; color: var(--purple); border: 1px solid var(--purple-border); }
        .btn.approve { background: var(--ok); color: #fff; }
        .btn.reject { background: var(--danger); color: #fff; }
        .summary {
            display: flex; justify-content: space-between; align-items: center; gap: 8px;
            margin-bottom: 12px; color: var(--muted); font-size: 0.84rem; font-weight: 600;
        }
        .card {
            background: var(--card); border: 1px solid var(--purple-border); border-radius: 16px;
            padding: 14px; margin-bottom: 12px;
            box-shadow: 0 8px 22px rgba(91, 33, 182, 0.05);
        }
        .head { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
        .nm { font-weight: 700; font-size: 1rem; }
        .sub { font-size: 0.82rem; color: var(--muted); margin-top: 4px; line-height: 1.45; }
        .school-tag {
            display: inline-flex; align-items: center; gap: 6px; margin-top: 8px;
            padding: 4px 10px; border-radius: 999px; background: #eef2ff; color: #4338ca;
            font-size: 0.75rem; font-weight: 700;
        }
        .badge { font-size: 0.72rem; padding: 5px 10px; border-radius: 999px; font-weight: 800; height: fit-content; }
        .badge.pending { background: #fef3c7; color: #92400e; }
        .badge.approved { background: #dcfce7; color: #166534; }
        .grid {
            display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px 14px;
            font-size: 0.86rem; color: #334155;
        }
        .row-label { display: block; font-size: 0.72rem; font-weight: 700; color: #94a3b8; margin-bottom: 2px; text-transform: uppercase; letter-spacing: .03em; }
        .row-value { font-weight: 600; word-break: break-word; }
        .row-value a { color: #4f46e5; font-weight: 700; text-decoration: none; }
        .row-value a:hover { text-decoration: underline; }
        .actions { margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap; padding-top: 12px; border-top: 1px dashed #ebe5f8; }
        .empty {
            text-align: center; color: var(--muted); background: #fff;
            border: 1px dashed #d8d2ea; border-radius: 16px; padding: 42px 16px;
        }
        .empty i { font-size: 1.6rem; color: #a78bfa; margin-bottom: 8px; }
        .msg { margin-bottom: 12px; border-radius: 12px; padding: 10px 12px; font-size: 0.84rem; font-weight: 600; }
        .msg.ok { background: #dcfce7; color: #166534; }
        .msg.err { background: #fee2e2; color: #b91c1c; }
        @media (max-width: 760px) {
            .filters-grid { grid-template-columns: 1fr; }
            .grid { grid-template-columns: 1fr; }
            .top { align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="top">
        <div>
            <div class="title">Aplikasi Approval Prestasi</div>
            <div class="meta">Login: {{ session('user.nama', session('user.username')) }}</div>
            <div class="scope-pill">
                <i class="fas fa-school"></i>
                @if($scopeCode01 !== '')
                    {{ $scopeSekolah !== '' ? $scopeSekolah : 'Scope Sekolah' }}
                @else
                    Semua Sekolah
                @endif
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="chip btn-logout"><i class="fas fa-right-from-bracket"></i> Logout</button>
        </form>
    </div>

    <div class="wrap">
        @if(session('success'))
            <div class="msg ok">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="msg err">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="msg err">{{ $errors->first() }}</div>
        @endif
        @if(!empty($errorMessage))
            <div class="msg err">{{ $errorMessage }}</div>
        @endif

        <div class="toolbar">
            <a href="{{ route('approval.prestasi.index', array_filter(['status' => 'pending', 'q' => $q, 'tanggal' => $tanggal])) }}" class="chip {{ $status === 'pending' ? 'active' : '' }}">Pending</a>
            <a href="{{ route('approval.prestasi.index', array_filter(['status' => 'approved', 'q' => $q, 'tanggal' => $tanggal])) }}" class="chip {{ $status === 'approved' ? 'active' : '' }}">Approved</a>
            <a href="{{ route('approval.prestasi.index', array_filter(['status' => 'all', 'q' => $q, 'tanggal' => $tanggal])) }}" class="chip {{ $status === 'all' ? 'active' : '' }}">Semua</a>
        </div>

        <form method="GET" action="{{ route('approval.prestasi.index') }}" class="filters">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="filters-grid">
                <div class="field">
                    <label for="q">NIS / Nama</label>
                    <input id="q" type="text" name="q" value="{{ $q }}" placeholder="Cari NIS atau nama siswa...">
                </div>
                <div class="field">
                    <label for="tanggal">Tanggal</label>
                    <input id="tanggal" type="date" name="tanggal" value="{{ $tanggal }}">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                <a href="{{ route('approval.prestasi.index', ['status' => $status]) }}" class="btn btn-ghost">Reset</a>
            </div>
        </form>

        <div class="summary">
            <span>{{ count($items) }} data ditampilkan</span>
            @if($q !== '' || $tanggal !== '')
                <span>Filter aktif</span>
            @endif
        </div>

        @forelse($items as $item)
            @php
                $sekolah = trim((string) ($item['sekolah'] ?? ''));
                $isApproved = (int) ($item['isapproved'] ?? 0) === 1;
            @endphp
            <div class="card">
                <div class="head">
                    <div>
                        <div class="nm">{{ $item['nmcust'] ?: '-' }}</div>
                        <div class="sub">
                            NIS: {{ $item['nocust'] ?: '-' }} · Kelas: {{ $item['kelas'] ?: '-' }}
                        </div>
                        @if($sekolah !== '')
                            <div class="school-tag"><i class="fas fa-building-columns"></i> {{ $sekolah }}</div>
                        @endif
                    </div>
                    <div class="badge {{ $isApproved ? 'approved' : 'pending' }}">
                        {{ $isApproved ? 'APPROVED' : 'PENDING' }}
                    </div>
                </div>
                <div class="grid">
                    <div>
                        <span class="row-label">Jenis</span>
                        <div class="row-value">{{ $item['jenis_prestasi'] ?: '-' }}</div>
                    </div>
                    <div>
                        <span class="row-label">Nilai</span>
                        <div class="row-value">{{ number_format((float) ($item['nilai_penghargaan'] ?? 0), 2, ',', '.') }}</div>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <span class="row-label">Keterangan</span>
                        <div class="row-value">{{ $item['keterangan'] ?: '-' }}</div>
                    </div>
                    <div>
                        <span class="row-label">Tahun Akademik</span>
                        <div class="row-value">{{ $item['bta'] ?: '-' }}</div>
                    </div>
                    <div>
                        <span class="row-label">URL Bukti</span>
                        <div class="row-value">
                            @if(!empty($item['url']))
                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer">Lihat bukti</a>
                            @else
                                -
                            @endif
                        </div>
                    </div>
                    <div>
                        <span class="row-label">Approved Date</span>
                        <div class="row-value">{{ $item['approveddate'] ?: '-' }}</div>
                    </div>
                    <div>
                        <span class="row-label">Approved By</span>
                        <div class="row-value">{{ $item['approvedby'] ?: '-' }}</div>
                    </div>
                </div>
                <div class="actions">
                    <form method="POST" action="{{ route('approval.prestasi.action') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $item['id'] }}">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="hidden" name="q" value="{{ $q }}">
                        <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                        <button type="submit" class="btn approve"><i class="fas fa-check"></i> Approve</button>
                    </form>
                    <form method="POST" action="{{ route('approval.prestasi.action') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $item['id'] }}">
                        <input type="hidden" name="action" value="tolak">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="hidden" name="q" value="{{ $q }}">
                        <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                        <button type="submit" class="btn reject"><i class="fas fa-xmark"></i> Tolak</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty">
                <div><i class="fas fa-inbox"></i></div>
                Tidak ada data approval pada filter ini.
            </div>
        @endforelse
    </div>
</body>
</html>
