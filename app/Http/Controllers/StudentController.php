<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    private const API_BASE_URL = 'http://vps1.smartpayment.co.id:8888/Data/Yogya_Muallimaat_Perizinan/WebAPI.php';
    private const JWT_SECRET = 'a7c2a8a9b3c4a5a6a7a8a9b0c1a2a3';

    public function liveSearch(Request $request)
    {
        $query = trim($request->get('q', ''));

        if ($query === '') {
            return response()->json(['data' => []]);
        }

        $now = now()->timestamp;
        $payload = [
            'METHOD'   => 'StudentRequest',
            'USERNAME' => 111111,
            'exp'      => $now + 3600,
            'iat'      => $now,
            'KEYWORD'  => $query,
        ];

        $token = $this->generateJwt($payload);

        try {
            Log::info('Student live search request', [
                'keyword' => $query,
            ]);

            $response = Http::timeout(15)
                ->get(self::API_BASE_URL . '?token=' . urlencode($token));
        } catch (\Throwable $e) {
            Log::error('Error calling StudentRequest API', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'data' => [],
                'error' => 'Gagal terhubung ke server siswa.',
            ], 500);
        }

        if (! $response->ok()) {
            return response()->json([
                'data' => [],
                'error' => 'Respon server tidak valid.',
            ], $response->status());
        }

        $data = $response->json();

        // Bentuk respon API (berdasarkan hasil uji):
        // { "data": [ { "NIS": "...", "NAMA": "...", ... }, ... ] }
        // atau terkadang "Data" / array langsung
        $students = $data['Data'] ?? $data['data'] ?? $data;

        if (! is_array($students)) {
            $students = [];
        } else {
            // Jika masih ada nesting "data" di dalamnya
            if (isset($students['data']) && is_array($students['data'])) {
                $students = $students['data'];
            }

            // Filter manual berdasarkan keyword (karena API mengembalikan semua data)
            $keyword = mb_strtolower($query);

            $students = array_filter($students, function ($row) use ($keyword) {
                $name = mb_strtolower($row['NAMA'] ?? $row['Nama'] ?? $row['nama'] ?? '');
                $nis  = mb_strtolower((string) ($row['NIS'] ?? $row['nis'] ?? $row['nisn'] ?? ''));

                return $keyword === '' ||
                    str_contains($name, $keyword) ||
                    str_contains($nis, $keyword);
            });

            // Normalisasi ke array numerik untuk JS
            $students = array_values($students);
        }

        return response()->json([
            'data' => $students,
        ]);
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
}

