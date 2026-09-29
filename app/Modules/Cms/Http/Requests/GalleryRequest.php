<?php

namespace App\Modules\Cms\Http\Requests;

use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Models\WebsiteGallery;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;

class GalleryRequest extends ContentRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', WebsiteGallery::class) ?? false;
    }

    protected function table(): string
    {
        return 'website_galleries';
    }

    protected function current(): ?WebsiteGallery
    {
        $gallery = $this->route('gallery');

        return $gallery instanceof WebsiteGallery ? $gallery : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = $this->contentRules(160);

        return [
            'title' => $rules['title'],
            'slug' => $rules['slug'],
            'status' => $rules['status'],
            'publish_at' => $rules['publish_at'],
            'description' => ['nullable', 'string', 'max:1000'],
            'date' => ['nullable', 'date'],
            'photos' => ['nullable', 'array'],
            'photos.*.id' => ['required', 'string'],
            'photos.*.caption' => ['nullable', 'string', 'max:200'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['string'],
            'uploads' => ['nullable', 'array', 'max:'.WebsiteGallery::MAX_PHOTOS],
            'uploads.*' => ['file', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'description' => 'deskripsi',
            'date' => 'tanggal kegiatan',
            'uploads' => 'foto',
            'uploads.*' => 'foto',
            'photos.*.caption' => 'keterangan foto',
        ];
    }

    /**
     * @return array{title: string, slug: string|null, description: string|null, date: CarbonImmutable|null, status: ContentStatus, publish_at: CarbonImmutable|null, photos: list<array{id: string, caption: string|null}>, remove_photos: list<string>, uploads: list<UploadedFile>}
     */
    public function payload(): array
    {
        /** @var list<array{id?: mixed, caption?: mixed}> $photos */
        $photos = array_values((array) $this->input('photos', []));
        $uploads = $this->file('uploads');

        return [
            'title' => trim($this->string('title')->value()),
            'slug' => $this->nullableString('slug'),
            'description' => $this->nullableString('description'),
            'date' => $this->nullableDate('date'),
            'status' => $this->status(),
            'publish_at' => $this->nullableDate('publish_at'),
            'photos' => array_map(fn (array $p) => [
                'id' => (string) ($p['id'] ?? ''),
                'caption' => is_string($p['caption'] ?? null) && trim($p['caption']) !== '' ? trim($p['caption']) : null,
            ], $photos),
            'remove_photos' => array_values(array_map('strval', (array) $this->input('remove_photos', []))),
            'uploads' => is_array($uploads) ? array_values($uploads) : [],
        ];
    }
}
