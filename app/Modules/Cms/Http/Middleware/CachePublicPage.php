<?php

namespace App\Modules\Cms\Http\Middleware;

use App\Modules\Cms\Services\PublicPageCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Sajikan halaman website publik dari cache untuk tamu (header X-Page-Cache: HIT/MISS).
 * Hanya respons 200 HTML/XML/teks yang disimpan.
 *
 * Nonce CSP berbeda di setiap request: saat disimpan, nonce diganti placeholder;
 * saat disajikan, placeholder diisi nonce request ini (lihat SecurityHeaders).
 */
class CachePublicPage
{
    private const NONCE_PLACEHOLDER = '__SKOOLIA_CSP_NONCE__';

    public function __construct(private PublicPageCache $cache) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        if (! $this->cache->cacheable($request)) {
            return $next($request);
        }

        $key = $this->cache->key($request);
        $hit = $this->cache->get($key);

        if ($hit !== null) {
            $content = str_replace(self::NONCE_PLACEHOLDER, (string) Vite::cspNonce(), $hit['content']);

            return new Response($content, 200, ['Content-Type' => $hit['type'], 'X-Page-Cache' => 'HIT']);
        }

        $response = $next($request);

        if ($response instanceof Response && $response->getStatusCode() === 200) {
            $content = (string) $response->getContent();
            $nonce = Vite::cspNonce();

            $this->cache->put(
                $key,
                $nonce ? str_replace($nonce, self::NONCE_PLACEHOLDER, $content) : $content,
                (string) $response->headers->get('Content-Type', 'text/html; charset=UTF-8'),
            );
            $response->headers->set('X-Page-Cache', 'MISS');
        }

        return $response;
    }
}
