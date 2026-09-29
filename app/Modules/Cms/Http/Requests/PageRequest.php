<?php

namespace App\Modules\Cms\Http\Requests;

use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Models\WebsitePage;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class PageRequest extends ContentRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', WebsitePage::class) ?? false;
    }

    protected function table(): string
    {
        return 'website_pages';
    }

    protected function current(): ?WebsitePage
    {
        $page = $this->route('page');

        return $page instanceof WebsitePage ? $page : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = $this->contentRules();
        $rules['slug'][] = Rule::notIn(WebsitePage::RESERVED_SLUGS);

        return [
            ...$rules,
            'show_in_menu' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [...parent::messages(), 'slug.not_in' => 'Slug ini dipakai sistem. Pilih slug lain.'];
    }

    /**
     * @return array{title: string, slug: string|null, content: string|null, excerpt: string|null, seo_title: string|null, seo_description: string|null, show_in_menu: bool, position: int, status: ContentStatus, publish_at: CarbonImmutable|null, image: UploadedFile|null, remove_image: bool}
     */
    public function payload(): array
    {
        return [
            'title' => trim($this->string('title')->value()),
            'slug' => $this->nullableString('slug'),
            'content' => $this->nullableString('content'),
            'excerpt' => $this->nullableString('excerpt'),
            'seo_title' => $this->nullableString('seo_title'),
            'seo_description' => $this->nullableString('seo_description'),
            'show_in_menu' => $this->boolean('show_in_menu'),
            'position' => $this->integer('position'),
            'status' => $this->status(),
            'publish_at' => $this->nullableDate('publish_at'),
            'image' => $this->upload('image'),
            'remove_image' => $this->boolean('remove_image'),
        ];
    }
}
