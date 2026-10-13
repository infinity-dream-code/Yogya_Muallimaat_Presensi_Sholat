<?php

namespace App\Support;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class PersistentLogin
{
    public const COOKIE = 'muallimat_presensi';

    /** Cookie lifetime in minutes (~10 years), aligned with SESSION_LIFETIME. */
    public const MINUTES = 5256000;

    public static function set(array $user): void
    {
        if (empty($user['username'])) {
            return;
        }

        try {
            $payload = json_encode($user, JSON_UNESCAPED_UNICODE);
            if ($payload === false) {
                return;
            }

            // Manual encrypt (cookie is excluded from EncryptCookies) to avoid double-encrypt size blow-up.
            $value = Crypt::encryptString($payload);

            // Browser limit ~4KB; if too large, drop bulky token fields and retry.
            if (strlen($value) > 3500) {
                $slim = $user;
                unset($slim['approval_token']);
                $payload = json_encode($slim, JSON_UNESCAPED_UNICODE);
                if ($payload === false) {
                    return;
                }
                $value = Crypt::encryptString($payload);
            }

            if (strlen($value) > 3800) {
                Log::warning('PersistentLogin cookie too large, skipped', [
                    'username' => $user['username'] ?? null,
                    'bytes' => strlen($value),
                ]);

                return;
            }

            Cookie::queue(cookie(
                self::COOKIE,
                $value,
                self::MINUTES,
                '/',
                null,
                null,
                true,
                false,
                'lax'
            ));
        } catch (\Throwable $e) {
            Log::warning('PersistentLogin set failed', ['message' => $e->getMessage()]);
        }
    }

    public static function forget(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    public static function read(?string $raw): ?array
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            // Prefer decrypt (new format). Fall back to plain JSON (legacy).
            try {
                $json = Crypt::decryptString($raw);
            } catch (\Throwable $e) {
                $json = $raw;
            }

            $data = json_decode($json, true);
            if (! is_array($data) || empty($data['username'])) {
                return null;
            }

            return $data;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function restoreIntoSession(\Illuminate\Http\Request $request): bool
    {
        if (session()->has('user') && session('user.username')) {
            return true;
        }

        $user = self::read($request->cookie(self::COOKIE));
        if ($user === null) {
            return false;
        }

        session()->put('user', $user);
        self::set($user);

        return true;
    }
}
