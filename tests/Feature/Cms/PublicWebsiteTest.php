<?php

use App\Models\User;
use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Enums\PageType;
use App\Modules\Cms\Enums\PostType;
use App\Modules\Cms\Models\WebsiteGallery;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\Cms\Models\WebsitePost;
use App\Modules\Cms\Services\WebsiteSettings;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Teacher;
use App\Modules\Ppdb\Models\PpdbPeriod;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->travelTo('2026-10-08 09:00:00');
});

function publishPage(string $slug, array $attributes = []): WebsitePage
{
    $page = WebsitePage::query()->where('slug', $slug)->firstOrFail();
    $page->update(['status' => ContentStatus::Published, 'published_at' => now(), ...$attributes]);

    return $page;
}

function livePost(PostType $type, string $slug, array $attributes = []): WebsitePost
{
    return WebsitePost::query()->create([
        'type' => $type,
        'slug' => $slug,
        'title' => str($slug)->replace('-', ' ')->title()->value(),
        'content' => '<p>Isi '.$slug.'</p>',
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
        ...$attributes,
    ]);
}

describe('halaman publik', function () {
    it('menampilkan beranda dengan tautan Masuk ke /login', function () {
        publishPage('sambutan', ['content' => '<p>Selamat datang para orang tua.</p>']);
        livePost(PostType::News, 'juara-lomba');
        livePost(PostType::Agenda, 'pentas-seni', ['event_starts_at' => now()->addWeek()]);

        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.url('login').'"', false)
            ->assertSee('Juara Lomba')
            ->assertSee('Pentas Seni')
            ->assertSee('Selamat datang para orang tua.');
    });

    it('menampilkan halaman statis terbit dan menolak draf', function () {
        publishPage('visi-misi', ['content' => '<h2>Visi</h2><p>Unggul dan berakhlak.</p>']);

        $this->get('/visi-misi')->assertOk()->assertSee('Unggul dan berakhlak.');
        $this->get('/profil')->assertNotFound(); // masih draf
        $this->get('/tidak-ada')->assertNotFound();
    });

    it('menampilkan kontak dengan data dari pengaturan website', function () {
        publishPage('kontak');
        app(WebsiteSettings::class)->save(['phone' => '(021) 555-0101', 'whatsapp' => '0812-3456', 'map_embed_url' => 'https://www.google.com/maps/embed?pb=x']);

        $this->get('/kontak')->assertOk()
            ->assertSee('(021) 555-0101')
            ->assertSee('https://wa.me/08123456', false)
            ->assertSee('https://www.google.com/maps/embed?pb=x', false);
    });

    it('tidak menampilkan konten draf, arsip, atau yang jadwalnya belum tiba', function () {
        livePost(PostType::News, 'terbit');
        livePost(PostType::News, 'draf', ['status' => ContentStatus::Draft]);
        livePost(PostType::News, 'arsip', ['status' => ContentStatus::Archived]);
        livePost(PostType::News, 'terjadwal', ['publish_at' => now()->addDay()]);

        $this->get('/berita')->assertOk()->assertSee('Terbit')->assertDontSee('Draf')->assertDontSee('Arsip')->assertDontSee('Terjadwal');
        $this->get('/berita/terbit')->assertOk();

        foreach (['draf', 'arsip', 'terjadwal'] as $slug) {
            $this->get("/berita/{$slug}")->assertNotFound();
        }

        $this->travelTo(now()->addDays(2));
        $this->get('/berita/terjadwal')->assertOk();
    });

    it('memisahkan URL berita dan agenda', function () {
        livePost(PostType::Agenda, 'rapat-orang-tua', ['event_starts_at' => now()->addDays(3), 'event_location' => 'Aula']);

        $this->get('/agenda')->assertOk()->assertSee('Rapat Orang Tua');
        $this->get('/agenda/rapat-orang-tua')->assertOk()->assertSee('Aula');
        $this->get('/berita/rapat-orang-tua')->assertNotFound();
    });

    it('menampilkan galeri terbit', function () {
        WebsiteGallery::query()->create(['slug' => 'hut-ri', 'title' => 'HUT RI', 'status' => ContentStatus::Published]);
        WebsiteGallery::query()->create(['slug' => 'rahasia', 'title' => 'Album Rahasia', 'status' => ContentStatus::Draft]);

        $this->get('/galeri')->assertOk()->assertSee('HUT RI')->assertDontSee('Album Rahasia');
        $this->get('/galeri/hut-ri')->assertOk();
        $this->get('/galeri/rahasia')->assertNotFound();
    });

    it('hanya menampilkan guru aktif yang ditandai tampil', function () {
        Teacher::factory()->create(['name' => 'Siti Tampil', 'show_on_website' => true, 'website_title' => 'Guru Kelas 1A']);
        Teacher::factory()->create(['name' => 'Rudi Tersembunyi', 'show_on_website' => false]);
        Teacher::factory()->create(['name' => 'Tono Nonaktif', 'show_on_website' => true, 'is_active' => false]);

        $this->get('/guru')->assertOk()
            ->assertSee('Siti Tampil')->assertSee('Guru Kelas 1A')
            ->assertDontSee('Rudi Tersembunyi')->assertDontSee('Tono Nonaktif');
    });

    it('menampilkan info PPDB dengan tombol daftar saat periode dibuka', function () {
        ppdbScenario();

        $this->get('/ppdb')->assertOk()
            ->assertSee('Pendaftaran dibuka')
            ->assertSee('href="'.url('ppdb/daftar').'"', false)
            ->assertSee('href="'.url('ppdb/hasil').'"', false);

        PpdbPeriod::query()->get()->each->update(['is_active' => false]);
        $this->get('/ppdb')->assertOk()->assertSee('Belum ada periode pendaftaran');
    });

    it('rute slug tidak menimpa rute aplikasi', function () {
        $this->get('/login')->assertOk()->assertInertia(fn ($page) => $page->component('Auth::PortalSelect'));
        $this->get('/ppdb/hasil')->assertOk();
        $this->get('/operator/dashboard')->assertRedirect();
    });
});

describe('SEO', function () {
    it('mengisi meta tag dari data SEO konten', function () {
        livePost(PostType::News, 'juara-sains', ['seo_title' => 'Juara Olimpiade Sains', 'seo_description' => 'Tim sains meraih juara satu.']);

        $this->get('/berita/juara-sains')->assertOk()
            ->assertSee('<title>Juara Olimpiade Sains — ', false)
            ->assertSee('<meta name="description" content="Tim sains meraih juara satu.">', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('<link rel="canonical" href="'.url('berita/juara-sains').'">', false);
    });

    it('membuat sitemap hanya berisi konten terbit', function () {
        publishPage('profil');
        livePost(PostType::News, 'terbit');
        livePost(PostType::News, 'draf', ['status' => ContentStatus::Draft]);

        $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.url('profil').'</loc>', false)
            ->assertSee('<loc>'.url('berita/terbit').'</loc>', false)
            ->assertDontSee('berita/draf', false)
            ->assertDontSee('<loc>'.url('visi-misi').'</loc>', false);
    });

    it('robots.txt menyebut sitemap dan menutup portal', function () {
        $this->get('/robots.txt')->assertOk()
            ->assertSee('Sitemap: '.url('sitemap.xml'))
            ->assertSee('Disallow: /operator');
    });

    it('pratinjau CMS memakai template website dengan noindex', function () {
        $post = livePost(PostType::News, 'draf-rahasia', ['status' => ContentStatus::Draft]);
        $operator = User::factory()->withRole(Role::Operator)->create();

        $this->actingAs($operator)->get("/operator/cms/berita/{$post->public_id}/pratinjau")
            ->assertOk()
            ->assertSee('Pratinjau')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    });
});

describe('cache halaman', function () {
    it('menyimpan halaman untuk tamu dan membersihkannya saat konten terbit', function () {
        livePost(PostType::News, 'pertama');

        $this->get('/berita')->assertHeader('X-Page-Cache', 'MISS')->assertSee('Pertama');
        $this->get('/berita')->assertHeader('X-Page-Cache', 'HIT');

        livePost(PostType::News, 'kedua');

        $this->get('/berita')->assertHeader('X-Page-Cache', 'MISS')->assertSee('Kedua');
    });

    it('tidak menyimpan query string tak dikenal, 404, atau pengguna login', function () {
        $this->get('/berita?utm_source=x')->assertHeaderMissing('X-Page-Cache');
        $this->get('/tidak-ada')->assertNotFound()->assertHeaderMissing('X-Page-Cache');

        $this->actingAs(User::factory()->withRole(Role::Operator)->create())
            ->get('/berita')->assertOk()->assertHeaderMissing('X-Page-Cache');
    });

    it('dibersihkan saat guru ditandai tampil atau pengaturan website berubah', function () {
        $teacher = Teacher::factory()->create(['name' => 'Siti Aminah', 'show_on_website' => false]);

        $this->get('/guru')->assertHeader('X-Page-Cache', 'MISS')->assertDontSee('Siti Aminah');
        $teacher->update(['show_on_website' => true]);
        $this->get('/guru')->assertHeader('X-Page-Cache', 'MISS')->assertSee('Siti Aminah');

        app(WebsiteSettings::class)->save(['tagline' => 'Slogan baru sekolah']);
        $this->get('/guru')->assertHeader('X-Page-Cache', 'MISS')->assertSee('Slogan baru sekolah');
    });
});

it('halaman sistem tetap terdaftar dengan tipe yang benar', function () {
    expect(WebsitePage::query()->where('slug', 'kontak')->value('type'))->toBe(PageType::Contact);
});
