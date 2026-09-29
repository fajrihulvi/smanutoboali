<?php

namespace App\Modules\Cms\Actions;

use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Models\WebsiteGallery;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\Cms\Models\WebsitePost;
use App\Modules\Cms\Services\ContentPublisher;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Ubah status dari daftar (ajukan tinjau, terbitkan, kembalikan ke draf, arsipkan).
 * Menerbitkan dari sini langsung tayang (jadwal terbit dikosongkan).
 */
class ChangeContentStatusAction
{
    public function __construct(private ContentPublisher $publisher, private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(WebsitePage|WebsitePost|WebsiteGallery $content, ContentStatus $status): void
    {
        $from = $content->status;
        $this->publisher->apply($content, $status);
        $content->save();

        $this->audit->record('cms', 'status_changed', "Mengubah status \"{$content->title}\" dari {$from->label()} ke {$status->label()}", $content);
    }
}
