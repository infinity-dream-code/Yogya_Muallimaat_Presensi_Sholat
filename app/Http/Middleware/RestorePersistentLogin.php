<?php

namespace App\Http\Middleware;

use App\Support\PersistentLogin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestorePersistentLogin
{
    /**
     * Restore session('user') from persistent cookie when session is missing/broken.
     * Re-queue cookie while the user remains authenticated.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $hasUser = session()->has('user') && session('user.username');

            if (! $hasUser) {
                PersistentLogin::restoreIntoSession($request);
            } elseif (is_array(session('user'))) {
                PersistentLogin::set(session('user'));
            }
        } catch (\Throwable $e) {
            // Never block the request because of cookie/session restore issues.
        }

        return $next($request);
    }
}
