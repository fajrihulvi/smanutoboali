<?php

namespace App\Modules\Cms\Actions;

use App\Models\User;
use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Enums\PageType;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\Cms\Services\ContentPublisher;
use App\Modules\Cms\Services\ContentSanitizer;
use App\Modules\Cms\Services\SlugGenerator;
use App\Modules\Cms\Services\WebsiteMedia;
use App\Modules\Core\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Buat/ubah halaman website. Halaman sistem: jenis & slug terkunci.
 */
class SavePageAction
{
    public function __construct(
        private ContentSanitizer $sanitizer,
        private SlugGenerator $slugs,
        private ContentPublisher $publisher,
        private WebsiteMedia $media,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array{title: string, slug: string|null, content: string|null, excerpt: string|null, seo_title: string|null, seo_description: string|null, show_in_menu: bool, position: int, status: ContentStatus, publish_at: CarbonImmutable|null, image: UploadedFile|null, remove_image: bool}  $data
     */
    public function handle(array $data, User $actor, ?WebsitePage $page = null): WebsitePage
    {
        $isNew = $page === null;

        $page = DB::transaction(function () use ($data, $actor, $page) {
            $page ??= new WebsitePage(['type' => PageType::Custom, 'created_by' => $actor->id]);

            if (! $page->type->isSystem()) {
                $page->slug = $this->slugs->unique(
                    WebsitePage::class,
                    $data['slug'] ?: $data['title'],
                    $page->exists ? $page->id : null,
                    120,
                    WebsitePage::RESERVED_SLUGS,
                );
            }

            $page->fill([
                'title' => $data['title'],
                'content' => $this->sanitizer->clean($data['content']),
                'excerpt' => $data['excerpt'],
                'seo_title' => $data['seo_title'],
                'seo_description' => $data['seo_description'],
                'show_in_menu' => $data['show_in_menu'],
                'position' => $data['position'],
                'updated_by' => $actor->id,
            ]);
            $this->publisher->apply($page, $data['status'], $data['publish_at']);
            $page->save();

            $this->media->replace($page, 'featured_image_id', $data['image'], $data['remove_image'], $actor, 'image');

            return $page;
        });

        $this->audit->record('cms', $isNew ? 'created' : 'updated', ($isNew ? 'Membuat' : 'Mengubah')." halaman \"{$page->title}\" ({$page->status->label()})", $page);

        return $page;
    }
}
