<?php

namespace App\Modules\Cms\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Slug unik dari judul; bila bentrok diberi akhiran angka (-2, -3, ...).
 * Baris yang dihapus (soft delete) ikut dihitung karena indeks unik tetap berlaku.
 */
class SlugGenerator
{
    /**
     * @param  class-string<Model>  $model
     * @param  list<string>  $reserved
     */
    public function unique(string $model, string $source, ?int $ignoreId = null, int $max = 120, array $reserved = []): string
    {
        $base = trim(Str::limit(Str::slug($source), $max - 5, ''), '-');
        $base = $base === '' ? 'konten' : $base;

        $slug = $base;
        $suffix = 2;

        while (in_array($slug, $reserved, true) || $this->taken($model, $slug, $ignoreId)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function taken(string $model, string $slug, ?int $ignoreId): bool
    {
        // Tanpa global scope: baris soft delete ikut dihitung.
        return $model::query()->withoutGlobalScopes()->where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }
}
