<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PerizinanController extends Controller
{
    private const API_BASE_URL = 'http://vps1.smartpayment.co.id:8888/Data/Yogya_Muallimaat_Perizinan/WebAPI.php';
    private const JWT_SECRET = 'a7c2a8a9b3c4a5a6a7a8a9b0c1a2a3';

    public function submitKedatangan(Request $request)
    {
        $validated = $request->validate([
            'no_kartu' => ['required', 'string'],
            'izin' => ['required', 'string'],
        ]);

        $username = session('user.username');
        if (!$username) {
            return response()->json([
                'success' => false,
                'message' => 'Session tidak valid. Silakan login kembali.',
            ], 401);
        }

        $now = now()->timestamp;
        $payload = [
            'METHOD'   => 'POSTPerizinanSingle',
            'NOKARTU' => $validated['no_kartu'],
            'IZIN'    => $validated['izin'],
            'USERNAME' => $username,
            'iat'     => $now,
            'exp'     => $now + 300,
        ];

        $token = $this->generateJwt($payload);

        try {
            Log::info('Submit perizinan kedatangan request', [
                'username' => $username,
                'payload' => $payload,
                'token_preview' => substr($token, 0, 50) . '...',
                'token_length' => strlen($token),
            ]);

            $url = self::API_BASE_URL . '?token=' . urlencode($token);
            Log::info('Submit perizinan kedatangan API URL', [
                'url_preview' => substr($url, 0, 100) . '...',
                'url_length' => strlen($url),
            ]);

            // Coba GET dulu (sesuai dengan API lainnya)
            $response = Http::timeout(15)
                ->get($url);

            // Jika GET gagal dengan 500, coba POST sebagai alternatif
            if ($response->status() === 500 && empty($response->body())) {
                Log::info('Trying POST method for submit perizinan kedatangan');

                // Variasi 1: POST dengan token di query string (seperti GET)
                $response = Http::timeout(15)
                    ->post($url);

                // Jika masih 500, coba variasi 2: POST dengan token di body sebagai form
                if ($response->status() === 500 && empty($response->body())) {
                    Log::info('Trying POST with token in form body for perizinan');
                    $response = Http::timeout(15)
                        ->asForm()
                        ->post(self::API_BASE_URL, ['token' => $token]);
                }
            }

            Log::info('Submit perizinan kedatangan API response', [
                'status' => $response->status(),
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
                    // HTTP 500 dengan body kosong
                    $errorMsg = 'Server API mengembalikan error tanpa pesan. Kemungkinan: data tidak valid (NOKARTU/IZIN tidak ditemukan), endpoint tidak tersedia, atau server bermasalah.';
                }

                Log::error('Submit perizinan kedatangan failed', [
                    'status' => $status,
                    'body' => $body,
                    'username' => $username,
                    'no_kartu' => $validated['no_kartu'],
                    'izin' => $validated['izin'],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], $status);
            }

            $data = $response->json();

            // Bentuk respon POSTPerizinanSingle (berdasarkan log):
            // [ { "STATUS": "OK", "NAMA": "...", "RES": "CHECK IN BERHASIL" } ]
            if (is_array($data)) {
                // Jika bentuknya array dengan elemen pertama berisi STATUS
                if (isset($data[0]['STATUS']) && strtoupper($data[0]['STATUS']) === 'OK') {
                    return response()->json([
                        'success' => true,
                        'message' => $data[0]['RES'] ?? 'Perizinan berhasil dikirim.',
                        'raw'     => $data,
                    ]);
                }

                // Jika ada KodeRespon di level pertama array
                if (isset($data[0]['KodeRespon']) && (int) $data[0]['KodeRespon'] === 1) {
                    return response()->json([
                        'success' => true,
                        'message' => $data[0]['PesanRespon'] ?? 'Perizinan berhasil dikirim.',
                        'raw'     => $data,
                    ]);
                }
            }

            // Fallback: bentuk objek standar dengan KodeRespon
            if (isset($data['KodeRespon']) && (int) $data['KodeRespon'] === 1) {
                return response()->json([
                    'success' => true,
                    'message' => $data['PesanRespon'] ?? 'Perizinan berhasil dikirim.',
                    'raw'     => $data,
                ]);
            }

            $message = $data['PesanRespon']
                ?? ($data[0]['RES'] ?? $data[0]['PesanRespon'] ?? 'Gagal mengirim perizinan.');

            return response()->json([
                'success' => false,
                'message' => $message,
                'raw'     => $data,
            ], 400);
        } catch (\Throwable $e) {
            Log::error('Error submitting perizinan kedatangan', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat terhubung ke server. Silakan coba lagi.',
            ], 500);
        }
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

    public function submitUmum(Request $request)
    {
        $validated = $request->validate([
            'no_kartu' => ['required', 'string'],
            'izin' => ['required', 'string'],
            'keterangan' => ['nullable', 'string'],
            'tanggal' => ['required', 'string'],
        ]);

        $username = session('user.username');
        if (!$username) {
            return response()->json([
                'success' => false,
                'message' => 'Session tidak valid. Silakan login kembali.',
            ], 401);
        }

        $now = now()->timestamp;
        $payload = [
            'METHOD'   => 'POSTPerizinanMultiple',
            'NOKARTU' => $validated['no_kartu'],
            'IZIN'    => $validated['izin'],
            'HARI'    => $validated['tanggal'], // Format: YYYY-MM-DD HH:mm:ss
            'USERNAME' => $username,
            'KET'     => $validated['keterangan'] ?? '',
            'iat'     => $now,
            'exp'     => $now + 300,
        ];

        $token = $this->generateJwt($payload);

        try {
            Log::info('Submit perizinan umum request', [
                'username' => $username,
                'payload' => $payload,
                'token_preview' => substr($token, 0, 50) . '...',
                'token_length' => strlen($token),
            ]);

            $url = self::API_BASE_URL . '?token=' . urlencode($token);
            Log::info('Submit perizinan umum API URL', [
                'url_preview' => substr($url, 0, 100) . '...',
                'url_length' => strlen($url),
            ]);

            // Coba GET dulu (sesuai dengan API lainnya)
            $response = Http::timeout(15)
                ->get($url);

            // Jika GET gagal dengan 500, coba POST sebagai alternatif
            if ($response->status() === 500 && empty($response->body())) {
                Log::info('Trying POST method for submit perizinan umum');

                // Variasi 1: POST dengan token di query string (seperti GET)
                $response = Http::timeout(15)
                    ->post($url);

                // Jika masih 500, coba variasi 2: POST dengan token di body sebagai form
                if ($response->status() === 500 && empty($response->body())) {
                    Log::info('Trying POST with token in form body for perizinan umum');
                    $response = Http::timeout(15)
                        ->asForm()
                        ->post(self::API_BASE_URL, ['token' => $token]);
                }
            }

            Log::info('Submit perizinan umum API response', [
                'status' => $response->status(),
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
                    // HTTP 500 dengan body kosong
                    $errorMsg = 'Server API mengembalikan error tanpa pesan. Kemungkinan: data tidak valid (NOKARTU/IZIN tidak ditemukan), endpoint tidak tersedia, atau server bermasalah.';
                }

                Log::error('Submit perizinan umum failed', [
                    'status' => $status,
                    'body' => $body,
                    'username' => $username,
                    'no_kartu' => $validated['no_kartu'],
                    'izin' => $validated['izin'],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], $status);
            }

            $data = $response->json();

            // Bentuk respon POSTPerizinanMultiple (mirip dengan POSTPerizinanSingle)
            if (is_array($data)) {
                // Jika bentuknya array dengan elemen pertama berisi STATUS
                if (isset($data[0]['STATUS'])) {
                    $status = strtoupper($data[0]['STATUS']);

                    // Jika STATUS === 'OK', sukses
                    if ($status === 'OK') {
                        return response()->json([
                            'success' => true,
                            'message' => $data[0]['RES'] ?? 'Perizinan berhasil dikirim.',
                            'raw'     => $data,
                        ]);
                    }

                    // Jika STATUS === 'NOTOK', error
                    if ($status === 'NOTOK') {
                        $errorMsg = $data[0]['RES']
                            ?? $data[0]['RESULT']
                            ?? $data[0]['PesanRespon']
                            ?? 'Gagal mengirim perizinan.';

                        return response()->json([
                            'success' => false,
                            'message' => $errorMsg,
                            'raw'     => $data,
                        ], 400);
                    }
                }

                // Jika ada KodeRespon di level pertama array
                if (isset($data[0]['KodeRespon']) && (int) $data[0]['KodeRespon'] === 1) {
                    return response()->json([
                        'success' => true,
                        'message' => $data[0]['PesanRespon'] ?? 'Perizinan berhasil dikirim.',
                        'raw'     => $data,
                    ]);
                }
            }

            // Fallback: bentuk objek standar dengan KodeRespon
            if (isset($data['KodeRespon']) && (int) $data['KodeRespon'] === 1) {
                return response()->json([
                    'success' => true,
                    'message' => $data['PesanRespon'] ?? 'Perizinan berhasil dikirim.',
                    'raw'     => $data,
                ]);
            }

            $message = $data['PesanRespon']
                ?? ($data[0]['RES'] ?? $data[0]['RESULT'] ?? $data[0]['PesanRespon'] ?? 'Gagal mengirim perizinan.');

            return response()->json([
                'success' => false,
                'message' => $message,
                'raw'     => $data,
            ], 400);
        } catch (\Throwable $e) {
            Log::error('Error submitting perizinan umum', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat terhubung ke server. Silakan coba lagi.',
            ], 500);
        }
    }

    public function submitKhusus(Request $request)
    {
        $validated = $request->validate([
            'no_kartu' => ['required', 'string'],
            'izin' => ['required', 'string'],
            'hari' => ['required', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $username = session('user.username');
        if (!$username) {
            return response()->json([
                'success' => false,
                'message' => 'Session tidak valid. Silakan login kembali.',
            ], 401);
        }

        $now = now()->timestamp;
        $payload = [
            'METHOD'   => 'POSTPerizinanSpecial',
            'NOKARTU' => $validated['no_kartu'],
            'IZIN'    => $validated['izin'],
            'HARI'    => (string) $validated['hari'], // Jumlah hari (1, 2, 3, dll)
            'KETIZIN' => $validated['keterangan'] ?? '',
            'USERNAME' => $username,
            'iat'     => $now,
            'exp'     => $now + 300,
        ];

        $token = $this->generateJwt($payload);

        try {
            Log::info('Submit perizinan khusus request', [
                'username' => $username,
                'payload' => $payload,
                'token_preview' => substr($token, 0, 50) . '...',
                'token_length' => strlen($token),
            ]);

            $url = self::API_BASE_URL . '?token=' . urlencode($token);
            Log::info('Submit perizinan khusus API URL', [
                'url_preview' => substr($url, 0, 100) . '...',
                'url_length' => strlen($url),
            ]);

            // Coba GET dulu (sesuai dengan API lainnya)
            $response = Http::timeout(15)
                ->get($url);

            // Jika GET gagal dengan 500, coba POST sebagai alternatif
            if ($response->status() === 500 && empty($response->body())) {
                Log::info('Trying POST method for submit perizinan khusus');

                // Variasi 1: POST dengan token di query string (seperti GET)
                $response = Http::timeout(15)
                    ->post($url);

                // Jika masih 500, coba variasi 2: POST dengan token di body sebagai form
                if ($response->status() === 500 && empty($response->body())) {
                    Log::info('Trying POST with token in form body for perizinan khusus');
                    $response = Http::timeout(15)
                        ->asForm()
                        ->post(self::API_BASE_URL, ['token' => $token]);
                }
            }

            Log::info('Submit perizinan khusus API response', [
                'status' => $response->status(),
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
                    // HTTP 500 dengan body kosong
                    $errorMsg = 'Server API mengembalikan error tanpa pesan. Kemungkinan: data tidak valid (NOKARTU/IZIN tidak ditemukan), endpoint tidak tersedia, atau server bermasalah.';
                }

                Log::error('Submit perizinan khusus failed', [
                    'status' => $status,
                    'body' => $body,
                    'username' => $username,
                    'no_kartu' => $validated['no_kartu'],
                    'izin' => $validated['izin'],
                    'hari' => $validated['hari'],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], $status);
            }

            $data = $response->json();

            // Bentuk respon POSTPerizinanSpecial (mirip dengan POSTPerizinanMultiple)
            if (is_array($data)) {
                // Jika bentuknya array dengan elemen pertama berisi STATUS
                if (isset($data[0]['STATUS'])) {
                    $status = strtoupper($data[0]['STATUS']);

                    // Jika STATUS === 'OK', sukses
                    if ($status === 'OK') {
                        return response()->json([
                            'success' => true,
                            'message' => $data[0]['RES'] ?? 'Perizinan berhasil dikirim.',
                            'raw'     => $data,
                        ]);
                    }

                    // Jika STATUS === 'NOTOK', error
                    if ($status === 'NOTOK') {
                        $errorMsg = $data[0]['RES']
                            ?? $data[0]['RESULT']
                            ?? $data[0]['PesanRespon']
                            ?? 'Gagal mengirim perizinan.';

                        return response()->json([
                            'success' => false,
                            'message' => $errorMsg,
                            'raw'     => $data,
                        ], 400);
                    }
                }

                // Jika ada KodeRespon di level pertama array
                if (isset($data[0]['KodeRespon']) && (int) $data[0]['KodeRespon'] === 1) {
                    return response()->json([
                        'success' => true,
                        'message' => $data[0]['PesanRespon'] ?? 'Perizinan berhasil dikirim.',
                        'raw'     => $data,
                    ]);
                }
            }

            // Fallback: bentuk objek standar dengan KodeRespon
            if (isset($data['KodeRespon']) && (int) $data['KodeRespon'] === 1) {
                return response()->json([
                    'success' => true,
                    'message' => $data['PesanRespon'] ?? 'Perizinan berhasil dikirim.',
                    'raw'     => $data,
                ]);
            }

            $message = $data['PesanRespon']
                ?? ($data[0]['RES'] ?? $data[0]['RESULT'] ?? $data[0]['PesanRespon'] ?? 'Gagal mengirim perizinan.');

            return response()->json([
                'success' => false,
                'message' => $message,
                'raw'     => $data,
            ], 400);
        } catch (\Throwable $e) {
            Log::error('Error submitting perizinan khusus', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat terhubung ke server. Silakan coba lagi.',
            ], 500);
        }
    }

    public function showLaporan()
    {
        $username = session('user.username');
        if (!$username) {
            return redirect()->route('login.form');
        }

        $now = now()->timestamp;

        $payload = [
            'METHOD'   => 'LogTransaksiRequest',
            'USERNAME' => $username,
            'iat'      => $now,
            'exp'      => $now + 300,
        ];

        $token = $this->generateJwt($payload);

        $rows = [];
        $error = null;

        try {
            $url = self::API_BASE_URL . '?token=' . urlencode($token);
            $response = Http::timeout(15)->get($url);

            if ($response->ok()) {
                $json = $response->json();

                if (is_array($json) && isset($json['datas']) && is_array($json['datas'])) {
                    $rows = $json['datas'];
                } elseif (is_array($json) && isset($json[0]) && is_array($json[0]) && isset($json[0]['datas']) && is_array($json[0]['datas'])) {
                    $rows = $json[0]['datas'];
                } elseif (is_array($json) && isset($json[0]) && is_array($json[0])) {
                    $rows = $json;
                } elseif (is_array($json)) {
                    $rows = [];
                } else {
                    $rows = [];
                }
            } else {
                $error = 'Gagal mengambil laporan';
            }
        } catch (\Throwable $e) {
            $error = 'Terjadi kesalahan saat mengambil laporan';
        }

        $rows = array_values(array_filter(array_map(function ($item) {
            if (is_array($item)) return $item;
            if (is_object($item)) return (array) $item;
            return null;
        }, $rows)));

        $rows = array_slice($rows, 0, 100);

        return view('laporan', [
            'rows' => $rows,
            'error' => $error,
        ]);
    }


    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
