<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Prestasi</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: #f5f2fb; color: #1f2431; }
        .top { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: #fff; border-bottom: 1px solid #e8e4f2; position: sticky; top: 0; }
        .title { font-size: 1.1rem; font-weight: 700; color: #5b21b6; }
        .meta { font-size: 0.85rem; color: #64748b; }
        .wrap { padding: 16px 20px 28px; }
        .toolbar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
        .chip { border: 1px solid #d7cdf1; border-radius: 10px; padding: 8px 12px; text-decoration: none; color: #5b21b6; background: #fff; font-weight: 600; font-size: 0.85rem; }
        .chip.active { background: #5b21b6; color: #fff; border-color: #5b21b6; }
        .card { background: #fff; border: 1px solid #e8e4f2; border-radius: 14px; padding: 12px; margin-bottom: 10px; }
        .head { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 8px; }
        .nm { font-weight: 700; font-size: 0.95rem; }
        .sub { font-size: 0.8rem; color: #64748b; margin-top: 3px; }
        .badge { font-size: 0.75rem; padding: 5px 8px; border-radius: 999px; font-weight: 700; }
        .badge.pending { background: #fef3c7; color: #92400e; }
        .badge.approved { background: #dcfce7; color: #166534; }
        .body { font-size: 0.86rem; color: #334155; line-height: 1.45; }
        .row { margin-bottom: 4px; }
        .actions { margin-top: 10px; display: flex; gap: 8px; }
        .btn { border: none; border-radius: 10px; padding: 8px 12px; font-size: 0.82rem; font-weight: 700; cursor: pointer; }
        .btn.approve { background: #16a34a; color: #fff; }
        .btn.reject { background: #dc2626; color: #fff; }
        .empty { text-align: center; color: #64748b; background: #fff; border: 1px dashed #d8d2ea; border-radius: 12px; padding: 30px 12px; }
        .msg { margin-bottom: 10px; border-radius: 10px; padding: 10px 12px; font-size: 0.84rem; font-weight: 600; }
        .msg.ok { background: #dcfce7; color: #166534; }
        .msg.err { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>
    <div class="top">
        <div>
            <div class="title">Aplikasi Approval Prestasi</div>
            <div class="meta">
                Login: {{ session('user.nama', session('user.username')) }}
                @if($scopeCode01 !== '')
                    · Scope CODE01 {{ $scopeCode01 }}
                @else
                    · Scope Semua Sekolah
                @endif
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="chip"><i class="fas fa-right-from-bracket"></i> Logout</button>
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
            <a href="{{ route('approval.prestasi.index', ['status' => 'pending']) }}" class="chip {{ $status === 'pending' ? 'active' : '' }}">Pending</a>
            <a href="{{ route('approval.prestasi.index', ['status' => 'approved']) }}" class="chip {{ $status === 'approved' ? 'active' : '' }}">Approved</a>
            <a href="{{ route('approval.prestasi.index', ['status' => 'all']) }}" class="chip {{ $status === 'all' ? 'active' : '' }}">Semua</a>
        </div>

        @forelse($items as $item)
            <div class="card">
                <div class="head">
                    <div>
                        <div class="nm">{{ $item['nmcust'] ?: '-' }}</div>
                        <div class="sub">
                            NIS/NOCUST: {{ $item['nocust'] ?: '-' }} · Kelas: {{ $item['kelas'] ?: '-' }}
                            · CODE01: {{ $item['code01'] ?: '-' }}{{ $item['sekolah'] ? ' (' . $item['sekolah'] . ')' : '' }}
                        </div>
                    </div>
                    <div class="badge {{ (int) $item['isapproved'] === 1 ? 'approved' : 'pending' }}">
                        {{ (int) $item['isapproved'] === 1 ? 'APPROVED' : 'PENDING' }}
                    </div>
                </div>
                <div class="body">
                    <div class="row"><strong>Jenis:</strong> {{ $item['jenis_prestasi'] ?: '-' }}</div>
                    <div class="row"><strong>Keterangan:</strong> {{ $item['keterangan'] ?: '-' }}</div>
                    <div class="row"><strong>Nilai:</strong> {{ number_format((float) ($item['nilai_penghargaan'] ?? 0), 2, ',', '.') }}</div>
                    <div class="row"><strong>Tahun Akademik:</strong> {{ $item['bta'] ?: '-' }}</div>
                    <div class="row"><strong>URL Bukti:</strong>
                        @if(!empty($item['url']))
                            <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer">Lihat</a>
                        @else
                            -
                        @endif
                    </div>
                    <div class="row"><strong>Approved Date:</strong> {{ $item['approveddate'] ?: '-' }}</div>
                    <div class="row"><strong>Approved By:</strong> {{ $item['approvedby'] ?: '-' }}</div>
                </div>
                <div class="actions">
                    <form method="POST" action="{{ route('approval.prestasi.action') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $item['id'] }}">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <button type="submit" class="btn approve">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('approval.prestasi.action') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $item['id'] }}">
                        <input type="hidden" name="action" value="tolak">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <button type="submit" class="btn reject">Tolak</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty">Tidak ada data approval pada filter ini.</div>
        @endforelse
    </div>
</body>
</html>

