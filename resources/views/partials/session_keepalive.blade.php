<script>
(function () {
    if (window.__muallimatKeepAliveInit) return;
    window.__muallimatKeepAliveInit = true;

    var PING_MS = 3 * 60 * 1000;
    var keepAliveUrl = @json(url('/session/keep-alive'));
    var pingInFlight = false;
    var lastPingAt = 0;

    function applyCsrf(token) {
        if (!token) return;
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', token);
        document.querySelectorAll('input[name="_token"]').forEach(function (el) {
            el.value = token;
        });
        window.__csrfToken = token;
    }

    function currentCsrf() {
        if (window.__csrfToken) return window.__csrfToken;
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function pingKeepAlive() {
        if (pingInFlight) return Promise.resolve(null);
        var now = Date.now();
        if (now - lastPingAt < 1500) return Promise.resolve(null);
        pingInFlight = true;
        lastPingAt = now;
        return fetch(keepAliveUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store'
        }).then(function (res) {
            if (res.status >= 500) {
                return new Promise(function (resolve) {
                    setTimeout(function () {
                        fetch(keepAliveUrl, {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            cache: 'no-store'
                        }).then(function (r2) { return r2.json().catch(function () { return null; }); })
                          .then(resolve)
                          .catch(function () { resolve(null); });
                    }, 400);
                });
            }
            return res.json().then(function (json) {
                if (json && json.csrf) applyCsrf(json.csrf);
                return json;
            }).catch(function () { return null; });
        }).catch(function () {
            return null;
        }).finally(function () {
            pingInFlight = false;
        });
    }

    function schedulePing() {
        setInterval(function () { pingKeepAlive(); }, PING_MS);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) pingKeepAlive();
        });
        window.addEventListener('focus', function () { pingKeepAlive(); });
    }

    var originalFetch = window.fetch.bind(window);
    window.fetch = function (input, init) {
        init = init || {};
        var method = (init.method || (input instanceof Request ? input.method : 'GET') || 'GET').toUpperCase();
        var token = currentCsrf();
        if (token && method !== 'GET' && method !== 'HEAD') {
            var headers = new Headers(init.headers || (input instanceof Request ? input.headers : undefined) || {});
            if (!headers.has('X-CSRF-TOKEN')) headers.set('X-CSRF-TOKEN', token);
            init = Object.assign({}, init, { headers: headers });
        }

        var retried419 = false;
        var retried500 = false;

        function doFetch(reqInput, reqInit) {
            return originalFetch(reqInput, reqInit).then(function (res) {
                if (res.status === 419 && !retried419) {
                    retried419 = true;
                    return pingKeepAlive().then(function (json) {
                        var fresh = (json && json.csrf) ? json.csrf : currentCsrf();
                        if (!fresh) return res;
                        var retryInit = Object.assign({}, reqInit || {});
                        var headers = new Headers(retryInit.headers || {});
                        headers.set('X-CSRF-TOKEN', fresh);
                        headers.set('X-Requested-With', 'XMLHttpRequest');
                        retryInit.headers = headers;
                        retryInit.credentials = retryInit.credentials || 'same-origin';
                        return doFetch(reqInput, retryInit);
                    });
                }
                if (res.status >= 500 && method === 'GET' && !retried500) {
                    retried500 = true;
                    return new Promise(function (resolve) {
                        setTimeout(function () {
                            doFetch(reqInput, reqInit).then(resolve);
                        }, 350);
                    });
                }
                return res;
            });
        }

        return doFetch(input, init);
    };

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') return;
        var method = (form.getAttribute('method') || 'GET').toUpperCase();
        if (method === 'GET') return;
        var token = currentCsrf();
        if (!token) return;
        var field = form.querySelector('input[name="_token"]');
        if (field) field.value = token;
    }, true);

    schedulePing();
    pingKeepAlive();
})();
</script>
