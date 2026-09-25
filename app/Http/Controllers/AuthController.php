<?php

namespace App\Http\Controllers;

use App\Models\CyberKey;
use App\Services\LaporanSsoService;
use App\Support\PersistentLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    private const API_BASE_URL_PRESENSI_SHOLAT = 'http://vps1.smartpayment.co.id:8888/Data/Yogya_Muallimaat_PresensiSholat/WebAPI.php';
    private const JWT_SECRET = 'a7c2a8a9b3c4a5a6a7a8a9b0c1a2a3';

    public function showLogin()
    {
        PersistentLogin::restoreIntoSession(request());

        if (session()->has('user') && session('user.username')) {
            return redirect()->route('admin.index');
        }

        return view('login');
    }

    public function logout(Request $request)
    {
        PersistentLogin::forget();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.form');
    }

    public function login(Request $request)
    {
        $turnstileEnabled = !empty(config('services.cloudflare_turnstile.site_key')) &&
            !empty(config('services.cloudflare_turnstile.secret_key'));

        $validated = $request->validate([
            'app' => ['required', 'in:presensi-sholat,aplikasi-laporan,approval-prestasi,catatan-kepribadian,tahfid'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'cf-turnstile-response' => [$turnstileEnabled ? 'required' : 'nullable', 'string'],
        ]);

        if ($turnstileEnabled && ! $this->verifyTurnstile($validated['cf-turnstile-response'] ?? null, $request->ip())) {
            return back()
                ->withInput($request->except('password'))
                ->with('login_error', 'Verifikasi keamanan gagal. Silakan ulangi captcha.');
        }

        Log::info('Login attempt received', [
            'app' => $validated['app'],
            'username' => $validated['username'],
        ]);

        if ($validated['app'] === 'aplikasi-laporan') {
            return $this->loginLaporan($validated);
        }
        if ($validated['app'] === 'approval-prestasi') {
            return $this->loginStaffWs($request, $validated, 'approval-prestasi', 'approval.prestasi.index', 'Approval Prestasi');
        }
        if ($validated['app'] === 'catatan-kepribadian') {
            return $this->loginStaffWs($request, $validated, 'catatan-kepribadian', 'catatan.kepribadian.index', 'Catatan Kepribadian Siswi');
        }
        if ($validated['app'] === 'tahfid') {
            return $this->loginTahfid($request, $validated);
        }

        return $this->loginPresensiSholat($request, $validated);
    }

    private function loginStaffWs(Request $request, array $validated, string $app, string $homeRoute, string $appLabel)
    {
        $username = trim($validated['username']);
        $password = $validated['password'];
        $wsUrl = rtrim((string) env('APPROVAL_WS_URL', 'http://103.23.103.43/ws_client/mualimat_reward/index.php'), '/');
        $wsRequest = [
            'method' => 'loginApproval',
            'username' => $username,
        ];

        Log::info('Staff WS login request', [
            'url' => $wsUrl,
            'app' => $app,
            'request' => $wsRequest,
            'ip' => $request->ip(),
        ]);

        try {
            $response = Http::timeout(20)->post($wsUrl, [
                'method' => 'loginApproval',
                'username' => $username,
                'password' => $password,
            ]);
        } catch (\Throwable $e) {
            Log::error('Staff WS login failed', ['app' => $app, 'message' => $e->getMessage()]);
            return back()
                ->withInput(['app' => $app, 'username' => $username])
                ->with('login_error', 'Tidak dapat terhubung ke server. Silakan coba lagi.');
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            $payload = [];
        }
        $wsStatus = (int) ($payload['status'] ?? 0);
        $wsMessage = trim((string) ($payload['message'] ?? ''));
        $rawBody = (string) $response->body();

        if ($wsStatus === 0 || $this->wsBodyLooksBroken($rawBody, $payload)) {
            Log::warning('Staff WS login empty/invalid JSON', [
                'url' => $wsUrl,
                'app' => $app,
                'status' => $response->status(),
                'body' => substr($rawBody, 0, 1000),
                'username' => $username,
            ]);

            return back()
                ->withInput(['app' => $app, 'username' => $username])
                ->with('login_error', 'Server sedang bermasalah. Unggah ulang file ws/index.php ke folder WS, lalu coba lagi.');
        }

        if (! $response->ok() || $wsStatus !== 200) {
            Log::warning('Staff WS login HTTP non-200', [
                'url' => $wsUrl,
                'app' => $app,
                'status' => $response->status(),
                'ws_status' => $wsStatus,
                'body' => substr((string) $response->body(), 0, 1000),
                'username' => $username,
            ]);
            $message = $wsMessage !== '' ? $wsMessage : 'Username atau password salah.';
            if ($wsStatus >= 500 && $wsMessage === '') {
                $message = 'Server sedang bermasalah. Silakan coba lagi.';
            }

            return back()
                ->withInput(['app' => $app, 'username' => $username])
                ->with('login_error', $message);
        }

        Log::info('Staff WS login response', [
            'url' => $wsUrl,
            'app' => $app,
            'status' => $response->status(),
            'json' => $payload,
            'username' => $username,
        ]);
        $data = (isset($payload['data']) && is_array($payload['data'])) ? $payload['data'] : [];
        $token = trim((string) ($data['token'] ?? ''));
        $role = strtolower(trim((string) ($data['role'] ?? '')));

        if ($token === '') {
            return back()
                ->withInput(['app' => $app, 'username' => $username])
                ->with('login_error', $wsMessage !== '' ? $wsMessage : 'Username atau password salah.');
        }

        if (! in_array($role, ['musrifah', 'musyrifah', 'superadmin'], true)) {
            return back()
                ->withInput(['app' => $app, 'username' => $username])
                ->with('login_error', 'Akun ini tidak memiliki akses '.$appLabel.'.');
        }

        $request->session()->put('user', [
            'userid' => trim((string) ($data['userid'] ?? '')),
            'username' => (string) ($data['username'] ?? $username),
            'nama' => (string) ($data['nama'] ?? $username),
            'role' => (string) ($data['role'] ?? ''),
            'code01' => trim((string) ($data['code01'] ?? '')),
            'approval_token' => $token,
            'app' => $app,
        ]);
        PersistentLogin::set(session('user'));

        return redirect()->route($homeRoute);
    }

    private function wsBodyLooksBroken(string $rawBody, array $payload): bool
    {
        if (isset($payload['status']) || isset($payload['data'])) {
            return false;
        }
        $trim = ltrim($rawBody);
        if ($trim === '') {
            return true;
        }
        if (str_starts_with($trim, '{') || str_starts_with($trim, '[')) {
            return $payload === [];
        }

        return true;
    }

    private function loginTahfid(Request $request, array $validated)
    {
        $username = trim($validated['username']);
        $password = $validated['password'];
        $wsUrl = rtrim((string) env('APPROVAL_WS_URL', 'http://103.23.103.43/ws_client/mualimat_reward/index.php'), '/');

        try {
            $staffResponse = Http::timeout(20)->post($wsUrl, [
                'method' => 'loginApproval',
                'username' => $username,
                'password' => $password,
            ]);
        } catch (\Throwable $e) {
            Log::error('Tahfid staff login failed', ['message' => $e->getMessage()]);
            return back()
                ->withInput(['app' => 'tahfid', 'username' => $username])
                ->with('login_error', 'Tidak dapat terhubung ke server. Silakan coba lagi.');
        }

        $staffPayload = is_array($staffResponse->json()) ? $staffResponse->json() : [];
        $staffStatus = (int) ($staffPayload['status'] ?? 0);
        $staffData = (isset($staffPayload['data']) && is_array($staffPayload['data'])) ? $staffPayload['data'] : [];
        $staffToken = trim((string) ($staffData['token'] ?? ''));
        $staffRole = strtolower(trim((string) ($staffData['role'] ?? '')));
        $staffBody = (string) $staffResponse->body();

        if ($staffStatus === 0 || $this->wsBodyLooksBroken($staffBody, $staffPayload)) {
            Log::warning('Tahfid staff login empty/invalid JSON', [
                'status' => $staffResponse->status(),
                'body' => substr($staffBody, 0, 1000),
                'username' => $username,
            ]);

            return back()
                ->withInput(['app' => 'tahfid', 'username' => $username])
                ->with('login_error', 'Server sedang bermasalah. Unggah ulang file ws/index.php ke folder WS, lalu coba lagi.');
        }

        if ($staffResponse->ok() && $staffStatus === 200 && $staffToken !== '' && in_array($staffRole, ['musrifah', 'musyrifah', 'superadmin'], true)) {
            $request->session()->put('user', [
                'userid' => trim((string) ($staffData['userid'] ?? '')),
                'username' => (string) ($staffData['username'] ?? $username),
                'nama' => (string) ($staffData['nama'] ?? $username),
                'role' => (string) ($staffData['role'] ?? ''),
                'code01' => trim((string) ($staffData['code01'] ?? '')),
                'approval_token' => $staffToken,
                'app' => 'tahfid',
                'user_type' => 'staff',
            ]);
            PersistentLogin::set(session('user'));

            return redirect()->route('tahfid.jadwal.index');
        }

        if ($staffStatus === 403) {
            $message = trim((string) ($staffPayload['message'] ?? ''));
            return back()
                ->withInput(['app' => 'tahfid', 'username' => $username])
                ->with('login_error', $message !== '' ? $message : 'Akun ini tidak memiliki akses Aplikasi Tahfid.');
        }

        if ($staffStatus >= 500) {
            return back()
                ->withInput(['app' => 'tahfid', 'username' => $username])
                ->with('login_error', 'Server sedang bermasalah. Silakan coba lagi.');
        }

        try {
            $siswaResponse = Http::timeout(20)->post($wsUrl, [
                'method' => 'login',
                'username' => $username,
                'password' => $password,
            ]);
        } catch (\Throwable $e) {
            Log::error('Tahfid siswa login failed', ['message' => $e->getMessage()]);
            return back()
                ->withInput(['app' => 'tahfid', 'username' => $username])
                ->with('login_error', 'Tidak dapat terhubung ke server. Silakan coba lagi.');
        }

        $siswaPayload = is_array($siswaResponse->json()) ? $siswaResponse->json() : [];
        $siswaStatus = (int) ($siswaPayload['status'] ?? $siswaResponse->status());
        $siswaData = (isset($siswaPayload['data']) && is_array($siswaPayload['data'])) ? $siswaPayload['data'] : [];
        $siswaToken = trim((string) ($siswaData['token'] ?? ''));

        if (! $siswaResponse->ok() || $siswaStatus !== 200 || $siswaToken === '') {
            $message = trim((string) ($siswaPayload['message'] ?? ''));
            if ($message === '') {
                $message = trim((string) ($staffPayload['message'] ?? ''));
            }
            if ($message === '') {
                $message = 'Username atau password salah.';
            }

            return back()
                ->withInput(['app' => 'tahfid', 'username' => $username])
                ->with('login_error', $message);
        }

        $request->session()->put('user', [
            'userid' => trim((string) ($siswaData['custid'] ?? '')),
            'username' => (string) ($siswaData['nocust'] ?? $username),
            'nama' => (string) ($siswaData['nmcust'] ?? $username),
            'role' => 'siswa',
            'code01' => trim((string) ($siswaData['code01'] ?? '')),
            'kelas' => trim((string) ($siswaData['kelas'] ?? '')),
            'custid' => trim((string) ($siswaData['custid'] ?? '')),
            'nocust' => trim((string) ($siswaData['nocust'] ?? '')),
            'approval_token' => $siswaToken,
            'app' => 'tahfid',
            'user_type' => 'siswa',
        ]);
        PersistentLogin::set(session('user'));

        return redirect()->route('tahfid.siswa.index');
    }

    private function loginLaporan(array $validated)
    {
        $username = trim($validated['username']);
        $password = $validated['password'];

        try {
            $user = CyberKey::query()
                ->whereRaw('LOWER(TRIM(users)) = ?', [strtolower($username)])
                ->first();
        } catch (\Throwable $e) {
            Log::error('CyberKey lookup failed', ['message' => $e->getMessage()]);
            return back()
                ->withInput(['app' => 'aplikasi-laporan', 'username' => $username])
                ->with('login_error', 'Tidak dapat terhubung ke database. Silakan coba lagi.');
        }

        if (! $user) {
            Log::warning('Laporan login failed: user not found', ['username' => $username]);
            return back()
                ->withInput(['app' => 'aplikasi-laporan', 'username' => $username])
                ->with('login_error', 'Username atau password salah.');
        }

        $storedPassword = strtolower(trim((string) $user->password));

        if ($storedPassword === '') {
            Log::warning('Laporan login failed: empty password in cyber_key', [
                'username' => $user->users,
            ]);
            return back()
                ->withInput(['app' => 'aplikasi-laporan', 'username' => $username])
                ->with('login_error', 'Akun ini belum memiliki password. Atur password dulu di aplikasi laporan atau hubungi administrator.');
        }

        $inputHash = strtolower(md5($password));

        if ($inputHash !== $storedPassword) {
            Log::warning('Laporan login failed: password mismatch', ['username' => $user->users]);
            return back()
                ->withInput(['app' => 'aplikasi-laporan', 'username' => $username])
                ->with('login_error', 'Username atau password salah.');
        }

        try {
            $sso = app(LaporanSsoService::class);
            $token = $sso->createToken((string) $user->users);
            $redirectUrl = $sso->buildRedirectUrl($token);

            return redirect()->away($redirectUrl);
        } catch (\Throwable $e) {
            Log::error('Laporan SSO token generation failed', ['message' => $e->getMessage()]);
            return back()
                ->withInput(['app' => 'aplikasi-laporan', 'username' => $username])
                ->with('login_error', 'Gagal membuat sesi SSO. Silakan coba lagi.');
        }
    }

    private function loginPresensiSholat(Request $request, array $validated)
    {
        $apiBaseUrl = self::API_BASE_URL_PRESENSI_SHOLAT;

        $payload = [
            'METHOD'   => 'LoginRequest',
            'USERNAME' => $validated['username'],
            'PASSWORD' => $validated['password'],
        ];

        $token = $this->generateJwt($payload);

        try {
            Log::info('Sending request to external API', [
                'url' => $apiBaseUrl,
                'token_preview' => substr($token, 0, 40) . '...',
            ]);

            $response = Http::timeout(15)
                ->get($apiBaseUrl . '?token=' . urlencode($token));

            Log::info('External API response raw', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error calling external API', [
                'message' => $e->getMessage(),
            ]);
            return back()
                ->withInput($request->except('password'))
                ->with('login_error', 'Tidak dapat terhubung ke server. Silakan coba lagi.');
        }

        if (! $response->ok()) {
            return back()
                ->withInput($request->except('password'))
                ->with('login_error', 'Terjadi kesalahan pada server. Silakan coba lagi.');
        }

        $data = $response->json();

        if (isset($data['KodeRespon']) && (int) $data['KodeRespon'] === 1) {
            $username = $validated['username'];

            $request->session()->put('user', [
                'username' => $username,
                'app'      => 'presensi-sholat',
            ]);
            PersistentLogin::set(session('user'));

            return redirect()
                ->route('dashboard.presensi-sholat')
                ->with('login_success', 'Login berhasil.');
        }

        $message = $data['PesanRespon'] ?? 'Login gagal. Akses Ditolak.';

        return back()
            ->withInput($request->except('password'))
            ->with('login_error', $message);
    }

    private function generateJwt(array $payload): string
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signingInput = $headerEncoded . '.' . $payloadEncoded;
        $signature = hash_hmac('sha256', $signingInput, self::JWT_SECRET, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return $signingInput . '.' . $signatureEncoded;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function verifyTurnstile(?string $token, ?string $ipAddress = null): bool
    {
        if (empty($token)) {
            return false;
        }

        $secretKey = config('services.cloudflare_turnstile.secret_key');
        if (empty($secretKey)) {
            return true;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $ipAddress,
                ]);

            if (! $response->ok()) {
                Log::warning('Turnstile verification HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return (bool) ($response->json('success') ?? false);
        } catch (\Throwable $e) {
            Log::error('Turnstile verification exception', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function showGantiPassword()
    {
        return redirect()->route('presensi.account.ganti-password');
    }

    public function showGantiPasswordPresensi()
    {
        return view('ganti_password_presensi');
    }

    public function gantiPassword(Request $request)
    {
        $validated = $request->validate([
            'new_password' => ['required', 'string', 'min:3'],
            'confirm_password' => ['required', 'string', 'same:new_password'],
        ]);

        $username = session('user.username');
        if (!$username) {
            return back()->with('password_error', 'Data user tidak tersedia. Muat ulang halaman.');
        }

        $apiBaseUrl = self::API_BASE_URL_PRESENSI_SHOLAT;

        $payload = [
            'METHOD'       => 'RequestNewPassword',
            'USERNAME'     => $username,
            'PASSWORD'     => 'farrelganteng',
            'NEWPASSWORD'  => $validated['new_password'],
            'NEWPASSWORD2' => $validated['confirm_password'],
        ];

        Log::info('Ganti password payload', [
            'payload' => $payload,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES),
        ]);

        $token = $this->generateJwt($payload);

        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $decodedPayload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            Log::info('Ganti password decoded token payload', ['decoded' => $decodedPayload]);
        }

        try {
            Log::info('Ganti password request', [
                'username' => $username,
                'payload' => $payload,
                'token_preview' => substr($token, 0, 50) . '...',
                'token_length' => strlen($token),
            ]);

            $url = $apiBaseUrl . '?token=' . urlencode($token);
            Log::info('Ganti password API URL', [
                'url_preview' => substr($url, 0, 100) . '...',
                'url_length' => strlen($url),
            ]);

            $response = Http::timeout(15)
                ->get($url);

            if ($response->status() === 500 && empty($response->body())) {
                Log::info('Trying POST method for ganti password');

                $response = Http::timeout(15)
                    ->post($url);

                if ($response->status() === 500 && empty($response->body())) {
                    Log::info('Trying POST with token in form body');
                    $response = Http::timeout(15)
                        ->asForm()
                        ->post($apiBaseUrl, ['token' => $token]);
                }

                if ($response->status() === 500 && empty($response->body())) {
                    Log::info('Trying POST with token in JSON body');
                    $response = Http::timeout(15)
                        ->asJson()
                        ->post($apiBaseUrl, ['token' => $token]);
                }
            }

            Log::info('Ganti password API response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'body_length' => strlen($response->body()),
                'successful' => $response->ok(),
            ]);

            if (!$response->ok()) {
                $status = $response->status();
                $body = $response->body();

                $errorMsg = 'Terjadi kesalahan pada server (HTTP ' . $status . ').';

                if ($body) {
                    $jsonData = json_decode($body, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['PesanRespon'])) {
                        $errorMsg = $jsonData['PesanRespon'];
                    } else {
                        $errorMsg .= ' ' . substr($body, 0, 150);
                    }
                } else {
                    $errorMsg = 'Server API mengembalikan error tanpa pesan. Silakan coba lagi atau hubungi administrator.';
                }

                Log::error('Ganti password failed', [
                    'status' => $status,
                    'body' => $body,
                    'body_length' => strlen($body),
                    'username' => $username,
                ]);

                return back()
                    ->withInput($request->except(['new_password', 'confirm_password']))
                    ->with('password_error', $errorMsg);
            }

            $data = $response->json();

            Log::info('Ganti password response data', ['data' => $data]);

            if (isset($data['KodeRespon']) && (int) $data['KodeRespon'] === 1) {
                return back()->with('password_success', 'Password berhasil diubah.');
            }

            $message = $data['PesanRespon'] ?? 'Gagal mengubah password.';
            return back()
                ->withInput($request->except(['new_password', 'confirm_password']))
                ->with('password_error', $message);

        } catch (\Throwable $e) {
            Log::error('Error changing password', [
                'message' => $e->getMessage(),
            ]);
            return back()
                ->withInput($request->except(['new_password', 'confirm_password']))
                ->with('password_error', 'Tidak dapat terhubung ke server. Silakan coba lagi.');
        }
    }
}
