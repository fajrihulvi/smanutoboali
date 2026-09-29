<?php

namespace App\Modules\Cms\Actions;

use App\Models\User;
use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Enums\PostType;
use App\Modules\Cms\Models\WebsitePost;
use App\Modules\Cms\Services\ContentPublisher;
use App\Modules\Cms\Services\ContentSanitizer;
use App\Modules\Cms\Services\SlugGenerator;
use App\Modules\Cms\Services\WebsiteMedia;
use App\Modules\Core\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Buat/ubah berita atau agenda.
 */
class SavePostAction
{
    public function __construct(
        private ContentSanitizer $sanitizer,
        private SlugGenerator $slugs,
        private ContentPublisher $publisher,
        private WebsiteMedia $media,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array{title: string, slug: string|null, category_id: int|null, excerpt: string|null, content: string|null, seo_title: string|null, seo_description: string|null, is_featured: bool, event_starts_at: CarbonImmutable|null, event_ends_at: CarbonImmutable|null, event_location: string|null, status: ContentStatus, publish_at: CarbonImmutable|null, image: UploadedFile|null, remove_image: bool}  $data
     */
    public function handle(PostType $type, array $data, User $actor, ?WebsitePost $post = null): WebsitePost
    {
        $isNew = $post === null;

        $post = DB::transaction(function () use ($type, $data, $actor, $post) {
            $post ??= new WebsitePost(['type' => $type, 'created_by' => $actor->id]);
            $isAgenda = $post->type === PostType::Agenda;

            $post->slug = $this->slugs->unique(WebsitePost::class, $data['slug'] ?: $data['title'], $post->exists ? $post->id : null, 160);
            $post->fill([
                'title' => $data['title'],
                'category_id' => $data['category_id'],
                'excerpt' => $data['excerpt'],
                'content' => $this->sanitizer->clean($data['content']),
                'seo_title' => $data['seo_title'],
                'seo_description' => $data['seo_description'],
                'is_featured' => $data['is_featured'],
                'event_starts_at' => $isAgenda ? $data['event_starts_at'] : null,
                'event_ends_at' => $isAgenda ? $data['event_ends_at'] : null,
                'event_location' => $isAgenda ? $data['event_location'] : null,
                'updated_by' => $actor->id,
            ]);
            $this->publisher->apply($post, $data['status'], $data['publish_at']);
            $post->save();

            $this->media->replace($post, 'featured_image_id', $data['image'], $data['remove_image'], $actor, 'image');

            return $post;
        });

        $label = strtolower($post->type->label());
        $this->audit->record('cms', $isNew ? 'created' : 'updated', ($isNew ? 'Membuat' : 'Mengubah')." {$label} \"{$post->title}\" ({$post->status->label()})", $post);

        return $post;
    }
}
