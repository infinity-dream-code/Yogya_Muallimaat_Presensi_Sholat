<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     * Persistent login encrypts its own payload to avoid double-encrypt size issues.
     *
     * @var array<int, string>
     */
    protected $except = [
        \App\Support\PersistentLogin::COOKIE,
    ];
}
