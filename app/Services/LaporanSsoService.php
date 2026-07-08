<?php

namespace App\Services;

class LaporanSsoService
{
    public function createToken(string $username): string
    {
        $secret = config('services.laporan_sso.secret');
        if (empty($secret)) {
            throw new \RuntimeException('PORTAL_SSO_SECRET belum dikonfigurasi.');
        }

        $now = time();
        $payload = [
            'sub' => $username,
            'iat' => $now,
            'exp' => $now + 60,
            'iss' => config('app.url'),
        ];

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $headerEncoded = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signingInput = $headerEncoded . '.' . $payloadEncoded;
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return $signingInput . '.' . $signatureEncoded;
    }

    public function buildRedirectUrl(string $token): string
    {
        $baseUrl = rtrim(config('services.laporan_sso.url'), '/');

        return $baseUrl . '/sso/login?token=' . urlencode($token);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
