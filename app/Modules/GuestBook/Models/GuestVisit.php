<?php

namespace App\Modules\GuestBook\Models;

use App\Models\User;
use App\Modules\Core\Models\File;
use App\Modules\GuestBook\Database\Factories\GuestVisitFactory;
use App\Modules\GuestBook\Enums\GuestCategory;
use App\Modules\GuestBook\Enums\GuestHost;
use App\Modules\GuestBook\Enums\GuestVisitSource;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Satu kunjungan tamu di buku tamu.
 *
 * @property int $id
 * @property string $public_id
 * @property string $full_name
 * @property string $institution
 * @property string $whatsapp
 * @property GuestCategory $category
 * @property string $purpose
 * @property GuestHost $host
 * @property int|null $photo_file_id
 * @property string|null $notes
 * @property CarbonImmutable $visited_at
 * @property GuestVisitSource $source
 * @property int|null $created_by
 * @property CarbonImmutable $created_at
 * @property-read File|null $photo
 * @property-read User|null $creator
 */
class GuestVisit extends Model
{
    /** @use HasFactory<GuestVisitFactory> */
    use HasFactory, HasPublicId, SoftDeletes;

    protected $fillable = [
        'full_name', 'institution', 'whatsapp', 'category', 'purpose', 'host',
        'photo_file_id', 'notes', 'visited_at', 'source', 'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => GuestCategory::class,
            'host' => GuestHost::class,
            'source' => GuestVisitSource::class,
            'visited_at' => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): GuestVisitFactory
    {
        return GuestVisitFactory::new();
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(File::class, 'photo_file_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Kunjungan pada rentang tanggal (inklusif, zona waktu aplikasi).
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisitedBetween(Builder $query, ?string $from, ?string $to): void
    {
        $query
            ->when($from, fn (Builder $q) => $q->where('visited_at', '>=', CarbonImmutable::parse((string) $from)->startOfDay()))
            ->when($to, fn (Builder $q) => $q->where('visited_at', '<=', CarbonImmutable::parse((string) $to)->endOfDay()));
    }

    /**
     * Nomor WhatsApp untuk tautan wa.me (62xxxxxxxx).
     */
    public function whatsappLink(): string
    {
        return 'https://wa.me/'.preg_replace('/^0/', '62', $this->whatsapp);
    }
}
