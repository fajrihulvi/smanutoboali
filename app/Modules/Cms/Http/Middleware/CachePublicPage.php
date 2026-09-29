<?php

namespace App\Modules\Cms\Http\Middleware;

use App\Modules\Cms\Services\PublicPageCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Sajikan halaman website publik dari cache untuk tamu (header X-Page-Cache: HIT/MISS).
 * Hanya respons 200 HTML/XML/teks yang disimpan.
 */
class CachePublicPage
{
    public function __construct(private PublicPageCache $cache) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        if (! $this->cache->cacheable($request)) {
            return $next($request);
        }

        $key = $this->cache->key($request);
        $hit = $this->cache->get($key);

        if ($hit !== null) {
            return new Response($hit['content'], 200, ['Content-Type' => $hit['type'], 'X-Page-Cache' => 'HIT']);
        }

        $response = $next($request);

        if ($response instanceof Response && $response->getStatusCode() === 200) {
            $this->cache->put($key, (string) $response->getContent(), (string) $response->headers->get('Content-Type', 'text/html; charset=UTF-8'));
            $response->headers->set('X-Page-Cache', 'MISS');
        }

        return $response;
    }
}
