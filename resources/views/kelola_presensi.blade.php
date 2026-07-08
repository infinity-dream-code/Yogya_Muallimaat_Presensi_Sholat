<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Presensi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg:#fff7ff;
            --surface:#fff;
            --surface2:#f5f5f1;
            --border:#e5e3de;
            --border2:#d0cdc6;
            --t1:#18170f;
            --t2:#6b6860;
            --t3:#9b9890;
            --accent:#1a6b3a;
            --accent2:#228848;
            --accentl:#e6f4eb;
            --r:#be3626;
            --rbg:#fdf0ee;
            --rb:#f0b8b0;
            --y:#a85d0a;
            --ybg:#fef8ea;
            --yb:#edcc80;
            --g:#1a6b3a;
            --gbg:#e6f4eb;
            --gb:#9ecfb0;
            --p:#8b3560;
            --pbg:#fceef4;
            --pb:#e0a8c4;
            --gray:#eceae5;
            --grayt:#8b8880;
            --s1:0 1px 3px rgba(0,0,0,.06);
            --s2:0 4px 16px rgba(0,0,0,.08);
            --s3:0 12px 40px rgba(0,0,0,.12);
            --rad:12px;
            --rads:8px;
            --radl:18px;
            --sw:272px;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        html,body{height:100%}
        body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--t1);line-height:1.5}
        ::-webkit-scrollbar{width:5px;height:5px}
        ::-webkit-scrollbar-track{background:transparent}
        ::-webkit-scrollbar-thumb{background:var(--border2);border-radius:99px}

        /* ── SIDEBAR ── */
        .sidebar{
            width:var(--sw);
            background: linear-gradient(180deg, #1e1b4b 0%, #0f0d1e 100%);
            position:fixed;top:0;left:0;bottom:0;
            display:flex;flex-direction:column;
            z-index:40;overflow-y:auto;overflow-x:hidden;
            transition:transform .28s cubic-bezier(.4,0,.2,1);
            border-right:1px solid rgba(255,255,255,.08);
            box-shadow:8px 0 32px rgba(0,0,0,.2);
            color:#e2e8f0;
        }
        .sb-brand{
            padding:24px 20px 16px;
            display:flex;align-items:center;gap:10px;
            border-bottom:none;
        }
        .sb-logo{
            width:48px;height:48px;border-radius:12px;
            background:transparent;
            display:flex;align-items:center;justify-content:center;
            overflow:hidden;flex-shrink:0;
        }
        .sb-logo img{width:100%;height:100%;object-fit:contain}
        .sb-name{font-size:.95rem;font-weight:600;color:#f8fafc}
        .sb-role{font-size:.78rem;color:#94a3b8;text-transform:none;letter-spacing:0;margin-top:1px}
        .sb-section{padding:0 20px 6px;flex:1}
        .sb-lbl{font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.12em;color:#64748b;padding:0 14px;margin-bottom:10px}
        .sb-nav{list-style:none}
        .sb-nav li{margin-bottom:4px}
        .sb-link{
            display:flex;align-items:center;gap:0;
            padding:12px 14px;border-radius:12px;
            color:#cbd5e1;
            text-decoration:none;font-size:.9rem;font-weight:500;
            transition:all .2s;cursor:pointer;border:none;
            background:transparent;width:100%;text-align:left;font-family:inherit;
        }
        .sb-ico{
            width:24px;height:24px;border-radius:0;
            background:transparent;
            display:flex;align-items:center;justify-content:center;
            font-size:1.05rem;flex-shrink:0;margin-right:12px;
            color:#94a3b8 !important;transition:color .15s;
        }
        .sb-link:hover{background:rgba(255,255,255,.08);color:#f8fafc}
        .sb-link.active{background:linear-gradient(135deg, rgba(124,58,237,0.3) 0%, rgba(109,40,217,0.2) 100%);color:#e9d5ff}
        .sb-link.active .sb-ico{color:#c4b5fd !important}
        .sb-foot{margin-top:auto;padding:16px 20px;border-top:none;font-size:.75rem;color:#64748b;font-family:inherit}
        .sb-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:39;opacity:0;transition:opacity .2s}

        /* ── MAIN ── */
        .main{flex:1;margin-left:var(--sw);display:flex;flex-direction:column;min-height:100vh}
        .topbar{
            background:var(--surface);border-bottom:1px solid var(--border);
            padding:0 28px;height:58px;
            display:flex;align-items:center;gap:12px;
            position:sticky;top:0;z-index:30;width:100%;
        }
        .tb-burger{
            display:none;width:32px;height:32px;
            border:1px solid var(--border);border-radius:var(--rads);
            background:transparent;cursor:pointer;
            align-items:center;justify-content:center;
            color:var(--t2);font-size:.9rem;flex-shrink:0;
        }
        .tb-title{font-size:1.05rem;font-weight:800;color:var(--t1);letter-spacing:-.02em;flex:1}
        .tb-btn{
            width:32px;height:32px;border:1px solid var(--border);
            border-radius:var(--rads);background:transparent;cursor:pointer;
            display:flex;align-items:center;justify-content:center;
            color:var(--t2);font-size:.82rem;transition:all .15s;text-decoration:none;
        }
        .tb-btn:hover{background:var(--accentl);border-color:var(--accent);color:var(--accent)}

        /* ── BODY ── */
        .body{padding:22px 28px 56px;width:100%;flex:1}
        .flash{display:flex;align-items:center;gap:9px;padding:10px 13px;border-radius:var(--rads);font-size:.84rem;font-weight:500;margin-bottom:16px}
        .flash-err{background:var(--rbg);color:var(--r);border:1px solid var(--rb)}
        .flash-ok{background:var(--gbg);color:var(--g);border:1px solid var(--gb)}

        /* ── TOP CONTROLS ── */
        .ctrl-row{display:grid;grid-template-columns:auto 1fr;gap:10px;margin-bottom:12px;align-items:stretch}
        .date-card{
            background:var(--surface);border:1px solid var(--border);
            border-radius:var(--rad);padding:12px 16px;
            display:flex;align-items:center;gap:14px;
            box-shadow:var(--s1);white-space:nowrap;
        }
        .date-lbl{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--t3);margin-bottom:2px}
        .date-val{font-size:.98rem;font-weight:700;color:var(--t1)}
        .btn-date{
            display:inline-flex;align-items:center;gap:7px;
            padding:9px 14px;background:var(--accent);color:#fff;
            border:none;border-radius:var(--rads);
            font-size:.82rem;font-weight:600;
            cursor:pointer;font-family:inherit;transition:all .15s;
        }
        .btn-date:hover{background:var(--accent2);transform:translateY(-1px);box-shadow:0 4px 12px rgba(26,107,58,.3)}
        .search-box{
            background:var(--surface);border:1px solid var(--border);
            border-radius:var(--rad);box-shadow:var(--s1);
            display:flex;align-items:center;overflow:hidden;
        }
        .search-ico{padding:0 13px;color:var(--t3);font-size:.85rem;flex-shrink:0}
        .search-inp{
            flex:1;border:none;outline:none;font-family:inherit;
            font-size:.88rem;color:var(--t1);background:transparent;
            padding:0 12px 0 0;height:48px;
        }
        .search-inp::placeholder{color:var(--t3)}

        /* ── BULK PANEL ── */
        .bulk{
            background:var(--surface);border:1px solid var(--border);
            border-radius:var(--rad);padding:12px 16px;
            margin-bottom:20px;box-shadow:var(--s1);
        }
        .bulk-head{display:flex;align-items:center;gap:8px;margin-bottom:10px}
        .bulk-lbl{font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:var(--t3)}
        .bulk-badge{display:inline-flex;align-items:center;padding:2px 7px;border-radius:99px;background:var(--accentl);color:var(--accent);font-size:.68rem;font-weight:700}
        .bulk-ctrls{display:flex;flex-wrap:wrap;align-items:center;gap:7px}
        .bsel{
            padding:7px 10px;border:1px solid var(--border);
            border-radius:var(--rads);background:var(--surface);
            font-family:inherit;font-size:.82rem;color:var(--t1);
            cursor:pointer;outline:none;transition:border-color .15s;
        }
        .bsel:focus{border-color:var(--accent)}
        .btn-apply{
            display:inline-flex;align-items:center;gap:7px;
            padding:8px 14px;background:var(--accent);color:#fff;
            border:none;border-radius:var(--rads);
            font-size:.82rem;font-weight:600;
            cursor:pointer;font-family:inherit;transition:all .15s;
        }
        .btn-apply:hover{background:var(--accent2)}
        .btn-apply:disabled{opacity:.5;cursor:not-allowed}

        /* ── SECTION HEADER ── */
        .sec{display:flex;align-items:center;gap:10px;margin-bottom:12px}
        .sec-txt{font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--t3);white-space:nowrap}
        .sec-line{flex:1;height:1px;background:var(--border)}

        /* ── GRID ── */
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:10px}

        /* ── STUDENT CARD ── */
        .card-student{
            background:var(--surface);
            border:1px solid var(--border);
            border-radius:16px;
            padding:0;
            box-shadow:0 1px 4px rgba(0,0,0,.05);
            position:relative;
            transition:box-shadow .2s,border-color .2s,transform .2s;
            overflow:hidden;
        }
        .card-student:hover{
            box-shadow:0 6px 24px rgba(0,0,0,.09);
            border-color:var(--border2);
            transform:translateY(-2px);
        }
        .card-student.chk{
            border-color:var(--accent);
            box-shadow:0 0 0 3px rgba(26,107,58,.12),0 4px 16px rgba(26,107,58,.1);
        }

        /* top strip accent bar */
        .card-student::before{
            content:'';
            display:block;height:3px;
            background:linear-gradient(90deg,var(--border2) 0%,transparent 100%);
            transition:background .2s;
        }
        .card-student.chk::before{
            background:linear-gradient(90deg,var(--accent) 0%,var(--accent2) 100%);
        }

        .card-inner{padding:14px 14px 12px}

        .card-header{
            display:flex;align-items:flex-start;
            justify-content:space-between;gap:8px;
            margin-bottom:12px;
        }
        .card-left{display:flex;align-items:flex-start;gap:9px;flex:1;min-width:0}

        .card-checkbox{
            width:16px;height:16px;margin-top:2px;
            accent-color:var(--accent);cursor:pointer;flex-shrink:0;
        }

        .card-info{flex:1;min-width:0}

        .card-title{
            font-size:.88rem;font-weight:700;color:var(--t1);
            line-height:1.35;word-break:break-word;
        }

        .card-sub{
            display:inline-flex;align-items:center;gap:5px;
            font-size:.7rem;color:var(--t3);margin-top:4px;
        }
        .card-sub .unit-badge{
            display:inline-flex;align-items:center;
            padding:1px 6px;border-radius:4px;
            background:var(--surface2);color:var(--t2);
            font-size:.67rem;font-weight:600;
            border:1px solid var(--border);
        }

        .chip-date{
            font-size:.62rem;font-family:'JetBrains Mono',monospace;font-weight:500;
            padding:3px 7px;border-radius:6px;
            background:var(--surface2);color:var(--t3);
            white-space:nowrap;flex-shrink:0;border:1px solid var(--border);
        }

        /* ── SHOLAT CHIPS GRID ── */
        .sholat-chips{
            display:grid;
            grid-template-columns:repeat(5,1fr);
            gap:5px;
        }

        /* ── CHIP REDESIGN ── */
        .chip{
            border:1px solid var(--border) !important;
            border-radius:10px !important;
            padding:0 !important;
            cursor:pointer;
            transition:all .15s cubic-bezier(.4,0,.2,1) !important;
            display:flex !important;
            flex-direction:column !important;
            align-items:center !important;
            justify-content:center !important;
            gap:3px !important;
            height:64px !important;
            min-height:unset !important;
            overflow:hidden !important;
            background:var(--surface2) !important;
            position:relative;
            box-shadow:0 1px 2px rgba(0,0,0,.04) !important;
        }
        .chip:hover{
            transform:translateY(-3px) scale(1.03) !important;
            box-shadow:0 6px 16px rgba(0,0,0,.12) !important;
            z-index:2;
        }
        .chip:active{transform:translateY(0) scale(1) !important}

        .chip-top{
            font-size:.56rem !important;
            font-weight:700 !important;
            font-family:'JetBrains Mono',monospace !important;
            opacity:.45 !important;
            line-height:1 !important;
            color:inherit !important;
            margin:0 !important;
            padding:0 !important;
            letter-spacing:.04em !important;
        }

        .chip-bottom{
            font-size:.75rem !important;
            font-weight:800 !important;
            line-height:1 !important;
            color:inherit !important;
            max-width:88% !important;
            overflow:hidden !important;
            text-overflow:ellipsis !important;
            white-space:nowrap !important;
            padding:0 !important;
            letter-spacing:-.01em !important;
        }

        /* colored chips */
        .chip-sholat{
            background:var(--gbg) !important;
            color:var(--g) !important;
            border-color:var(--gb) !important;
            box-shadow:0 1px 3px rgba(26,107,58,.12) !important;
        }
        .chip-izin{
            background:var(--ybg) !important;
            color:var(--y) !important;
            border-color:var(--yb) !important;
            box-shadow:0 1px 3px rgba(168,93,10,.1) !important;
        }
        .chip-alpa{
            background:var(--rbg) !important;
            color:var(--r) !important;
            border-color:var(--rb) !important;
            box-shadow:0 1px 3px rgba(190,54,38,.12) !important;
        }
        .chip-sakit{
            background:var(--pbg) !important;
            color:var(--p) !important;
            border-color:var(--pb) !important;
            box-shadow:0 1px 3px rgba(139,53,96,.1) !important;
        }
        .chip-haid{
            background:var(--pbg) !important;
            color:var(--p) !important;
            border-color:var(--pb) !important;
            box-shadow:0 1px 3px rgba(139,53,96,.1) !important;
        }
        .chip-belum{
            background:var(--surface2) !important;
            color:var(--grayt) !important;
            border-color:var(--border) !important;
            opacity:.7 !important;
        }
        .chip-disabled{
            opacity:.35 !important;
            cursor:not-allowed !important;
            pointer-events:none !important;
        }

        /* Chip hover color variants */
        .chip-sholat:hover{box-shadow:0 6px 16px rgba(26,107,58,.2) !important}
        .chip-alpa:hover{box-shadow:0 6px 16px rgba(190,54,38,.2) !important}
        .chip-izin:hover{box-shadow:0 6px 16px rgba(168,93,10,.18) !important}
        .chip-sakit:hover,.chip-haid:hover{box-shadow:0 6px 16px rgba(139,53,96,.18) !important}

        .btn-load-more{
            width:100%;padding:13px;
            border:1.5px dashed var(--border2);border-radius:var(--rad);
            background:transparent;color:var(--accent);
            font-size:.84rem;font-weight:600;cursor:pointer;
            display:flex;align-items:center;justify-content:center;gap:8px;
            transition:all .15s;font-family:inherit;
        }
        .btn-load-more:hover{background:var(--accentl);border-color:var(--accent)}
        .btn-load-more:disabled{opacity:.5;cursor:wait}
        .load-more-wrap{grid-column:1/-1;margin-top:4px}

        .empty-state{grid-column:1/-1;text-align:center;padding:56px 24px;background:var(--surface);border:1px solid var(--border);border-radius:var(--rad)}
        .empty-ico{font-size:2rem;opacity:.2;margin-bottom:10px;color:var(--t2)}
        .empty-txt{font-size:.86rem;color:var(--t3)}

        .list-wrap{position:relative;min-height:200px}
        .page-loader{
            position:absolute;inset:0;background:rgba(240,240,236,.9);
            display:flex;flex-direction:column;align-items:center;justify-content:center;
            z-index:5;border-radius:var(--rad);
            transition:opacity .2s,visibility .2s;backdrop-filter:blur(2px);
        }
        .page-loader.hidden{opacity:0;visibility:hidden;pointer-events:none}
        .spinner{width:32px;height:32px;border:3px solid rgba(26,107,58,.12);border-top-color:var(--accent);border-radius:50%;animation:spin .7s linear infinite}
        .loader-txt{margin-top:10px;font-size:.8rem;color:var(--t3)}
        @keyframes spin{to{transform:rotate(360deg)}}

        /* ── MODAL ── */
        .modal-overlay{
            position:fixed;inset:0;background:rgba(0,0,0,.55);
            backdrop-filter:blur(4px);z-index:200;
            display:flex;align-items:center;justify-content:center;padding:20px;
            opacity:0;pointer-events:none;transition:opacity .2s;
        }
        .modal-overlay.open{opacity:1;pointer-events:auto}
        .modal-box{
            background:var(--surface);border-radius:var(--radl);
            padding:24px;width:100%;max-width:330px;
            box-shadow:var(--s3);border:1px solid var(--border);
            transform:scale(.95) translateY(10px);
            transition:transform .22s cubic-bezier(.34,1.56,.64,1);
        }
        .modal-overlay.open .modal-box{transform:scale(1) translateY(0)}
        .modal-title{font-size:.92rem;font-weight:700;color:var(--t1);margin-bottom:4px}
        .modal-sub{font-size:.8rem;color:var(--t3);margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid var(--border)}
        .modal-opts{display:flex;flex-direction:column;gap:5px}
        .modal-opt{
            display:flex;align-items:center;gap:10px;width:100%;
            padding:10px 12px;border:1.5px solid var(--border);
            border-radius:var(--rads);background:var(--surface);
            color:var(--t1);font-size:.86rem;font-weight:500;
            font-family:inherit;cursor:pointer;text-align:left;transition:all .13s;
        }
        .opt-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
        .modal-opt:hover{transform:translateX(2px)}
        .modal-opt[data-status="Sholat"]:hover,.modal-opt[data-status="Haid"]:hover{border-color:var(--g);background:var(--gbg);color:var(--g)}
        .modal-opt[data-status="Izin"]:hover{border-color:var(--y);background:var(--ybg);color:var(--y)}
        .modal-opt[data-status="Alpa"]:hover{border-color:var(--r);background:var(--rbg);color:var(--r)}
        .modal-opt[data-status="Sakit"]:hover{border-color:var(--p);background:var(--pbg);color:var(--p)}
        .modal-close{
            margin-top:9px;width:100%;padding:9px;
            border:1px solid var(--border);border-radius:var(--rads);
            background:transparent;color:var(--t3);
            font-size:.84rem;font-family:inherit;cursor:pointer;transition:all .13s;
        }
        .modal-close:hover{background:var(--surface2);color:var(--t1)}

        /* ── TOAST ── */
        .toast{
            position:fixed;bottom:22px;left:50%;
            transform:translateX(-50%);
            padding:9px 16px;border-radius:99px;
            font-size:.84rem;font-weight:600;z-index:300;
            display:flex;align-items:center;gap:7px;
            box-shadow:var(--s3);animation:toastIn .3s cubic-bezier(.34,1.56,.64,1);
            border:1px solid transparent;white-space:nowrap;
        }
        .toast.success{background:var(--surface);color:var(--g);border-color:var(--gb)}
        .toast.error{background:var(--surface);color:var(--r);border-color:var(--rb)}
        @keyframes toastIn{from{opacity:0;transform:translateX(-50%) translateY(14px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}

        /* ── RESPONSIVE ── */
        @media(min-width:1400px){.grid{grid-template-columns:repeat(4,1fr)}}
        @media(min-width:1060px) and (max-width:1399px){.grid{grid-template-columns:repeat(3,1fr)}}
        @media(min-width:720px) and (max-width:1059px){.grid{grid-template-columns:repeat(2,1fr)}}

        @media(max-width:840px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .sb-overlay{display:block}
            .sb-overlay.open{opacity:1;pointer-events:auto}
            .main{margin-left:0}
            .topbar{padding:0 16px}
            .tb-burger{display:flex}
            .body{padding:16px 14px 48px}
            .ctrl-row{grid-template-columns:1fr}
            .date-card{flex-wrap:wrap}
        }
        @media(max-width:600px){.grid{grid-template-columns:1fr}}
        @media(max-width:500px){.bulk-ctrls{flex-direction:column;align-items:stretch}.bsel,.btn-apply{width:100%}}
    </style>
</head>
<body>
<aside class="sidebar" id="sidebar">
    <div class="sb-brand">
        <div class="sb-logo"><img src="{{ asset('logo.png') }}" alt="Logo"></div>
        <div>
            <div class="sb-name">{{ session('user.username', '-') }}</div>
            <div class="sb-role">User</div>
        </div>
    </div>
    <div class="sb-section">
        <div class="sb-lbl">Presensi Sholat</div>
        <ul class="sb-nav">
            <li><a href="{{ route('presensi-sholat.qr') }}" class="sb-link"><span class="sb-ico"><i class="fas fa-qrcode"></i></span>Presensi Sholat</a></li>
            <li><a href="{{ route('presensi-haid.qr') }}" class="sb-link"><span class="sb-ico"><i class="fas fa-qrcode"></i></span>Presensi Haid</a></li>
            <li><a href="{{ route('presensi.log-marifah') }}" class="sb-link"><span class="sb-ico"><i class="fas fa-rectangle-list"></i></span>Log Marifah</a></li>
            <li><a href="{{ route('presensi.log-presensi') }}" class="sb-link"><span class="sb-ico"><i class="fas fa-square-check"></i></span>Log Presensi</a></li>
            <li><a href="{{ route('presensi.kelola') }}" class="sb-link active"><span class="sb-ico"><i class="fas fa-arrows-rotate"></i></span>Kelola Presensi</a></li>
            <li><a href="{{ route('presensi.rekap-sholat') }}" class="sb-link"><span class="sb-ico"><i class="fas fa-chart-simple"></i></span>Rekap Sholat</a></li>
            <li><a href="{{ route('presensi.account.ganti-password') }}" class="sb-link"><span class="sb-ico"><i class="fas fa-gear"></i></span>Account Controls</a></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sb-link"><span class="sb-ico"><i class="fas fa-right-from-bracket"></i></span>Log Out</button>
                </form>
            </li>
        </ul>
    </div>
    <div class="sb-foot">App Ver : 1.2.1</div>
</aside>
<div class="sb-overlay" id="sbOverlay"></div>

<div class="main">
    <header class="topbar">
        <button class="tb-burger" id="sbToggle" type="button"><i class="fas fa-bars"></i></button>
        <span class="tb-title">Kelola Presensi</span>
        <a href="#" class="tb-btn" id="refreshBtn" title="Refresh"><i class="fas fa-rotate-right"></i></a>
    </header>

    <div class="body">
        @if(session('error'))<div class="flash flash-err"><i class="fas fa-circle-exclamation"></i>{{ session('error') }}</div>@endif
        @if(session('success'))<div class="flash flash-ok"><i class="fas fa-check-circle"></i>{{ session('success') }}</div>@endif
        @if($error)<div class="flash flash-err"><i class="fas fa-circle-exclamation"></i>{{ $error }}</div>@endif

        <form method="GET" action="{{ route('presensi.kelola') }}">
            <div class="ctrl-row">
                <div class="date-card">
                    <div>
                        <div class="date-lbl">Tanggal</div>
                        <div class="date-val" id="dateDisplay">{{ $tanggal === $today ? 'Hari ini' : $tanggal }}</div>
                    </div>
                    <input type="date" name="tanggal" id="tanggalPicker" value="{{ $tanggal }}" max="{{ $today }}" style="display:none;">
                    <button type="button" class="btn-date" onclick="document.getElementById('tanggalPicker').showPicker&&document.getElementById('tanggalPicker').showPicker()">
                        <i class="fas fa-calendar-days"></i> Pilih Tanggal
                    </button>
                </div>
                <div class="search-box">
                    <span class="search-ico"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="search-inp" placeholder="Cari nama atau NIS…" autocomplete="off">
                </div>
            </div>
        </form>

        <div class="bulk">
            <div class="bulk-head">
                <span class="bulk-lbl">Mass Update</span>
                <span class="bulk-badge" id="selectedMeta">0 terpilih</span>
            </div>
            <div class="bulk-ctrls">
                <select id="filterMusyrifah" class="bsel"><option value="">Semua musyrifah</option></select>
                <select id="filterUnit" class="bsel"><option value="">Semua unit</option></select>
                <select id="sortUnit" class="bsel">
                    <option value="unit_asc">Unit A–Z</option>
                    <option value="unit_desc">Unit Z–A</option>
                </select>
                <select id="bulkSession" class="bsel">
                    <option value="1">Sesi 1</option><option value="2">Sesi 2</option>
                    <option value="3">Sesi 3</option><option value="4">Sesi 4</option><option value="5">Sesi 5</option>
                </select>
                <select id="bulkStatus" class="bsel">
                    <option value="Sholat">Sholat</option><option value="Izin">Izin</option>
                    <option value="Haid">Haid</option><option value="Sakit">Sakit</option><option value="Alpa">Alpa</option>
                </select>
                <button type="button" class="btn-apply" id="btnBulkApply"><i class="fas fa-check"></i> Terapkan</button>
            </div>
        </div>

        <div class="sec"><span class="sec-txt">Siswa</span><span class="sec-line"></span></div>

        <div class="list-wrap">
            <div class="page-loader" id="pageLoader">
                <div class="spinner"></div>
                <span class="loader-txt">Memuat data…</span>
            </div>
            <div class="grid" id="studentList"></div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalOverlay">
    <div class="modal-box">
        <div class="modal-title" id="modalTitle">Edit Presensi</div>
        <div class="modal-sub" id="modalSub"></div>
        <form id="editForm">
            @csrf
            <input type="hidden" name="id" id="editId">
            <input type="hidden" name="session" id="editSession">
            <input type="hidden" name="status" id="editStatus">
            <input type="hidden" name="tanggal" id="editTanggal" value="{{ $tanggal }}">
            <div class="modal-opts">
                <button type="button" class="modal-opt" data-status="Sholat"><span class="opt-dot" style="background:var(--g)"></span>Sholat</button>
                <button type="button" class="modal-opt" data-status="Izin"><span class="opt-dot" style="background:var(--y)"></span>Izin</button>
                <button type="button" class="modal-opt" data-status="Haid"><span class="opt-dot" style="background:var(--g)"></span>Haid</button>
                <button type="button" class="modal-opt" data-status="Sakit"><span class="opt-dot" style="background:var(--p)"></span>Sakit</button>
                <button type="button" class="modal-opt" data-status="Alpa"><span class="opt-dot" style="background:var(--r)"></span>Alpa</button>
            </div>
            <button type="button" class="modal-close" id="modalCloseBtn">Tutup</button>
        </form>
    </div>
</div>

<script>
(function(){
    var T='{{ $tanggal }}',TODAY='{{ $today }}';
    var DURLS=['{{ route("presensi.kelola.data",[],false) }}','/kelola-presensi/data'];
    var UURL='{{ route("presensi.kelola.update",[],false) }}';

    function showLoad(){document.getElementById('pageLoader').classList.remove('hidden')}
    function hideLoad(){document.getElementById('pageLoader').classList.add('hidden')}
    function setDate(t){document.getElementById('dateDisplay').textContent=t===TODAY?'Hari ini':t}

    function fixChips(root){
        var LABEL={
            'SHOLAT':'Sh','ALPA':'Alpa','IZIN':'Izin',
            'SAKIT':'Skt','HAID':'H','BELUM PRESENSI':'–','BELUM':'–'
        };

        root.querySelectorAll('.card-student').forEach(function(card){
            if(card.dataset.fixed)return;
            card.dataset.fixed='1';

            /* Wrap inner content in .card-inner */
            if(!card.querySelector('.card-inner')){
                var inner=document.createElement('div');
                inner.className='card-inner';
                while(card.firstChild)inner.appendChild(card.firstChild);
                card.appendChild(inner);
            }

            /* Upgrade .card-sub to show unit as badge */
            var sub=card.querySelector('.card-sub');
            if(sub&&!sub.querySelector('.unit-badge')){
                var txt=(sub.textContent||'').replace('Unit:','').trim();
                if(txt){
                    sub.innerHTML='<span class="unit-badge">'+txt+'</span>';
                }
            }

            /* Wrap chips */
            if(!card.querySelector('.sholat-chips')){
                var chips=Array.from(card.querySelectorAll('.chip'));
                if(chips.length){
                    var wrap=document.createElement('div');
                    wrap.className='sholat-chips';
                    chips[0].parentNode.insertBefore(wrap,chips[0]);
                    chips.forEach(function(c){wrap.appendChild(c)});
                }
            }
        });

        /* Shorten chip labels */
        root.querySelectorAll('.chip').forEach(function(chip){
            var bot=chip.querySelector('.chip-bottom');
            if(!bot)return;
            var txt=(bot.textContent||'').trim().toUpperCase();
            var mapped=LABEL[txt];
            if(mapped)bot.textContent=mapped;
            else if(txt.length>5)bot.textContent=txt.slice(0,4);
        });
    }

    function rebuildFilters(){
        var m=new Set(),u=new Set();
        var meta=document.getElementById('meta-filters');
        if(meta){
            var uStr=meta.dataset.units||'',mStr=meta.dataset.musyrifah||'';
            if(uStr)uStr.split('||').forEach(function(v){v=v.trim();if(v)u.add(v)});
            if(mStr)mStr.split('||').forEach(function(v){v=v.trim();if(v)m.add(v)});
        }else{
            document.querySelectorAll('.card-student').forEach(function(c){
                var mv=(c.dataset.musyrifah||'').trim();
                var uv=(c.dataset.unit||'').trim();
                if(mv)m.add(mv);if(uv)u.add(uv);
            });
        }
        [{id:'filterMusyrifah',d:m},{id:'filterUnit',d:u}].forEach(function(o){
            var s=document.getElementById(o.id),k=s.value;
            while(s.options.length>1)s.remove(1);
            Array.from(o.d).sort().forEach(function(v){
                var op=document.createElement('option');
                op.value=v;op.textContent=v;s.appendChild(op);
            });
            s.value=k;
        });
    }

    function upMeta(){
        var n=document.querySelectorAll('.card-checkbox:checked').length;
        document.getElementById('selectedMeta').textContent=n+' terpilih';
    }

    function syncChk(){
        document.querySelectorAll('.card-student').forEach(function(c){
            var cb=c.querySelector('.card-checkbox');
            if(cb)c.classList.toggle('chk',cb.checked);
        });
    }

    function tryFetch(urls,q,i){
        i=i||0;if(i>=urls.length)return Promise.reject(new Error('fail'));
        return fetch((urls[i]||'')+q,{credentials:'same-origin'})
            .then(function(r){if(r.status===404)throw new Error('404');return r.text()})
            .catch(function(){return tryFetch(urls,q,i+1)});
    }

    function loadData(tanggal,page,append){
        page=page||1;append=!!append;
        var srch=(document.getElementById('searchInput').value||'').trim();
        var mus=document.getElementById('filterMusyrifah').value||'';
        var unt=document.getElementById('filterUnit').value||'';
        var srt=document.getElementById('sortUnit').value||'unit_asc';
        var q='?tanggal='+encodeURIComponent(tanggal)+'&page='+page+'&per_page=50';
        if(srch)q+='&search='+encodeURIComponent(srch);
        if(mus)q+='&musyrifah='+encodeURIComponent(mus);
        if(unt)q+='&unit='+encodeURIComponent(unt);
        if(srt)q+='&sort='+encodeURIComponent(srt);
        if(!append)showLoad();
        tryFetch(DURLS,q)
            .then(function(html){
                var list=document.getElementById('studentList');
                if(append){
                    var tmp=document.createElement('div');tmp.innerHTML=html;
                    fixChips(tmp);
                    tmp.querySelectorAll('.card-student').forEach(function(c){list.appendChild(c.cloneNode(true))});
                    var old=list.querySelector('.load-more-wrap');if(old)old.remove();
                    var lm=tmp.querySelector('.load-more-wrap');if(lm)list.appendChild(lm.cloneNode(true));
                }else{
                    list.innerHTML=html;fixChips(list);
                }
                T=tanggal;
                if(!append)setDate(tanggal);
                history.replaceState(null,'','?tanggal='+encodeURIComponent(tanggal));
                rebuildFilters();upMeta();syncChk();
            })
            .catch(function(){
                if(!append)document.getElementById('studentList').innerHTML=
                    '<div class="empty-state"><div class="empty-ico"><i class="fas fa-triangle-exclamation"></i></div><div class="empty-txt">Gagal memuat data. Refresh halaman.</div></div>';
            })
            .finally(function(){if(!append)hideLoad()});
    }

    function doUpdate(id,sess,stat,cb){
        var fd=new FormData();
        fd.append('_token',document.querySelector('input[name="_token"]').value);
        fd.append('id',id);fd.append('session',sess);fd.append('status',stat);
        fd.append('tanggal',T);fd.append('_ajax','1');
        fetch(UURL,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
            .then(function(r){return r.json().catch(function(){return{ok:false}})})
            .then(function(d){if(cb)cb(null,d)})
            .catch(function(e){if(cb)cb(e,null)});
    }

    function toast(msg,err){
        var old=document.querySelector('.toast');if(old)old.remove();
        var t=document.createElement('div');
        t.className='toast '+(err?'error':'success');
        t.innerHTML='<i class="fas '+(err?'fa-circle-exclamation':'fa-check-circle')+'"></i>'+msg;
        document.body.appendChild(t);
        setTimeout(function(){t.remove()},3500);
    }

    document.addEventListener('DOMContentLoaded',function(){
        loadData(T);

        document.getElementById('tanggalPicker').addEventListener('change',function(){if(this.value)loadData(this.value)});
        document.getElementById('refreshBtn').addEventListener('click',function(e){e.preventDefault();loadData(T)});

        var st;
        document.getElementById('searchInput').addEventListener('input',function(){
            clearTimeout(st);st=setTimeout(function(){loadData(T,1,false)},300);
        });

        ['filterMusyrifah','filterUnit','sortUnit'].forEach(function(id){
            document.getElementById(id).addEventListener('change',function(){loadData(T,1,false)});
        });

        document.getElementById('btnBulkApply').addEventListener('click',function(){
            var ids=[];
            document.querySelectorAll('.card-student').forEach(function(c){
                var cb=c.querySelector('.card-checkbox');
                if(cb&&cb.checked){
                    var b=c.querySelector('.chip-edit,.btn-edit-presensi,.chip[data-id]');
                    if(!b){var chips=c.querySelectorAll('.chip');if(chips.length)b=chips[0];}
                    if(b&&b.dataset.id)ids.push({id:b.dataset.id});
                }
            });
            if(!ids.length){toast('Pilih minimal 1 siswa dulu.',true);return}
            var sess=document.getElementById('bulkSession').value||'1';
            var stat=document.getElementById('bulkStatus').value||'Sholat';
            var btn=document.getElementById('btnBulkApply');
            btn.disabled=true;
            var total=ids.length,done=0,ok=0,errMsg=null;
            ids.forEach(function(item){
                doUpdate(item.id,sess,stat,function(err,data){
                    done++;if(!err&&data&&data.ok)ok++;
                    else if(data&&data.message)errMsg=data.message;
                    if(done===total){
                        btn.disabled=false;loadData(T,1,false);
                        if(ok===total)toast('Berhasil update '+ok+' siswa.');
                        else if(ok>0)toast('Berhasil '+ok+'/'+total+'.'+(errMsg?' '+errMsg:''));
                        else toast(errMsg||'Update gagal.',true);
                    }
                });
            });
        });

        var modal=document.getElementById('modalOverlay');
        var editId=document.getElementById('editId');
        var editSess=document.getElementById('editSession');
        var editTgl=document.getElementById('editTanggal');
        var mTitle=document.getElementById('modalTitle');
        var mSub=document.getElementById('modalSub');

        document.getElementById('modalOverlay').addEventListener('click',function(e){
            var btn=e.target.closest('.modal-opt[data-status]');
            if(!btn)return;
            var stat=btn.dataset.status;
            var ids=(window._mids&&window._mids.length)?window._mids:[{id:editId.value}];
            if(!ids.length||!ids[0].id){toast('ID tidak ditemukan.',true);return;}
            modal.classList.remove('open');
            var total=ids.length,done=0,ok=0,errMsg=null;
            ids.forEach(function(item){
                doUpdate(item.id,editSess.value,stat,function(err,data){
                    done++;
                    if(!err&&data&&data.ok)ok++;
                    else if(data&&data.message)errMsg=data.message;
                    if(done===total){
                        loadData(T);
                        if(ok===total)toast(total>1?'Berhasil update '+ok+' presensi.':'Presensi diperbarui.');
                        else if(ok>0)toast('Berhasil '+ok+'/'+total+'.'+(errMsg?' '+errMsg:''));
                        else toast(errMsg||'Gagal update.',true);
                    }
                });
            });
        });

        document.getElementById('studentList').addEventListener('click',function(e){
            var cb=e.target.closest('.card-checkbox');
            if(cb){upMeta();syncChk();return}

            var lmb=e.target.closest('.btn-load-more');
            if(lmb&&!lmb.disabled){
                var wrap=lmb.closest('.load-more-wrap');
                var np=parseInt(wrap.dataset.page||'1',10)+1;
                lmb.disabled=true;lmb.innerHTML='<i class="fas fa-spinner fa-spin"></i> Memuat…';
                loadData(T,np,true);return;
            }

            var chip=e.target.closest('.chip-edit,.btn-edit-presensi,.chip[data-id]');
            if(!chip){
                var anyChip=e.target.closest('.chip');
                if(anyChip&&anyChip.dataset&&anyChip.dataset.id)chip=anyChip;
            }
            if(chip){
                e.preventDefault();
                var card=chip.closest('.card-student');
                var checked=Array.from(document.querySelectorAll('.card-student')).filter(function(c){
                    var x=c.querySelector('.card-checkbox');return x&&x.checked;
                });
                if(checked.length>1&&card&&card.querySelector('.card-checkbox:checked')){
                    window._mids=checked.map(function(c){
                        var b=c.querySelector('.chip-edit,.btn-edit-presensi,.chip[data-id]');
                        if(!b){var ch=c.querySelectorAll('.chip');if(ch.length)b=ch[0];}
                        return b&&b.dataset.id?{id:b.dataset.id}:null;
                    }).filter(Boolean);
                }else{window._mids=[]}
                editId.value=chip.dataset.id||'';
                editSess.value=chip.dataset.session||'';
                editTgl.value=T;
                mTitle.textContent='Edit Presensi — Sholat '+(chip.dataset.session||'');
                var n=window._mids.length;
                mSub.textContent=n>1?n+' siswa terpilih (update jamak)':((chip.dataset.name||'')+(chip.dataset.unit?' · '+chip.dataset.unit:''));
                modal.classList.add('open');
            }
        });

        document.getElementById('modalCloseBtn').addEventListener('click',function(){modal.classList.remove('open')});
        modal.addEventListener('click',function(e){if(e.target===modal)modal.classList.remove('open')});

        var sb=document.getElementById('sidebar');
        var sbo=document.getElementById('sbOverlay');
        document.getElementById('sbToggle').addEventListener('click',function(){sb.classList.toggle('open');sbo.classList.toggle('open')});
        sbo.addEventListener('click',function(){sb.classList.remove('open');sbo.classList.remove('open')});
    });
})();
</script>
</body>
</html>