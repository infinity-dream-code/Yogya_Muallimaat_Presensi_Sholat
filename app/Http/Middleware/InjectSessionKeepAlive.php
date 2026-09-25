<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inject session keep-alive + CSRF recovery script into HTML responses.
 */
class InjectSessionKeepAlive
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! session('user.username')) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || ! str_contains($content, '</body>')) {
            return $response;
        }

        // Skip non-interactive exports / PDFs that happen to be HTML shells.
        if ($request->is('*/export-*') || $request->is('*/export/*')) {
            return $response;
        }

        $script = view('partials.session_keepalive')->render();
        $response->setContent(str_replace('</body>', $script."\n</body>", $content));

        return $response;
    }
}
