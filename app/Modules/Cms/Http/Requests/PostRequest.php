<?php

namespace App\Modules\Cms\Http\Requests;

use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Enums\PostType;
use App\Modules\Cms\Models\WebsiteCategory;
use App\Modules\Cms\Models\WebsitePost;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class PostRequest extends ContentRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', WebsitePost::class) ?? false;
    }

    protected function table(): string
    {
        return 'website_posts';
    }

    protected function current(): ?WebsitePost
    {
        $post = $this->route('post');

        return $post instanceof WebsitePost ? $post : null;
    }

    public function type(): PostType
    {
        /** @var string $segment */
        $segment = $this->route()?->defaults['type'] ?? 'berita';

        return PostType::fromSegment($segment);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isAgenda = $this->type() === PostType::Agenda;
        $rules = $this->contentRules(160);

        return [
            ...$rules,
            'category' => ['nullable', 'string', Rule::exists('website_categories', 'public_id')],
            'is_featured' => ['boolean'],
            'event_starts_at' => [$isAgenda ? 'required' : 'nullable', 'date'],
            'event_ends_at' => ['nullable', 'date', 'after_or_equal:event_starts_at'],
            'event_location' => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'category' => 'kategori',
            'event_starts_at' => 'waktu mulai',
            'event_ends_at' => 'waktu selesai',
            'event_location' => 'lokasi',
        ];
    }

    /**
     * @return array{title: string, slug: string|null, category_id: int|null, excerpt: string|null, content: string|null, seo_title: string|null, seo_description: string|null, is_featured: bool, event_starts_at: CarbonImmutable|null, event_ends_at: CarbonImmutable|null, event_location: string|null, status: ContentStatus, publish_at: CarbonImmutable|null, image: UploadedFile|null, remove_image: bool}
     */
    public function payload(): array
    {
        $category = $this->nullableString('category');

        return [
            'title' => trim($this->string('title')->value()),
            'slug' => $this->nullableString('slug'),
            'category_id' => $category ? WebsiteCategory::query()->where('public_id', $category)->value('id') : null,
            'excerpt' => $this->nullableString('excerpt'),
            'content' => $this->nullableString('content'),
            'seo_title' => $this->nullableString('seo_title'),
            'seo_description' => $this->nullableString('seo_description'),
            'is_featured' => $this->boolean('is_featured'),
            'event_starts_at' => $this->nullableDate('event_starts_at'),
            'event_ends_at' => $this->nullableDate('event_ends_at'),
            'event_location' => $this->nullableString('event_location'),
            'status' => $this->status(),
            'publish_at' => $this->nullableDate('publish_at'),
            'image' => $this->upload('image'),
            'remove_image' => $this->boolean('remove_image'),
        ];
    }
}
