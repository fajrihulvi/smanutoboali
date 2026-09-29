<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan untuk semua respons: CSP (script bernonce), anti-framing, anti-sniffing,
 * Referrer-Policy, Permissions-Policy, dan HSTS di produksi.
 *
 * Sumber eksternal yang diizinkan: Pusher (realtime), Midtrans Snap (pembayaran),
 * Google Fonts, dan Google Maps (peta di halaman Kontak website).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        /** @var Response $response */
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=(self), usb=(), interest-cohort=()');
        $headers->remove('X-Powered-By');

        if (config('skoolia.security.csp')) {
            $name = config('skoolia.security.csp_report_only') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
            $headers->set($name, $this->policy($nonce));
        }

        if (config('skoolia.security.hsts') && app()->isProduction() && $request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function policy(string $nonce): string
    {
        $midtrans = 'https://*.midtrans.com';
        $dev = $this->viteDevOrigins();

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'", $midtrans, ...$dev['http']],
            'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com', ...$dev['http']],
            'font-src' => ["'self'", 'data:', 'https://fonts.gstatic.com', ...$dev['http']],
            'img-src' => ["'self'", 'data:', 'blob:', 'https:', ...$dev['http']],
            'media-src' => ["'self'", 'blob:'],
            'connect-src' => ["'self'", $midtrans, 'https://*.pusher.com', 'wss://*.pusher.com', ...$dev['http'], ...$dev['ws']],
            'frame-src' => ["'self'", $midtrans, 'https://www.google.com', 'https://maps.google.com'],
            'worker-src' => ["'self'", 'blob:'],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'self'"],
        ];

        $policy = collect($directives)->map(fn (array $sources, string $name) => $name.' '.implode(' ', array_unique($sources)))->implode('; ');

        return app()->isProduction() ? $policy.'; upgrade-insecure-requests' : $policy;
    }

    /**
     * Origin Vite dev server (file public/hot) — hanya saat pengembangan lokal.
     *
     * @return array{http: list<string>, ws: list<string>}
     */
    private function viteDevOrigins(): array
    {
        if (app()->isProduction() || ! Vite::isRunningHot()) {
            return ['http' => [], 'ws' => []];
        }

        $url = trim((string) @file_get_contents(public_path('hot')));

        if ($url === '') {
            return ['http' => [], 'ws' => []];
        }

        // Sintaks CSP tidak mendukung host IPv6 literal (mis. http://[::1]:5173):
        // khusus lokal, pakai sumber skema.
        if (str_contains($url, '[')) {
            return ['http' => ['http:'], 'ws' => ['ws:']];
        }

        return ['http' => [$url], 'ws' => [preg_replace('#^http#', 'ws', $url) ?? $url]];
    }
}
