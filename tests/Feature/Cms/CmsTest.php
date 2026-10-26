<?php

use App\Models\User;
use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Enums\PageType;
use App\Modules\Cms\Enums\PostType;
use App\Modules\Cms\Models\WebsiteBanner;
use App\Modules\Cms\Models\WebsiteCategory;
use App\Modules\Cms\Models\WebsiteGallery;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\Cms\Models\WebsitePost;
use App\Modules\Cms\Services\WebsiteSettings;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\File;
use App\Modules\People\Models\Teacher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
    $this->travelTo('2026-10-08 09:00:00');
    $this->operator = User::factory()->withRole(Role::Operator)->create();
});

/**
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function postPayload(array $extra = []): array
{
    return [
        'title' => 'Juara Lomba Sains Tingkat Kota',
        'content' => '<p>Selamat kepada tim sains.</p>',
        'status' => ContentStatus::Draft->value,
        ...$extra,
    ];
}

function newsPost(array $attributes = []): WebsitePost
{
    return WebsitePost::query()->create([
        'type' => PostType::News,
        'slug' => 'berita-'.fake()->unique()->numberBetween(1, 99999),
        'title' => 'Berita',
        'status' => ContentStatus::Draft,
        ...$attributes,
    ]);
}

describe('akses', function () {
    it('hanya pemegang cms.manage yang bisa membuka panel CMS', function (Role $role) {
        $this->actingAs(User::factory()->withRole($role)->create());

        foreach (['halaman', 'berita', 'agenda', 'galeri', 'banner', 'kategori', 'guru', 'pengaturan'] as $path) {
            $this->get("/operator/cms/{$path}")->assertForbidden();
        }
    })->with([Role::Principal, Role::Teacher, Role::Guardian]);

    it('operator tanpa cms.manage ditolak', function () {
        $this->operator->revokePermissionTo('cms.manage');
        $this->operator->roles->each->revokePermissionTo('cms.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($this->operator->fresh())->get('/operator/cms/halaman')->assertForbidden();
        $this->post('/operator/cms/berita', postPayload())->assertForbidden();
    });

    it('operator melihat halaman sistem yang disiapkan otomatis', function () {
        $this->actingAs($this->operator)->get('/operator/cms/halaman')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cms::Operator/Pages/Index')
                ->has('pages.data', 6)
                ->where('pages.data.0.is_system', true));

        foreach (['/operator/cms/berita', '/operator/cms/agenda', '/operator/cms/galeri', '/operator/cms/banner', '/operator/cms/kategori', '/operator/cms/guru', '/operator/cms/pengaturan', '/operator/cms/halaman/tambah', '/operator/cms/berita/tambah', '/operator/cms/galeri/tambah'] as $url) {
            $this->get($url)->assertOk();
        }
    });
});

describe('sanitasi HTML', function () {
    it('membuang script, event handler, iframe, dan tautan javascript', function () {
        $this->actingAs($this->operator)->post('/operator/cms/berita', postPayload([
            'content' => '<p onclick="steal()">Halo <strong>dunia</strong></p>'
                .'<script>alert(1)</script><iframe src="https://evil.test"></iframe>'
                .'<a href="javascript:alert(1)">klik</a><a href="https://skoolia.test" target="_blank">aman</a>'
                .'<img src="https://skoolia.test/a.jpg" onerror="alert(1)" alt="a"><style>p{}</style>',
        ]))->assertSessionHasNoErrors();

        $content = WebsitePost::query()->sole()->content;

        expect($content)
            ->toContain('<strong>dunia</strong>')
            ->toContain('href="https://skoolia.test"')
            ->toContain('rel="noopener noreferrer"')
            ->toContain('<img src="https://skoolia.test/a.jpg" alt="a" />')
            ->not->toContain('script')
            ->not->toContain('onclick')
            ->not->toContain('onerror')
            ->not->toContain('iframe')
            ->not->toContain('javascript:')
            ->not->toContain('<style');
    });

    it('menyimpan tabel dari editor dan menampilkannya di website dengan pembungkus gulir', function () {
        $this->actingAs($this->operator)->post('/operator/cms/halaman', [
            'title' => 'Jadwal Ekstrakurikuler',
            'slug' => 'jadwal-ekskul',
            'status' => ContentStatus::Published->value,
            // Bentuk HTML keluaran TipTap (TableKit).
            'content' => '<table style="min-width: 75px" onclick="x()"><colgroup><col style="min-width: 25px"></colgroup><tbody>'
                .'<tr><th colspan="1" rowspan="1"><p>Hari</p></th><th colspan="2" rowspan="1" style="color:red"><p>Kegiatan</p></th></tr>'
                .'<tr><td colspan="1" rowspan="1"><p>Senin</p></td><td colspan="1" rowspan="1"><p>Pramuka</p></td><td><p>Hadroh</p></td></tr>'
                .'</tbody></table>',
        ])->assertSessionHasNoErrors();

        $content = WebsitePage::query()->where('slug', 'jadwal-ekskul')->sole()->content;

        expect($content)
            ->toContain('<table>')
            ->toContain('<th colspan="2" rowspan="1"><p>Kegiatan</p></th>')
            ->toContain('<td colspan="1" rowspan="1"><p>Senin</p></td>')
            ->not->toContain('style=')
            ->not->toContain('onclick');

        auth()->logout();
        $this->get('/jadwal-ekskul')->assertOk()
            ->assertSee('<div class="my-6 overflow-x-auto"><table>', false)
            ->assertSee('</table></div>', false);
    });

    it('menyimpan isi editor kosong sebagai string kosong', function () {
        $page = WebsitePage::query()->where('slug', 'profil')->sole();

        $this->actingAs($this->operator)->put("/operator/cms/halaman/{$page->public_id}", [
            'title' => 'Profil',
            'content' => '<p></p>',
            'status' => ContentStatus::Draft->value,
        ])->assertSessionHasNoErrors();

        expect($page->fresh()->content)->toBe('');
    });
});

describe('slug', function () {
    it('dibuat otomatis dari judul dan diberi akhiran bila bentrok', function () {
        $this->actingAs($this->operator);
        $this->post('/operator/cms/berita', postPayload())->assertSessionHasNoErrors();
        $this->post('/operator/cms/berita', postPayload())->assertSessionHasNoErrors();

        expect(WebsitePost::query()->orderBy('id')->pluck('slug')->all())
            ->toBe(['juara-lomba-sains-tingkat-kota', 'juara-lomba-sains-tingkat-kota-2']);
    });

    it('slug manual harus unik & berformat benar, dan slug sistem ditolak untuk halaman', function () {
        $this->actingAs($this->operator);
        newsPost(['slug' => 'dipakai']);

        $this->post('/operator/cms/berita', postPayload(['slug' => 'dipakai']))->assertSessionHasErrors('slug');
        $this->post('/operator/cms/berita', postPayload(['slug' => 'Huruf Besar!']))->assertSessionHasErrors('slug');
        $this->post('/operator/cms/halaman', ['title' => 'Berita', 'slug' => 'berita', 'status' => 'DRAFT'])->assertSessionHasErrors('slug');
        $this->post('/operator/cms/halaman', ['title' => 'Ekstrakurikuler', 'slug' => 'ekstrakurikuler', 'status' => 'DRAFT'])->assertSessionHasNoErrors();

        expect(WebsitePage::query()->where('slug', 'ekstrakurikuler')->sole()->type)->toBe(PageType::Custom);
    });

    it('slug halaman sistem terkunci', function () {
        $page = WebsitePage::query()->where('slug', 'visi-misi')->sole();

        $this->actingAs($this->operator)->put("/operator/cms/halaman/{$page->public_id}", [
            'title' => 'Visi & Misi',
            'slug' => 'visi-baru',
            'status' => 'PUBLISHED',
        ])->assertSessionHasNoErrors();

        expect($page->fresh())->slug->toBe('visi-misi')->title->toBe('Visi & Misi')->status->toBe(ContentStatus::Published);
    });
});

describe('status & jadwal terbit', function () {
    it('mengikuti aturan transisi', function () {
        $this->actingAs($this->operator);
        $post = newsPost(['status' => ContentStatus::Review]);

        $this->patch("/operator/cms/berita/{$post->public_id}/status", ['status' => 'ARCHIVED'])->assertSessionHasErrors('status');
        $this->patch("/operator/cms/berita/{$post->public_id}/status", ['status' => 'PUBLISHED'])->assertSessionHasNoErrors();

        expect($post->fresh())
            ->status->toBe(ContentStatus::Published)
            ->published_at->toDateTimeString()->toBe('2026-10-08 09:00:00');
        expect($post->fresh()->isLive())->toBeTrue();

        $this->patch("/operator/cms/berita/{$post->public_id}/status", ['status' => 'ARCHIVED'])->assertSessionHasNoErrors();
        expect(WebsitePost::query()->live()->count())->toBe(0);
    });

    it('konten terbit terjadwal baru tampil saat waktunya tiba', function () {
        $this->actingAs($this->operator);
        $this->post('/operator/cms/agenda', postPayload([
            'title' => 'Pentas Seni',
            'status' => 'PUBLISHED',
            'publish_at' => '2026-10-10 07:00',
            'event_starts_at' => '2026-10-20 08:00',
            'event_location' => 'Aula',
        ]))->assertSessionHasNoErrors();

        $post = WebsitePost::query()->sole();
        expect($post->type)->toBe(PostType::Agenda)
            ->and($post->isScheduled())->toBeTrue()
            ->and(WebsitePost::query()->live()->count())->toBe(0);

        $this->travelTo('2026-10-10 07:00:01');
        expect(WebsitePost::query()->live()->count())->toBe(1);
    });

    it('agenda wajib punya waktu mulai; berita tidak menyimpan data acara', function () {
        $this->actingAs($this->operator);
        $this->post('/operator/cms/agenda', postPayload())->assertSessionHasErrors('event_starts_at');

        $this->post('/operator/cms/berita', postPayload(['event_starts_at' => '2026-10-20 08:00', 'event_location' => 'Aula']))->assertSessionHasNoErrors();
        expect(WebsitePost::query()->sole())->event_starts_at->toBeNull()->event_location->toBeNull();
    });

    it('berita tidak bisa dibuka lewat URL agenda', function () {
        $post = newsPost();

        $this->actingAs($this->operator)->get("/operator/cms/agenda/{$post->public_id}/ubah")->assertNotFound();
        $this->get("/operator/cms/berita/{$post->public_id}/ubah")->assertOk();
    });

    it('pratinjau menampilkan draf hanya untuk pengelola', function () {
        $post = newsPost(['title' => 'Draf Rahasia', 'content' => '<p>Isi draf</p>']);

        $this->actingAs($this->operator)->get("/operator/cms/berita/{$post->public_id}/pratinjau")
            ->assertOk()->assertSee('Draf Rahasia')->assertSee('<p>Isi draf</p>', false);

        $this->actingAs(User::factory()->withRole(Role::Teacher)->create())
            ->get("/operator/cms/berita/{$post->public_id}/pratinjau")->assertForbidden();
    });
});

describe('hapus', function () {
    it('halaman sistem tidak bisa dihapus, halaman custom bisa', function () {
        $this->actingAs($this->operator);
        $system = WebsitePage::query()->where('slug', 'kontak')->sole();
        $custom = WebsitePage::query()->create(['type' => PageType::Custom, 'slug' => 'ekskul', 'title' => 'Ekskul', 'status' => ContentStatus::Draft]);

        $this->delete("/operator/cms/halaman/{$system->public_id}")->assertForbidden();
        $this->delete("/operator/cms/halaman/{$custom->public_id}")->assertRedirect();

        expect(WebsitePage::query()->count())->toBe(6)
            ->and(WebsitePage::withTrashed()->where('slug', 'ekskul')->exists())->toBeTrue();
    });

    it('menghapus kategori tidak menghapus beritanya', function () {
        $this->actingAs($this->operator)->post('/operator/cms/kategori', ['name' => 'Prestasi'])->assertSessionHasNoErrors();
        $category = WebsiteCategory::query()->sole();
        $post = newsPost(['category_id' => $category->id]);

        $this->post('/operator/cms/kategori', ['name' => 'Prestasi'])->assertSessionHasErrors('name');
        $this->delete("/operator/cms/kategori/{$category->public_id}")->assertRedirect();

        expect($post->fresh()->category_id)->toBeNull();
    });
});

describe('gambar & galeri', function () {
    it('gambar utama disimpan publik dan bisa diganti', function () {
        $this->actingAs($this->operator)->post('/operator/cms/berita', postPayload([
            'image' => UploadedFile::fake()->image('utama.jpg', 1200, 800),
        ]))->assertSessionHasNoErrors();

        $post = WebsitePost::query()->with('featuredImage')->sole();
        $first = $post->featuredImage;
        expect($first->visibility)->toBe(FileVisibility::Public);
        Storage::disk('public')->assertExists($first->path);

        $this->put("/operator/cms/berita/{$post->public_id}", postPayload([
            'image' => UploadedFile::fake()->image('baru.png', 400, 300),
        ]))->assertSessionHasNoErrors();

        expect($post->fresh()->featured_image_id)->not->toBe($first->id)
            ->and(File::query()->find($first->id))->toBeNull();
    });

    it('menolak file bukan gambar untuk editor', function () {
        $this->actingAs($this->operator)
            ->postJson('/operator/cms/gambar-editor', ['image' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
            ->assertUnprocessable();

        $this->postJson('/operator/cms/gambar-editor', ['image' => UploadedFile::fake()->image('isi.jpg')])
            ->assertCreated()
            ->assertJsonStructure(['url', 'width', 'height']);
    });

    it('album: tambah foto, ubah keterangan & urutan, hapus foto', function () {
        $this->actingAs($this->operator)->post('/operator/cms/galeri', [
            'title' => 'Class Meeting',
            'status' => 'DRAFT',
            'uploads' => [UploadedFile::fake()->image('1.jpg'), UploadedFile::fake()->image('2.jpg'), UploadedFile::fake()->image('3.jpg')],
        ])->assertSessionHasNoErrors();

        $gallery = WebsiteGallery::query()->sole();
        [$a, $b, $c] = $gallery->photos()->pluck('public_id')->all();

        $this->put("/operator/cms/galeri/{$gallery->public_id}", [
            'title' => 'Class Meeting',
            'status' => 'PUBLISHED',
            'photos' => [['id' => $c, 'caption' => 'Final futsal'], ['id' => $a, 'caption' => '']],
            'remove_photos' => [$b],
        ])->assertSessionHasNoErrors();

        $photos = $gallery->photos()->get();
        expect($photos->pluck('public_id')->all())->toBe([$c, $a])
            ->and($photos->first()->caption)->toBe('Final futsal')
            ->and($gallery->fresh()->isLive())->toBeTrue();
    });
});

describe('banner, pengaturan, guru', function () {
    it('halaman banner menerima daftar biasa, juga saat masih kosong (temuan geladi S35)', function () {
        $this->actingAs($this->operator)->get('/operator/cms/banner')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Cms::Operator/Banners/Index')->has('banners', 0));

        WebsiteBanner::query()->create(['title' => 'PPDB', 'position' => 1, 'is_active' => true]);

        $this->get('/operator/cms/banner')->assertInertia(fn (Assert $page) => $page->has('banners', 1)->where('banners.0.title', 'PPDB'));
    });

    it('banner wajib bergambar dan hanya yang aktif dalam periode yang tayang', function () {
        $this->actingAs($this->operator);
        $this->post('/operator/cms/banner', ['title' => 'PPDB dibuka', 'is_active' => true])->assertSessionHasErrors('image');
        $this->post('/operator/cms/banner', ['title' => 'PPDB dibuka', 'is_active' => true, 'link_url' => 'javascript:alert(1)', 'image' => UploadedFile::fake()->image('b.jpg')])
            ->assertSessionHasErrors('link_url');
        $this->post('/operator/cms/banner', ['title' => 'PPDB dibuka', 'is_active' => true, 'link_url' => '/ppdb', 'ends_at' => '2026-10-31 23:59', 'image' => UploadedFile::fake()->image('b.jpg')])
            ->assertSessionHasNoErrors();

        expect(WebsiteBanner::query()->live()->count())->toBe(1);
        $this->travelTo('2026-11-01 00:00:00');
        expect(WebsiteBanner::query()->live()->count())->toBe(0);
    });

    it('menyimpan pengaturan website dan memvalidasi tautan peta', function () {
        $this->actingAs($this->operator);
        $this->put('/operator/cms/pengaturan', ['map_embed_url' => 'https://evil.test/embed'])->assertSessionHasErrors('map_embed_url');
        $this->put('/operator/cms/pengaturan', [
            'tagline' => 'Cerdas & berkarakter',
            'instagram' => 'https://instagram.com/sekolah',
            'map_embed_url' => 'https://www.google.com/maps/embed?pb=abc',
        ])->assertSessionHasNoErrors();

        $settings = app(WebsiteSettings::class);
        expect($settings->get('tagline'))->toBe('Cerdas & berkarakter')
            ->and($settings->get('instagram'))->toBe('https://instagram.com/sekolah')
            ->and($settings->get('ppdb_cta_title'))->toBe('Penerimaan Peserta Didik Baru');
    });

    it('mengatur guru yang tampil di website', function () {
        $teacher = Teacher::factory()->create(['name' => 'Siti Aminah']);

        $this->actingAs($this->operator)->put("/operator/cms/guru/{$teacher->public_id}", [
            'show_on_website' => true,
            'website_title' => 'Guru Kelas 1A',
        ])->assertSessionHasNoErrors();

        expect($teacher->fresh())->show_on_website->toBeTrue()->website_title->toBe('Guru Kelas 1A');

        $this->get('/operator/cms/guru?tampil=ya')
            ->assertInertia(fn (Assert $page) => $page->has('teachers.data', 1)->where('shownCount', 1));
    });
});
