<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perizinan Khusus</title>
    @php
        $pageTitle = 'Perizinan Khusus';
    @endphp
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
        }
        .drawer {
            position: fixed;
            top: 0;
            left: 0;
            width: 272px;
            height: 100%;
            background: linear-gradient(180deg, #0f172a 0%, #020617 100%);
            color: #f9fafb;
            transform: translateX(-100%);
            transition: transform 0.2s ease;
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
            z-index: 40;
            border-right: 1px solid rgba(255,255,255,0.06);
            box-shadow: 4px 0 24px rgba(0,0,0,0.2);
        }
        .drawer.open {
            transform: translateX(0);
        }
        .drawer-backdrop.open {
            opacity: 1;
            pointer-events: auto;
        }
        .drawer-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .drawer-logo {
            width: 50px;
            height: 50px;
            border-radius: 999px;
            overflow: hidden;
            margin-right: 10px;
        }
        .drawer-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .drawer-user-name {
            font-size: 0.95rem;
            font-weight: 600;
        }
        .drawer-user-role {
            font-size: 0.78rem;
            opacity: 0.75;
        }
        .drawer-divider {
            height: 1px;
            background: #4b5563;
            margin: 16px 0;
        }
        .drawer-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            flex: 1;
        }
        .drawer-menu-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            margin-bottom: 10px;
            padding-left: 14px;
        }
        .drawer-item {
            margin-bottom: 12px;
        }
        .drawer-link {
            display: flex;
            align-items: center;
            padding: 12px 14px;
            border-radius: 999px;
            color: inherit;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.15s ease;
        }
        .drawer-link span.icon {
            width: 26px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin-right: 12px;
        }
        .drawer-link span.icon i {
            font-size: 1.05rem;
            color: #e2e8f0 !important;
        }
        .drawer-link:hover {
            background: rgba(148, 163, 184, 0.18);
        }
        .drawer-link.active {
            background: rgba(148, 163, 184, 0.15);
        }
        .drawer-link:hover span.icon i,
        .drawer-link:active span.icon i,
        .drawer-link.active span.icon i,
        .drawer-link:focus span.icon i,
        .drawer-link:visited span.icon i {
            color: #e2e8f0 !important;
        }
        .drawer-link a span.icon i,
        .drawer-link button span.icon i {
            color: #e2e8f0 !important;
        }
        .drawer-footer {
            font-size: 0.78rem;
            opacity: 0.7;
            margin-top: 12px;
        }
        .header {
            display: flex;
            align-items: center;
            padding: 16px 16px 10px;
        }
        .burger {
            width: 28px;
            margin-right: 12px;
            border: none;
            background: transparent;
            padding: 0;
            cursor: pointer;
        }
        .burger span {
            display: block;
            height: 3px;
            border-radius: 999px;
            background: #7c3aed;
            margin-bottom: 5px;
        }
        .title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #a855f7;
        }
        .content {
            padding: 40px 16px 32px;
        }
        .card {
            margin-top: 20px;
            background: #f5ebff;
            border-radius: 18px;
            padding: 16px 12px 20px;
            box-shadow: 0 12px 30px rgba(109, 40, 217, 0.15);
        }
        .form-group {
            margin-bottom: 12px;
        }
        select,
        input {
            width: 100%;
            border-radius: 14px;
            border: 1.5px solid #d0b4ff;
            padding: 12px 14px;
            font-size: 0.95rem;
            font-family: inherit;
            background: #ffffff;
            outline: none;
        }
        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }
        .search-wrapper {
            position: relative;
        }
        .search-wrapper .icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: #a1a0b8;
        }
        .search-wrapper .icon i {
            font-size: 1rem;
        }
        .btn-submit span.arrow i {
            font-size: 0.9rem;
        }
        .search-wrapper input {
            padding-left: 38px;
        }
        .search-results {
            position: absolute;
            left: 0;
            right: 0;
            top: 100%;
            margin-top: 4px;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.18);
            max-height: 260px;
            overflow-y: auto;
            z-index: 30;
        }
        .search-result-item {
            padding: 8px 12px;
            font-size: 0.9rem;
            cursor: pointer;
            border-bottom: 1px solid #f3e8ff;
        }
        .search-result-item:last-child {
            border-bottom: none;
        }
        .search-result-item:hover {
            background: #f5ebff;
        }
        .btn-submit {
            margin-top: 14px;
            width: 100%;
            border-radius: 999px;
            border: none;
            padding: 13px 18px;
            font-size: 0.98rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            color: #ffffff;
            background: linear-gradient(135deg, #a855f7, #9333ea);
            box-shadow: 0 14px 32px rgba(109, 40, 217, 0.45);
            cursor: pointer;
        }
        .btn-submit span.arrow {
            margin-right: 10px;
        }
        @media (min-width: 960px) {
            body {
                justify-content: flex-start;
            }
            .app {
                max-width: 1024px;
                margin-left: 272px;
            }
            .content {
                max-width: 520px;
                margin: 64px auto 32px;
                margin-left: max(0, calc(50vw - 532px));
            }
            .drawer {
                position: fixed;
                transform: translateX(0);
            }
            .drawer-backdrop {
                display: none;
            }
            .burger {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="app">
        <div class="header">
            <button class="burger" id="drawerToggle" type="button" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="title">{{ $pageTitle }}</div>
        </div>

        <main class="content">
            <div class="card">
                <div class="form-group">
                    <select id="izinType">
                        <option selected disabled>Pilih Jenis Perizinan</option>
                        @foreach(session('izin_types_special', []) as $izin)
                            <option value="{{ $izin['IZIN'] ?? '' }}">{{ $izin['KETIZIN'] ?? '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group search-wrapper">
                    <span class="icon"><i class="fas fa-search"></i></span>
                    <input
                        type="text"
                        id="studentSearch"
                        autocomplete="off"
                        placeholder="Cari Siswa (Nama atau NIS)">
                    <div id="studentSearchResults" class="search-results" style="display:none;"></div>
                </div>

                <div class="form-group">
                    <input
                        type="number"
                        id="hariIzin"
                        placeholder="Hari"
                        min="1"
                        autocomplete="off">
                </div>

                <div class="form-group">
                    <input
                        type="text"
                        id="keteranganIzin"
                        placeholder="Keterangan Izin"
                        autocomplete="off">
                </div>

                <button type="button" class="btn-submit">
                    <span class="arrow"><i class="fas fa-paper-plane"></i></span>
                    KIRIM PERIZINAN
                </button>
            </div>
        </main>
    </div>

    <div class="drawer-backdrop" id="drawerBackdrop"></div>
    <aside class="drawer" id="drawer">
        <div class="drawer-header">
            <div class="drawer-logo">
                <img src="{{ asset('logo.png') }}" alt="Logo">
            </div>
            <div>
                <div class="drawer-user-name">{{ session('user.username', 'farrelep') }}</div>
                <div class="drawer-user-role">Superadmin</div>
            </div>
        </div>

        <div class="drawer-divider"></div>

        <div class="drawer-menu-label">Perizinan</div>
        <ul class="drawer-menu">
            <li class="drawer-item">
                <a href="{{ route('perizinan.kedatangan') }}" class="drawer-link">
                    <span class="icon"><i class="fas fa-user-clock"></i></span>
                    <span>Kedatangan/Kepulangan</span>
                </a>
            </li>
            <li class="drawer-item">
                <a href="{{ route('perizinan.umum') }}" class="drawer-link">
                    <span class="icon"><i class="fas fa-pen-to-square"></i></span>
                    <span>Perizinan Umum</span>
                </a>
            </li>
            <li class="drawer-item">
                <a href="{{ route('perizinan.khusus') }}" class="drawer-link active">
                    <span class="icon"><i class="fas fa-user-group"></i></span>
                    <span>Perizinan Khusus</span>
                </a>
            </li>
            <li class="drawer-item">
                <a href="{{ route('laporan') }}" class="drawer-link">
                    <span class="icon"><i class="fas fa-file-lines"></i></span>
                    <span>Laporan Perizinan</span>
                </a>
            </li>
            <li class="drawer-item">
                <a href="{{ route('account.ganti-password') }}" class="drawer-link">
                    <span class="icon"><i class="fas fa-gear"></i></span>
                    <span>Account Controls</span>
                </a>
            </li>
            <li class="drawer-item">
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="drawer-link" style="width: 100%; text-align: left; border: none; background: transparent; color: inherit; cursor: pointer; font-size: inherit; font-family: inherit; padding: 12px 14px;">
                        <span class="icon"><i class="fas fa-right-from-bracket"></i></span>
                        <span>Log Out</span>
                    </button>
                </form>
            </li>
        </ul>

        <div class="drawer-footer">
            App Ver : 1.2.1
        </div>
    </aside>

    <script>
        const toggleBtn = document.getElementById('drawerToggle');
        const backdrop = document.getElementById('drawerBackdrop');
        const drawer = document.getElementById('drawer');

        function closeDrawer() {
            drawer.classList.remove('open');
            backdrop.classList.remove('open');
        }

        toggleBtn.addEventListener('click', function () {
            const isOpen = drawer.classList.contains('open');
            if (isOpen) {
                closeDrawer();
            } else {
                drawer.classList.add('open');
                backdrop.classList.add('open');
            }
        });

        backdrop.addEventListener('click', closeDrawer);

        // Live search siswa
        const searchInput = document.getElementById('studentSearch');
        const resultsBox = document.getElementById('studentSearchResults');
        const izinTypeSelect = document.getElementById('izinType');
        const hariIzinInput = document.getElementById('hariIzin');
        const keteranganIzinInput = document.getElementById('keteranganIzin');
        const submitBtn = document.querySelector('.btn-submit');
        let searchTimeout = null;
        let selectedStudent = null; // Menyimpan data siswa yang dipilih (termasuk PID)

        function clearResults() {
            resultsBox.innerHTML = '';
            resultsBox.style.display = 'none';
        }

        function renderResults(items) {
            if (!items || !items.length) {
                resultsBox.innerHTML = '';
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'search-result-item';
                emptyDiv.textContent = 'Tidak ada siswa dengan kata kunci tersebut.';
                resultsBox.appendChild(emptyDiv);
                resultsBox.style.display = 'block';
                return;
            }

            resultsBox.innerHTML = '';
            items.forEach(function (item) {
                const div = document.createElement('div');
                div.className = 'search-result-item';

                const name = item.NAMA ?? item.Nama ?? item.nama ?? item.NamaLengkap ?? item.name ?? '-';
                const nis = item.NIS ?? item.nis ?? item.nisn ?? '';
                const pid = item.PID ?? item.pid ?? item.NOKARTU ?? item.no_kartu ?? '';

                div.textContent = nis ? name + ' (' + nis + ')' : name;

                div.addEventListener('click', function () {
                    searchInput.value = nis ? name + ' (' + nis + ')' : name;
                    selectedStudent = {
                        pid: pid,
                        name: name,
                        nis: nis
                    };
                    clearResults();
                });

                resultsBox.appendChild(div);
            });

            resultsBox.style.display = 'block';
        }

        searchInput.addEventListener('input', function () {
            const q = searchInput.value.trim();
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            // Reset selected student saat user mengetik ulang
            selectedStudent = null;

            if (q.length < 2) {
                clearResults();
                return;
            }

            searchTimeout = setTimeout(function () {
                fetch(`{{ route('students.search') }}?q=` + encodeURIComponent(q), {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        renderResults(data.data || []);
                    })
                    .catch(function () {
                        console.error('Gagal mengambil data siswa');
                        clearResults();
                    });
            }, 300);
        });

        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
                clearResults();
            }
        });

        // Submit perizinan khusus
        submitBtn.addEventListener('click', function () {
            const izinType = izinTypeSelect.value;
            
            if (!izinType || izinType === '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Peringatan',
                    text: 'Silakan pilih jenis perizinan terlebih dahulu.',
                });
                return;
            }

            if (!selectedStudent || !selectedStudent.pid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Peringatan',
                    text: 'Silakan pilih siswa terlebih dahulu.',
                });
                return;
            }

            const hariIzin = hariIzinInput.value.trim();
            const keteranganIzin = keteranganIzinInput.value.trim();

            if (!hariIzin || parseInt(hariIzin) < 1) {
                Swal.fire({
                    icon: 'error',
                    title: 'Peringatan',
                    text: 'Silakan masukkan jumlah hari (minimal 1 hari).',
                });
                return;
            }

            // Disable button saat submit
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="arrow">⏳</span> MENGIRIM...';

            // Kirim request ke API
            fetch(`{{ route('perizinan.khusus.submit') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    no_kartu: selectedStudent.pid,
                    izin: izinType,
                    hari: hariIzin,
                    keterangan: keteranganIzin
                })
            })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="arrow"><i class="fas fa-paper-plane"></i></span> KIRIM PERIZINAN';

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: data.message || 'Perizinan berhasil dikirim.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(function () {
                        // Reset form
                        izinTypeSelect.value = '';
                        searchInput.value = '';
                        hariIzinInput.value = '';
                        keteranganIzinInput.value = '';
                        selectedStudent = null;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message || 'Gagal mengirim perizinan. Silakan coba lagi.',
                    });
                }
            })
            .catch(function (error) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="arrow"><i class="fas fa-paper-plane"></i></span> KIRIM PERIZINAN';
                
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Tidak dapat terhubung ke server. Silakan coba lagi.',
                });
            });
        });
    </script>
</body>
</html>
