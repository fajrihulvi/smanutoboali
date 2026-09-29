<?php

namespace App\Modules\Cms\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Enums\PostType;
use App\Modules\Cms\Models\WebsiteGallery;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\Cms\Models\WebsitePost;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = array_map(fn (string $path) => $this->entry($path), ['/', 'berita', 'agenda', 'galeri', 'guru', 'ppdb']);

        foreach (WebsitePage::query()->live()->get(['slug', 'updated_at']) as $page) {
            $urls[] = $this->entry($page->slug, $page->updated_at);
        }

        foreach (WebsitePost::query()->live()->get(['type', 'slug', 'updated_at']) as $post) {
            $urls[] = $this->entry(($post->type === PostType::Agenda ? 'agenda' : 'berita').'/'.$post->slug, $post->updated_at);
        }

        foreach (WebsiteGallery::query()->live()->get(['slug', 'updated_at']) as $gallery) {
            $urls[] = $this->entry('galeri/'.$gallery->slug, $gallery->updated_at);
        }

        return response()
            ->view('public.sitemap', ['urls' => $urls], 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * @return array{loc: string, lastmod: CarbonInterface|null}
     */
    private function entry(string $path, ?CarbonInterface $lastmod = null): array
    {
        return ['loc' => url($path), 'lastmod' => $lastmod];
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            ...array_map(fn (string $p) => "Disallow: /{$p}", ['operator', 'guru/', 'kepala-sekolah', 'orang-tua', 'ppdb/akun', 'login', 'files', 'notifikasi', 'settings']),
            '',
            'Sitemap: '.url('sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
