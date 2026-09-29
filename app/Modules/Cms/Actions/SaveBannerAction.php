<?php

namespace App\Modules\Cms\Actions;

use App\Models\User;
use App\Modules\Cms\Models\WebsiteBanner;
use App\Modules\Cms\Services\WebsiteMedia;
use App\Modules\Core\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveBannerAction
{
    public function __construct(private WebsiteMedia $media, private AuditLogger $audit) {}

    /**
     * @param  array{title: string, subtitle: string|null, link_url: string|null, link_label: string|null, position: int, is_active: bool, starts_at: CarbonImmutable|null, ends_at: CarbonImmutable|null, image: UploadedFile|null}  $data
     *
     * @throws ValidationException
     */
    public function handle(array $data, User $actor, ?WebsiteBanner $banner = null): WebsiteBanner
    {
        $isNew = $banner === null;

        if ($isNew && $data['image'] === null) {
            throw ValidationException::withMessages(['image' => 'Gambar banner wajib diunggah.']);
        }

        $banner = DB::transaction(function () use ($data, $actor, $banner) {
            $banner ??= new WebsiteBanner;
            $banner->fill(collect($data)->except('image')->all())->save();
            $this->media->replace($banner, 'image_file_id', $data['image'], false, $actor, 'image');

            return $banner;
        });

        $this->audit->record('cms', $isNew ? 'created' : 'updated', ($isNew ? 'Membuat' : 'Mengubah')." banner \"{$banner->title}\"", $banner);

        return $banner;
    }
}
