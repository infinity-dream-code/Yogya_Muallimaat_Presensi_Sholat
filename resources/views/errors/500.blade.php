<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Memuat ulang</title>
    <style>
        body { font-family: system-ui, sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; color:#334155; background:#f8fafc; }
        p { font-size: 0.95rem; }
    </style>
</head>
<body>
    <p>Memuat ulang…</p>
    <script>
    (function () {
        var key = 'muallimat_500_reload_' + location.pathname + location.search;
        try {
            if (sessionStorage.getItem(key) === '1') {
                sessionStorage.removeItem(key);
                return;
            }
            sessionStorage.setItem(key, '1');
            location.reload();
        } catch (e) {
            location.reload();
        }
    })();
    </script>
</body>
</html>
