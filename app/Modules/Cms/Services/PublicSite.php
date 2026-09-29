<?php

namespace App\Modules\Cms\Services;

use App\Modules\Cms\Enums\PageType;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\School\Models\School;
use App\Modules\School\Services\SchoolProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Data bersama layout website publik: identitas sekolah, pengaturan, dan menu.
 * Dihitung sekali per request (disimpan di atribut request).
 */
class PublicSite
{
    private const REQUEST_KEY = 'cms.public-site';

    public function __construct(private SchoolProfile $profile, private WebsiteSettings $settings) {}

    /**
     * @return array<string, mixed>
     */
    public function shared(): array
    {
        /** @var array<string, mixed>|null $cached */
        $cached = request()->attributes->get(self::REQUEST_KEY);

        if ($cached !== null) {
            return $cached;
        }

        $profile = $this->profile->toArray();
        $settings = $this->settings->all();
        $school = School::query()->first();

        /** @var Collection<int, WebsitePage> $pages */
        $pages = WebsitePage::query()->live()->where('show_in_menu', true)
            ->orderBy('position')->orderBy('id')
            ->get(['id', 'type', 'slug', 'title']);

        $shared = [
            'school' => [
                'name' => $profile['name'],
                'logo_url' => $profile['logo_url'],
                'address' => $settings['address'] !== '' ? $settings['address'] : ($school->address ?? ''),
                'phone' => $settings['phone'] !== '' ? $settings['phone'] : ($school->phone ?? ''),
                'email' => $settings['email'] !== '' ? $settings['email'] : ($school->email ?? ''),
                'principal_name' => $school->principal_name ?? null,
            ],
            'settings' => $settings,
            'profileMenu' => $pages->reject(fn (WebsitePage $p) => $p->type === PageType::Contact)
                ->map(fn (WebsitePage $p) => ['title' => $p->title, 'url' => url($p->slug)])->values()->all(),
            'contactUrl' => ($contact = $pages->first(fn (WebsitePage $p) => $p->type === PageType::Contact)) ? url($contact->slug) : null,
            'socials' => array_filter([
                'Facebook' => $settings['facebook'],
                'Instagram' => $settings['instagram'],
                'YouTube' => $settings['youtube'],
                'TikTok' => $settings['tiktok'],
            ]),
            'whatsappUrl' => $settings['whatsapp'] !== '' ? 'https://wa.me/'.preg_replace('/\D/', '', $settings['whatsapp']) : null,
        ];

        request()->attributes->set(self::REQUEST_KEY, $shared);

        return $shared;
    }

    /**
     * Deskripsi meta dari HTML (maks 160 karakter).
     */
    public static function describe(?string ...$candidates): string
    {
        foreach ($candidates as $text) {
            $plain = trim((string) preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(str_replace('<', ' <', (string) $text)))));

            if ($plain !== '') {
                return Str::limit($plain, 157);
            }
        }

        return '';
    }
}
