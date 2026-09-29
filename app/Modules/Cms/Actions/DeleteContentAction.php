<?php

namespace App\Modules\Cms\Actions;

use App\Modules\Cms\Models\WebsiteBanner;
use App\Modules\Cms\Models\WebsiteGallery;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\Cms\Models\WebsitePost;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Hapus konten website. Halaman sistem tidak bisa dihapus (hanya diarsipkan).
 * Halaman/berita/galeri di-soft delete; gambarnya ikut dibersihkan untuk banner.
 */
class DeleteContentAction
{
    public function __construct(private FileService $files, private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(WebsitePage|WebsitePost|WebsiteGallery|WebsiteBanner $content): void
    {
        if ($content instanceof WebsitePage && $content->type->isSystem()) {
            throw ValidationException::withMessages(['page' => 'Halaman sistem tidak bisa dihapus. Arsipkan bila tidak ingin ditampilkan.']);
        }

        DB::transaction(function () use ($content) {
            if ($content instanceof WebsiteBanner) {
                $image = $content->image_file_id ? File::query()->find($content->image_file_id) : null;
                $content->delete();

                if ($image) {
                    $this->files->delete($image);
                }

                return;
            }

            $content->delete();
        });

        $this->audit->record('cms', 'deleted', "Menghapus \"{$content->title}\" dari website", $content);
    }
}
