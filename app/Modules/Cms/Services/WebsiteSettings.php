<?php

namespace App\Modules\Cms\Services;

use App\Modules\Cms\Models\WebsiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan website (identitas, kontak, media sosial, peta, CTA PPDB, footer).
 * Nama & logo sekolah tetap dari pengaturan sekolah (modul Core).
 */
class WebsiteSettings
{
    public const CACHE_KEY = 'cms.website-settings';

    /** @var array<string, string> */
    public const DEFAULTS = [
        'tagline' => '',
        'about' => '',
        'address' => '',
        'phone' => '',
        'whatsapp' => '',
        'email' => '',
        'opening_hours' => '',
        'map_embed_url' => '',
        'facebook' => '',
        'instagram' => '',
        'youtube' => '',
        'tiktok' => '',
        'ppdb_cta_title' => 'Penerimaan Peserta Didik Baru',
        'ppdb_cta_text' => 'Pendaftaran dilakukan secara online. Siapkan dokumen dan daftar sekarang.',
        'footer_text' => '',
    ];

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        /** @var array<string, string> $stored */
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => WebsiteSetting::query()
            ->whereIn('key', array_keys(self::DEFAULTS))
            ->pluck('value', 'key')
            ->map(fn ($value) => is_scalar($value) ? (string) $value : '')
            ->all());

        return array_merge(self::DEFAULTS, $stored);
    }

    public function get(string $key): string
    {
        return $this->all()[$key] ?? '';
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function save(array $values): void
    {
        foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
            WebsiteSetting::query()->updateOrCreate(['key' => $key], ['value' => trim((string) $value)]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
