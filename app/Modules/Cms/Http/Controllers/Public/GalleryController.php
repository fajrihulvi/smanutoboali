<?php

namespace App\Modules\Cms\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Models\WebsiteGallery;
use App\Modules\Cms\Services\PublicSite;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public const PER_PAGE = 12;

    public function index(): View
    {
        return view('public.galleries.index', [
            'galleries' => WebsiteGallery::query()->live()
                ->withCount('photos')
                ->with(['photos' => fn ($q) => $q->limit(1), 'photos.file'])
                ->orderByDesc('date')->orderByDesc('id')
                ->paginate(self::PER_PAGE)->withQueryString(),
            'seo' => ['title' => 'Galeri', 'description' => 'Dokumentasi foto kegiatan sekolah.'],
        ]);
    }

    public function show(string $slug): View
    {
        $gallery = WebsiteGallery::query()->live()->where('slug', $slug)->with('photos.file')->firstOrFail();

        return self::render($gallery);
    }

    public static function render(WebsiteGallery $gallery, bool $preview = false): View
    {
        return view('public.galleries.show', [
            'gallery' => $gallery,
            'preview' => $preview,
            'seo' => [
                'title' => $gallery->title,
                'description' => PublicSite::describe($gallery->description, "Dokumentasi {$gallery->title}"),
                'image' => $gallery->photos->first()?->file,
                'noindex' => $preview,
            ],
        ]);
    }
}
