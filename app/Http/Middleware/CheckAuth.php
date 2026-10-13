<?php

namespace App\Http\Middleware;

use App\Support\PersistentLogin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (! session()->has('user') || ! session('user.username')) {
                PersistentLogin::restoreIntoSession($request);
            }
        } catch (\Throwable $e) {
            //
        }

        if (! session()->has('user') || ! session('user.username')) {
            return redirect()->route('login.form');
        }

        return $next($request);
    }
}
