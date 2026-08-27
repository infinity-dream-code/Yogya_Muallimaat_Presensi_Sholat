@extends('layouts.approval')

@section('title', 'Kelola Admin')
@section('heading', 'Kelola Admin')

@section('content')
    @php
        $adminStoreRoute = $adminStoreRoute ?? 'approval.admin.store';
        $adminUpdateRoute = $adminUpdateRoute ?? 'approval.admin.update';
        $adminDeleteRoute = $adminDeleteRoute ?? 'approval.admin.delete';
        $roleOptions = [];
        $schoolOptions = [];
        foreach ($users as $row) {
            $role = trim((string) ($row['role'] ?? ''));
            $school = strtolower($role) === 'superadmin'
                ? 'Semua sekolah'
                : (trim((string) ($row['sekolah'] ?? '')) !== '' ? $row['sekolah'] : (trim((string) ($row['code01'] ?? '')) ?: '-'));
            if ($role !== '') {
                $roleOptions[$role] = $role;
            }
            $schoolOptions[$school] = $school;
        }
        ksort($roleOptions);
        ksort($schoolOptions);
    @endphp

    <div class="panel table-card">
        <div class="filters">
            <div class="filters-grid" style="grid-template-columns: 1.4fr .9fr 1fr auto;">
                <div class="field">
                    <label for="adminQ">Cari</label>
                    <input id="adminQ" type="text" placeholder="Nama atau username...">
                </div>
                <div class="field">
                    <label for="adminRole">Hak akses</label>
                    <select id="adminRole">
                        <option value="">Semua</option>
                        @foreach($roleOptions as $opt)
                            <option value="{{ $opt }}">{{ strtolower($opt) === 'superadmin' ? 'Superadmin' : $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="adminSchool">Sekolah</label>
                    <select id="adminSchool">
                        <option value="">Semua sekolah</option>
                        @foreach($schoolOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="btn btn-ghost" id="adminReset">Reset</button>
            </div>
        </div>
        <div class="table-toolbar">
            <span class="count-pill" id="adminCount">{{ count($users) }} akun</span>
            <button type="button" class="btn btn-primary" id="btnAdd"><i class="fas fa-plus"></i> Tambah Admin</button>
        </div>
        <div class="table-wrap">
            @if(count($users) === 0)
                <div class="empty">
                    <div><i class="fas fa-user-slash"></i></div>
                    Belum ada akun Musrifah atau superadmin.
                </div>
            @else
                <table class="data" id="adminTable">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Hak akses</th>
                            <th>Sekolah</th>
                            <th style="width:160px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="adminBody">
                    @foreach($users as $user)
                        @php
                            $role = trim((string) ($user['role'] ?? ''));
                            $isSa = strtolower($role) === 'superadmin';
                            $isSelf = (string) ($user['id'] ?? '') === (string) session('user.userid', '');
                            $schoolLabel = $isSa ? 'Semua sekolah' : (trim((string) ($user['sekolah'] ?? '')) !== '' ? $user['sekolah'] : ($user['code01'] ?: '-'));
                            $initials = strtoupper(mb_substr(trim((string) ($user['nama'] ?? 'A')), 0, 1));
                        @endphp
                        <tr
                            class="data-row"
                            data-nama="{{ mb_strtolower((string) ($user['nama'] ?? '')) }}"
                            data-username="{{ mb_strtolower((string) ($user['username'] ?? '')) }}"
                            data-role="{{ $role }}"
                            data-sekolah="{{ $schoolLabel }}"
                        >
                            <td>
                                <div class="cell-user">
                                    <span class="avatar">{{ $initials }}</span>
                                    <div class="cell-main">{{ $user['nama'] ?: '-' }}</div>
                                </div>
                            </td>
                            <td class="mono">{{ $user['username'] ?: '-' }}</td>
                            <td>
                                <span class="badge {{ $isSa ? 'role-sa' : 'role-ms' }}">{{ $isSa ? 'Superadmin' : 'Musrifah' }}</span>
                            </td>
                            <td>{{ $schoolLabel }}</td>
                            <td>
                                <div class="actions">
                                    <button
                                        type="button"
                                        class="btn btn-ghost btn-sm btn-edit"
                                        data-id="{{ $user['id'] }}"
                                        data-username="{{ $user['username'] }}"
                                        data-nama="{{ $user['nama'] }}"
                                        data-role="{{ $isSa ? 'superadmin' : 'Musrifah' }}"
                                        data-code01="{{ $user['code01'] }}"
                                    ><i class="fas fa-pen"></i> Edit</button>
                                    @if(!$isSelf)
                                        <form method="POST" action="{{ route($adminDeleteRoute) }}" class="js-confirm" data-title="Hapus admin?" data-text="Akun {{ $user['username'] }} akan dihapus.">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $user['id'] }}">
                                            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="empty" id="adminEmpty" style="display:none;">Tidak ada admin yang cocok dengan filter.</div>
        @endif
        </div>
    </div>

    <div class="modal-bg" id="adminModal">
        <div class="modal">
            <h3 id="modalTitle">Tambah Admin</h3>
            <form method="POST" id="adminForm">
                @csrf
                <input type="hidden" name="id" id="fId">
                <div class="modal-grid">
                    <div class="field">
                        <label for="fNama">Nama</label>
                        <input id="fNama" name="nama" type="text" maxlength="50" required>
                    </div>
                    <div class="field">
                        <label for="fUsername">Username</label>
                        <input id="fUsername" name="username" type="text" maxlength="50" required>
                    </div>
                    <div class="field">
                        <label for="fPassword">Password</label>
                        <input id="fPassword" name="password" type="password" minlength="4" maxlength="128" autocomplete="new-password">
                        <div class="cell-sub" id="passwordHint">Minimal 4 karakter.</div>
                    </div>
                    <div class="field">
                        <label for="fRole">Hak akses</label>
                        <select id="fRole" name="role" required>
                            <option value="Musrifah">Musrifah (satu sekolah)</option>
                            <option value="superadmin">Superadmin (semua sekolah)</option>
                        </select>
                    </div>
                    <div class="field" id="schoolField">
                        <label for="fCode01">Sekolah</label>
                        <select id="fCode01" name="code01">
                            <option value="">Pilih sekolah</option>
                            @foreach($sekolah as $row)
                                <option value="{{ $row['code01'] }}">{{ $row['sekolah'] }} ({{ $row['code01'] }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-ghost" id="btnCancel">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    var modal = document.getElementById('adminModal');
    var form = document.getElementById('adminForm');
    var title = document.getElementById('modalTitle');
    var hint = document.getElementById('passwordHint');
    var role = document.getElementById('fRole');
    var schoolField = document.getElementById('schoolField');
    var code01 = document.getElementById('fCode01');
    var password = document.getElementById('fPassword');
    var storeUrl = @json(route($adminStoreRoute));
    var updateUrl = @json(route($adminUpdateRoute));

    function toggleSchool() {
        var needSchool = role.value === 'Musrifah';
        schoolField.style.display = needSchool ? 'block' : 'none';
        code01.required = needSchool;
        if (!needSchool) code01.value = '';
    }

    function openModal(mode, data) {
        form.reset();
        document.getElementById('fId').value = '';
        if (mode === 'edit') {
            title.textContent = 'Edit Admin';
            form.action = updateUrl;
            document.getElementById('fId').value = data.id;
            document.getElementById('fNama').value = data.nama || '';
            document.getElementById('fUsername').value = data.username || '';
            role.value = data.role || 'Musrifah';
            code01.value = data.code01 || '';
            password.required = false;
            hint.textContent = 'Kosongkan jika tidak ingin mengubah password.';
        } else {
            title.textContent = 'Tambah Admin';
            form.action = storeUrl;
            password.required = true;
            hint.textContent = 'Minimal 4 karakter.';
        }
        toggleSchool();
        modal.classList.add('open');
    }

    document.getElementById('btnAdd').addEventListener('click', function () { openModal('create'); });
    document.getElementById('btnCancel').addEventListener('click', function () { modal.classList.remove('open'); });
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.classList.remove('open'); });
    role.addEventListener('change', toggleSchool);

    document.querySelectorAll('.btn-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal('edit', {
                id: btn.getAttribute('data-id'),
                username: btn.getAttribute('data-username'),
                nama: btn.getAttribute('data-nama'),
                role: btn.getAttribute('data-role'),
                code01: btn.getAttribute('data-code01')
            });
        });
    });

    var body = document.getElementById('adminBody');
    var qEl = document.getElementById('adminQ');
    var roleEl = document.getElementById('adminRole');
    var schEl = document.getElementById('adminSchool');
    var countEl = document.getElementById('adminCount');
    var emptyEl = document.getElementById('adminEmpty');
    var table = document.getElementById('adminTable');
    var collapsed = {};

    function applyAdminFilter() {
        if (!body) return;
        var q = (qEl && qEl.value || '').trim().toLowerCase();
        var role = roleEl ? roleEl.value : '';
        var school = schEl ? schEl.value : '';
        body.querySelectorAll('tr.group-row').forEach(function (el) { el.remove(); });
        var rows = Array.prototype.slice.call(body.querySelectorAll('tr.data-row'));
        var visible = [];
        rows.forEach(function (row) {
            var ok = true;
            if (q && row.getAttribute('data-nama').indexOf(q) === -1 && row.getAttribute('data-username').indexOf(q) === -1) ok = false;
            if (ok && role && row.getAttribute('data-role') !== role) ok = false;
            if (ok && school && row.getAttribute('data-sekolah') !== school) ok = false;
            row.style.display = ok ? '' : 'none';
            if (ok) visible.push(row);
        });
        var map = {};
        var order = [];
        visible.forEach(function (row) {
            var key = row.getAttribute('data-sekolah') || 'Lainnya';
            if (!map[key]) { map[key] = []; order.push(key); }
            map[key].push(row);
        });
        order.sort(function (a, b) { return a.localeCompare(b, 'id'); });
        order.forEach(function (key) {
            var tr = document.createElement('tr');
            tr.className = 'group-row';
            tr.setAttribute('data-group', key);
            var td = document.createElement('td');
            td.colSpan = 5;
            var open = !collapsed[key];
            td.innerHTML = '<i class="fas ' + (open ? 'fa-chevron-down' : 'fa-chevron-right') + '"></i><i class="fas fa-school"></i> ' +
                key + '<span class="group-count">' + map[key].length + '</span>';
            tr.appendChild(td);
            body.appendChild(tr);
            map[key].forEach(function (row) {
                row.style.display = open ? '' : 'none';
                body.appendChild(row);
            });
        });
        var shown = visible.filter(function (row) { return row.style.display !== 'none'; }).length;
        if (countEl) countEl.textContent = shown + ' akun';
        if (emptyEl) emptyEl.style.display = visible.length === 0 ? 'block' : 'none';
        if (table) table.style.display = visible.length === 0 ? 'none' : '';
    }

    if (body) {
        body.addEventListener('click', function (e) {
            var group = e.target.closest('tr.group-row');
            if (!group) return;
            var key = group.getAttribute('data-group');
            collapsed[key] = !collapsed[key];
            applyAdminFilter();
        });
        [qEl, roleEl, schEl].forEach(function (el) {
            if (!el) return;
            el.addEventListener('input', applyAdminFilter);
            el.addEventListener('change', applyAdminFilter);
        });
        var resetBtn = document.getElementById('adminReset');
        if (resetBtn) resetBtn.addEventListener('click', function () {
            if (qEl) qEl.value = '';
            if (roleEl) roleEl.value = '';
            if (schEl) schEl.value = '';
            collapsed = {};
            applyAdminFilter();
        });
        applyAdminFilter();
    }
})();
</script>
@endsection
