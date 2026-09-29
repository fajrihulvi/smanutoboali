<?php

namespace App\Modules\Cms\Services;

use App\Modules\Cms\Enums\ContentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Menerapkan status & jadwal terbit pada konten (halaman, berita/agenda, galeri).
 *
 * `published_at` = tanggal tayang yang ditampilkan publik: jadwal terbit bila diisi,
 * selain itu saat pertama kali diterbitkan.
 */
class ContentPublisher
{
    /**
     * @throws ValidationException
     */
    public function apply(Model $content, ContentStatus $next, ?CarbonImmutable $publishAt = null): void
    {
        /** @var ContentStatus|null $current */
        $current = $content->exists ? $content->getAttribute('status') : null;

        if ($current !== null && ! $current->canTransitionTo($next)) {
            throw ValidationException::withMessages([
                'status' => "Status tidak bisa diubah dari {$current->label()} ke {$next->label()}.",
            ]);
        }

        $content->setAttribute('status', $next);
        $content->setAttribute('publish_at', $next === ContentStatus::Published ? $publishAt : null);

        if ($next === ContentStatus::Published) {
            $content->setAttribute('published_at', $publishAt ?? $content->getAttribute('published_at') ?? CarbonImmutable::now());
        }
    }
}
