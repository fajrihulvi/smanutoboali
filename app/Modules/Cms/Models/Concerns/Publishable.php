<?php

namespace App\Modules\Cms\Models\Concerns;

use App\Modules\Cms\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Konten yang tampil publik: status TERBIT dan jadwal terbit sudah tiba.
 *
 * @mixin Model
 */
trait Publishable
{
    /**
     * @param  Builder<static>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('status', ContentStatus::Published)
            ->where(fn (Builder $q) => $q->whereNull('publish_at')->orWhere('publish_at', '<=', now()));
    }

    public function isLive(): bool
    {
        return $this->getAttribute('status') === ContentStatus::Published
            && ($this->getAttribute('publish_at') === null || $this->getAttribute('publish_at')->lte(now()));
    }

    public function isScheduled(): bool
    {
        return $this->getAttribute('status') === ContentStatus::Published
            && $this->getAttribute('publish_at') !== null
            && $this->getAttribute('publish_at')->isFuture();
    }
}
