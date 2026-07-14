<?php

namespace App\Http\Controllers;

use App\Models\CyberKey;
use App\Services\LaporanSsoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    private const API_BASE_URL_PRESENSI_SHOLAT = 'http://vps1.smartpayment.co.id:8888/Data/Yogya_Muallimaat_PresensiSholat/WebAPI.php';
    private const JWT_SECRET = 'a7c2a8a9b3c4a5a6a7a8a9b0c1a2a3';

    public function showLogin()
    {
        if (session()->has('user') && session('user.username')) {
            if (session('user.app') === 'presensi-sholat') {
                return redirect()->route('dashboard.presensi-sholat');
            }
            if (session('user.app') === 'approval-prestasi') {
                return redirect()->route('approval.prestasi.index');
            }
        }

        return view('login');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('login.form');
    }

    public function login(Request $request)
    {
        $turnstileEnabled = !empty(config('services.cloudflare_turnstile.site_key')) &&
            !empty(config('services.cloudflare_turnstile.secret_key'));

        $validated = $request->validate([
            'app' => ['required', 'in:presensi-sholat,aplikasi-laporan,approval-prestasi'],
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
            return $this->loginApprovalPrestasi($request, $validated);
        }

        return $this->loginPresensiSholat($request, $validated);
    }

    private function loginApprovalPrestasi(Request $request, array $validated)
    {
        $username = trim($validated['username']);
        $password = $validated['password'];
        $wsUrl = rtrim((string) env('APPROVAL_WS_URL', 'http://103.23.103.43/ws_client/mualimat_reward/index.php'), '/');
        $wsRequest = [
            'method' => 'loginApproval',
            'username' => $username,
            // password jangan dilog
        ];

        Log::info('Approval WS login request', [
            'url' => $wsUrl,
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
            Log::error('Approval prestasi WS login failed', ['message' => $e->getMessage()]);
            return back()
                ->withInput(['app' => 'approval-prestasi', 'username' => $username])
                ->with('login_error', 'Tidak dapat terhubung ke server approval. Silakan coba lagi.');
        }

        if (! $response->ok()) {
            Log::warning('Approval WS login HTTP non-200', [
                'url' => $wsUrl,
                'status' => $response->status(),
                'body' => substr((string) $response->body(), 0, 1000),
                'username' => $username,
            ]);
            return back()
                ->withInput(['app' => 'approval-prestasi', 'username' => $username])
                ->with('login_error', 'Server approval sedang bermasalah. Silakan coba lagi.');
        }

        $payload = $response->json();
        Log::info('Approval WS login response', [
            'url' => $wsUrl,
            'status' => $response->status(),
            'json' => $payload,
            'username' => $username,
        ]);
        $data = (is_array($payload) && isset($payload['data']) && is_array($payload['data'])) ? $payload['data'] : [];
        $token = trim((string) ($data['token'] ?? ''));
        $role = strtolower(trim((string) ($data['role'] ?? '')));

        if (($payload['status'] ?? 500) !== 200 || $token === '') {
            $message = (string) ($payload['message'] ?? 'Username atau password salah.');
            return back()
                ->withInput(['app' => 'approval-prestasi', 'username' => $username])
                ->with('login_error', $message);
        }

        if ($role === '' || $role === 'siswa') {
            return back()
                ->withInput(['app' => 'approval-prestasi', 'username' => $username])
                ->with('login_error', 'Akun ini tidak memiliki akses Approval Prestasi.');
        }

        $request->session()->put('user', [
            'username' => (string) ($data['username'] ?? $username),
            'nama' => (string) ($data['nama'] ?? $username),
            'role' => (string) ($data['role'] ?? ''),
            'code01' => trim((string) ($data['code01'] ?? '')),
            'approval_token' => $token,
            'app' => 'approval-prestasi',
        ]);

        return redirect()->route('approval.prestasi.index');
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
            return back()->with('password_error', 'Session tidak valid. Silakan login kembali.');
        }

        $apiBaseUrl = self::API_BASE_URL_PRESENSI_SHOLAT;

        $payload = [
            'METHOD'       => 'RequestNewPassword',
            'USERNAME'     => $username,
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
