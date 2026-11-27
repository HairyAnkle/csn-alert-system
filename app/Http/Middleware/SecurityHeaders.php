<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and attach defense-in-depth HTTP security headers.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), payment=(), usb=()');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! $response->headers->has('Content-Security-Policy')) {
            $viteDev = app()->environment('local') ? 'http://localhost:* http://127.0.0.1:* ws://localhost:* ws://127.0.0.1:*' : '';

            $csp = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://unpkg.com https://cdnjs.cloudflare.com {$viteDev}",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://unpkg.com https://cdnjs.cloudflare.com {$viteDev}",
                "font-src 'self' https://fonts.bunny.net https://cdnjs.cloudflare.com data:",
                "img-src 'self' data: https: blob: https://*.tile.openstreetmap.org https://*.openstreetmap.org https://cdnjs.cloudflare.com",
                "connect-src 'self' ws: wss: https://*.firebaseio.com https://*.googleapis.com https://identitytoolkit.googleapis.com https://nominatim.openstreetmap.org https://*.trycloudflare.com wss://*.trycloudflare.com {$viteDev}",
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "form-action 'self'",
            ];

            $response->headers->set('Content-Security-Policy', trim(preg_replace('/\s+/', ' ', implode('; ', $csp))));
        }

        return $response;
    }
}
