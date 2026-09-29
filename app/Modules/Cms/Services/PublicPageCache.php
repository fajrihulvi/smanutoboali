<?php

namespace App\Modules\Cms\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Vite;

/**
 * Cache HTML halaman website publik untuk tamu.
 *
 * Kunci menyertakan nomor versi; `flush()` cukup menaikkan versi sehingga semua
 * entri lama tidak terpakai lagi (tanpa tag cache — aman untuk driver file/database).
 * TTL membatasi keterlambatan konten terjadwal & periode PPDB yang berganti.
 */
class PublicPageCache
{
    public const TTL_SECONDS = 600;

    private const VERSION_KEY = 'cms.page-cache.version';

    /** Query string yang boleh ikut di-cache (selain itu tidak di-cache). */
    private const ALLOWED_QUERY = ['page', 'kategori'];

    public function cacheable(Request $request): bool
    {
        return $request->isMethod('GET')
            && $request->user() === null
            && array_diff(array_keys($request->query()), self::ALLOWED_QUERY) === [];
    }

    public function key(Request $request): string
    {
        $query = $request->query();
        ksort($query);

        // Hash manifest Vite ikut kunci: setelah build baru, HTML lama (yang merujuk
        // file aset ber-hash lama) otomatis tidak dipakai lagi.
        $assets = Vite::isRunningHot() ? 'hot' : (string) Vite::manifestHash();

        return 'cms.page.'.$this->version().'.'.sha1($assets.'|'.$request->path().'?'.http_build_query($query));
    }

    /**
     * @return array{content: string, type: string}|null
     */
    public function get(string $key): ?array
    {
        $value = Cache::get($key);

        return is_array($value) && isset($value['content'], $value['type']) ? $value : null;
    }

    public function put(string $key, string $content, string $type): void
    {
        Cache::put($key, ['content' => $content, 'type' => $type], self::TTL_SECONDS);
    }

    public function flush(): void
    {
        Cache::forever(self::VERSION_KEY, $this->version() + 1);
    }

    private function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }
}
