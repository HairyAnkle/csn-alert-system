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

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent Clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Enforce strict referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict sensitive browser features
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), payment=(), usb=()');

        // XSS Filter defense for legacy user agents
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Content Security Policy (permitting CDN assets, Leaflet maps, WebSockets, and inline Tailwind/Alpine bindings)
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
