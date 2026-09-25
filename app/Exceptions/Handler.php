<?php

namespace App\Exceptions;

use App\Support\PersistentLogin;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof TokenMismatchException) {
            return $this->recoverCsrf($request);
        }

        try {
            if ($request->hasSession() && $this->shouldRetryTransientGet($request, $e)) {
                $request->session()->put('_transient_get_retried', true);

                return redirect()->to($request->fullUrl());
            }

            if ($request->hasSession() && $request->session()->has('_transient_get_retried')) {
                $request->session()->forget('_transient_get_retried');
            }
        } catch (Throwable $ignored) {
            //
        }

        // Authenticated GET: never strand the user on a raw 500 — soft reload page (auto once).
        if ($this->shouldSoft500($request, $e)) {
            return response()->view('errors.500', [], 500);
        }

        return parent::render($request, $e);
    }

    private function recoverCsrf($request)
    {
        try {
            PersistentLogin::restoreIntoSession($request);
            if ($request->hasSession()) {
                $request->session()->regenerateToken();
            }
        } catch (Throwable $ignored) {
            //
        }

        $csrf = '';
        try {
            $csrf = csrf_token();
        } catch (Throwable $ignored) {
            //
        }

        if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'message' => 'csrf',
                'csrf' => $csrf,
            ], 419);
        }

        // Safe methods: just reload same URL with fresh session/CSRF.
        if ($request->isMethodSafe()) {
            return redirect()->to($request->fullUrl());
        }

        // Form POST/PUT/etc: silent one-shot auto-resubmit (no "sesi habis", no login redirect).
        return response()->view('errors.419', [
            'retry_action' => $request->fullUrl(),
            'retry_method' => strtoupper($request->method()),
            'retry_inputs' => $request->except(['_token']),
            'csrf' => $csrf,
        ], 200);
    }

    private function shouldSoft500($request, Throwable $e): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return false;
        }

        $status = 500;
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
        }

        return $status >= 500;
    }

    private function shouldRetryTransientGet($request, Throwable $e): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->session()->get('_transient_get_retried')) {
            return false;
        }

        return $this->isTransientError($e);
    }

    private function isTransientError(Throwable $e): bool
    {
        $haystack = strtolower($e->getMessage().' '.(string) $e);

        $needles = [
            'unable to obtain lock',
            'session has already been started',
            'deadlock',
            'try restarting transaction',
            'server has gone away',
            'lost connection',
            'error while reading line from the server',
            'connection refused',
            'sqlstate[40001]',
            'sqlstate[hy000]',
            'serialization failure',
            'database is locked',
            'broken pipe',
            'connection timed out',
            'temporary failure',
        ];

        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
