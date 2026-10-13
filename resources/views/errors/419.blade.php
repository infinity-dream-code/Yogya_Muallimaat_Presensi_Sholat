<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ $csrf ?? csrf_token() }}">
    <title>Memuat ulang</title>
    <style>
        body { font-family: system-ui, sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; color:#334155; background:#f8fafc; }
        p { font-size: 0.95rem; }
    </style>
</head>
<body>
    <p>Memuat ulang…</p>
    @php
        $action = $retry_action ?? null;
        $method = strtoupper($retry_method ?? 'GET');
        $inputs = is_array($retry_inputs ?? null) ? $retry_inputs : [];
        $token = $csrf ?? csrf_token();
    @endphp

    @if ($action && $method !== 'GET' && $method !== 'HEAD')
        <form id="retryForm" method="POST" action="{{ $action }}" style="display:none">
            <input type="hidden" name="_token" value="{{ $token }}">
            @if (!in_array($method, ['POST', 'GET'], true))
                <input type="hidden" name="_method" value="{{ $method }}">
            @endif
            @foreach ($inputs as $name => $value)
                @if (is_array($value))
                    @foreach ($value as $v)
                        <input type="hidden" name="{{ $name }}[]" value="{{ is_scalar($v) ? $v : '' }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $name }}" value="{{ is_scalar($value) ? $value : '' }}">
                @endif
            @endforeach
        </form>
        <script>
        (function () {
            var key = 'muallimat_419_post_' + location.pathname;
            try {
                if (sessionStorage.getItem(key) === '1') {
                    sessionStorage.removeItem(key);
                    if (document.referrer) location.replace(document.referrer);
                    else location.replace(@json(url('/admin')));
                    return;
                }
                sessionStorage.setItem(key, '1');
            } catch (e) {}
            var form = document.getElementById('retryForm');
            if (form) form.submit();
        })();
        </script>
    @else
        <script>
        (function () {
            var keepAliveUrl = @json(url('/session/keep-alive'));
            var key = 'muallimat_419_retry';

            function goBack() {
                if (document.referrer) location.replace(document.referrer);
                else location.replace(@json(url('/admin')));
            }

            try {
                if (sessionStorage.getItem(key) === '1') {
                    sessionStorage.removeItem(key);
                    goBack();
                    return;
                }
                sessionStorage.setItem(key, '1');
            } catch (e) {}

            fetch(keepAliveUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store'
            }).then(function () { goBack(); }).catch(function () { goBack(); });
        })();
        </script>
    @endif
</body>
</html>
