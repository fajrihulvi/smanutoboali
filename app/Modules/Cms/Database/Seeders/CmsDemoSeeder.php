<?php

namespace App\Modules\Cms\Database\Seeders;

use App\Models\User;
use App\Modules\Cms\Actions\SaveBannerAction;
use App\Modules\Cms\Actions\SaveCategoryAction;
use App\Modules\Cms\Actions\SaveGalleryAction;
use App\Modules\Cms\Actions\SavePageAction;
use App\Modules\Cms\Actions\SavePostAction;
use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Enums\PostType;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\Cms\Models\WebsitePost;
use App\Modules\Cms\Services\WebsiteSettings;
use App\Modules\People\Models\Teacher;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Konten website demo: isi halaman sistem, berita (termasuk draf & terjadwal), agenda,
 * album galeri, banner, pengaturan, dan guru yang tampil. Gambar dibuat dengan GD.
 */
class CmsDemoSeeder extends Seeder
{
    /** @var list<string> */
    private array $tempFiles = [];

    public function run(
        SavePageAction $savePage,
        SavePostAction $savePost,
        SaveCategoryAction $saveCategory,
        SaveGalleryAction $saveGallery,
        SaveBannerAction $saveBanner,
        WebsiteSettings $settings,
    ): void {
        if (app()->isProduction() || WebsitePost::query()->exists()) {
            return;
        }

        $operator = User::query()->where('email', 'operator@skoolia.test')->first();

        if (! $operator) {
            return;
        }

        $now = CarbonImmutable::now();

        $pages = [
            'profil' => '<p>SKOOLIA adalah sekolah dasar yang berkomitmen menumbuhkan anak yang <strong>cerdas, berkarakter, dan peduli</strong>. Berdiri sejak 2005, sekolah kami melayani lebih dari 400 siswa dengan 30 guru berpengalaman.</p><h2>Sejarah singkat</h2><p>Berawal dari enam ruang kelas, kini sekolah memiliki perpustakaan, laboratorium komputer, dan lapangan olahraga.</p>',
            'visi-misi' => '<h2>Visi</h2><p>Terwujudnya generasi yang beriman, cerdas, mandiri, dan berakhlak mulia.</p><h2>Misi</h2><ol><li><p>Menyelenggarakan pembelajaran yang aktif dan menyenangkan.</p></li><li><p>Membiasakan nilai-nilai karakter dalam kegiatan sehari-hari.</p></li><li><p>Mengembangkan bakat siswa melalui kegiatan ekstrakurikuler.</p></li></ol>',
            'sambutan' => '<p>Assalamu\'alaikum warahmatullahi wabarakatuh.</p><p>Selamat datang di website resmi sekolah kami. Website ini kami hadirkan sebagai jendela informasi bagi orang tua, siswa, dan masyarakat.</p><blockquote><p>Setiap anak adalah bintang yang bersinar dengan caranya sendiri.</p></blockquote><p>Wassalamu\'alaikum warahmatullahi wabarakatuh.</p>',
            'struktur-organisasi' => '<p>Struktur organisasi sekolah terdiri dari Kepala Sekolah, Wakil Kepala Sekolah bidang Kurikulum dan Kesiswaan, Tata Usaha, Wali Kelas, dan Guru Mata Pelajaran.</p>',
            'fasilitas' => '<ul><li><p>18 ruang kelas ber-AC</p></li><li><p>Perpustakaan dengan 5.000 koleksi buku</p></li><li><p>Laboratorium komputer</p></li><li><p>Lapangan olahraga dan taman bermain</p></li><li><p>Musala dan UKS</p></li></ul>',
            'kontak' => '<p>Silakan hubungi kami melalui telepon, WhatsApp, atau datang langsung pada jam layanan.</p>',
        ];

        foreach (WebsitePage::query()->get() as $page) {
            $savePage->handle([
                'title' => $page->title,
                'slug' => null,
                'content' => $pages[$page->slug] ?? '',
                'excerpt' => null,
                'seo_title' => null,
                'seo_description' => null,
                'show_in_menu' => true,
                'position' => $page->position,
                'status' => ContentStatus::Published,
                'publish_at' => null,
                'image' => null,
                'remove_image' => false,
            ], $operator, $page);
        }

        $prestasi = $saveCategory->handle('Prestasi');
        $kegiatan = $saveCategory->handle('Kegiatan');
        $saveCategory->handle('Informasi');

        $post = fn (array $data) => [
            'slug' => null, 'excerpt' => null, 'seo_title' => null, 'seo_description' => null, 'is_featured' => false,
            'event_starts_at' => null, 'event_ends_at' => null, 'event_location' => null, 'publish_at' => null,
            'image' => null, 'remove_image' => false, 'category_id' => null, ...$data,
        ];

        $news = [
            ['Tim Sains Raih Juara 1 Olimpiade Tingkat Kota', $prestasi->id, true, 8, '#1d4ed8'],
            ['Kunjungan Edukasi ke Museum Nasional', $kegiatan->id, false, 5, '#0f766e'],
            ['Gerakan Literasi: Membaca 15 Menit Sebelum Belajar', $kegiatan->id, false, 3, '#b45309'],
            ['Siswa Kelas 5 Juara Lomba Pidato Bahasa Inggris', $prestasi->id, false, 1, '#7c3aed'],
        ];

        foreach ($news as [$title, $categoryId, $featured, $daysAgo, $color]) {
            $saved = $savePost->handle(PostType::News, $post([
                'title' => $title,
                'category_id' => $categoryId,
                'is_featured' => $featured,
                'excerpt' => 'Kabar terbaru dari kegiatan sekolah kami.',
                'content' => '<p>'.$title.'. Kegiatan ini menjadi bukti semangat belajar siswa dan dukungan orang tua.</p><p>Terima kasih kepada seluruh guru pembimbing yang telah mendampingi.</p>',
                'status' => ContentStatus::Published,
                'image' => $this->image($color),
            ]), $operator);
            $saved->update(['published_at' => $now->subDays($daysAgo)->setTime(9, 0)]);
        }

        $savePost->handle(PostType::News, $post([
            'title' => 'Persiapan Akreditasi Sekolah',
            'content' => '<p>Draf berita yang belum diterbitkan.</p>',
            'status' => ContentStatus::Review,
        ]), $operator);

        $savePost->handle(PostType::News, $post([
            'title' => 'Pembukaan Pendaftaran Ekstrakurikuler',
            'content' => '<p>Berita ini terjadwal tayang otomatis.</p>',
            'status' => ContentStatus::Published,
            'publish_at' => $now->addDays(3)->setTime(7, 0),
        ]), $operator);

        foreach ([['Pentas Seni Akhir Semester', 14, 'Aula sekolah'], ['Rapat Orang Tua Kelas 6', 7, 'Ruang kelas 6']] as [$title, $inDays, $place]) {
            $savePost->handle(PostType::Agenda, $post([
                'title' => $title,
                'content' => '<p>Seluruh orang tua dan siswa diundang untuk hadir.</p>',
                'event_starts_at' => $now->addDays($inDays)->setTime(8, 0),
                'event_ends_at' => $now->addDays($inDays)->setTime(11, 0),
                'event_location' => $place,
                'status' => ContentStatus::Published,
            ]), $operator);
        }

        $saveGallery->handle([
            'title' => 'Peringatan Hari Kemerdekaan',
            'slug' => null,
            'description' => 'Lomba dan upacara memperingati HUT RI.',
            'date' => $now->setDate((int) $now->format('Y'), 8, 17),
            'status' => ContentStatus::Published,
            'publish_at' => null,
            'photos' => [],
            'remove_photos' => [],
            'uploads' => array_map(fn ($c) => $this->image($c), ['#b91c1c', '#1d4ed8', '#15803d', '#a16207', '#6d28d9', '#0e7490']),
        ], $operator);

        $saveBanner->handle([
            'title' => 'Penerimaan Peserta Didik Baru',
            'subtitle' => 'Pendaftaran online dibuka. Daftar sekarang dan pantau hasilnya dari rumah.',
            'link_url' => '/ppdb/daftar',
            'link_label' => 'Daftar PPDB',
            'position' => 1,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
            'image' => $this->image('#1e3a8a', 1600, 700),
        ], $operator);

        $saveBanner->handle([
            'title' => 'Sekolah yang Menumbuhkan Karakter',
            'subtitle' => 'Belajar aktif, menyenangkan, dan bermakna.',
            'link_url' => '/profil',
            'link_label' => 'Kenali kami',
            'position' => 2,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
            'image' => $this->image('#0f766e', 1600, 700),
        ], $operator);

        $settings->save([
            'tagline' => 'Cerdas, berkarakter, dan peduli',
            'about' => 'Sekolah dasar yang menumbuhkan anak cerdas, berkarakter, dan peduli lingkungan.',
            'address' => 'Jl. Pendidikan No. 1, Jakarta',
            'phone' => '(021) 555-0101',
            'whatsapp' => '6281200000000',
            'email' => 'info@skoolia.test',
            'opening_hours' => 'Senin–Jumat, 07.00–15.00',
            'instagram' => 'https://instagram.com/skoolia',
            'youtube' => 'https://youtube.com/@skoolia',
        ]);

        Teacher::query()->where('is_active', true)->orderBy('id')->limit(8)->get()
            ->each(fn (Teacher $t, int $i) => $t->update([
                'show_on_website' => true,
                'website_title' => $i === 0 ? 'Wakil Kepala Sekolah' : 'Guru',
            ]));

        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }
    }

    /**
     * Gambar polos berwarna (demo) sebagai UploadedFile.
     */
    private function image(string $hex, int $width = 1200, int $height = 800): UploadedFile
    {
        $img = imagecreatetruecolor($width, $height);
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x') ?? [30, 58, 138];
        imagefill($img, 0, 0, (int) imagecolorallocate($img, (int) $r, (int) $g, (int) $b));

        // Aksen lingkaran agar gambar tidak terlalu polos.
        $light = (int) imagecolorallocatealpha($img, 255, 255, 255, 100);
        imagefilledellipse($img, (int) ($width * 0.78), (int) ($height * 0.3), (int) ($height * 0.9), (int) ($height * 0.9), $light);
        imagefilledellipse($img, (int) ($width * 0.15), (int) ($height * 0.85), (int) ($height * 0.6), (int) ($height * 0.6), $light);

        $path = (string) tempnam(sys_get_temp_dir(), 'cms');
        imagejpeg($img, $path, 85);
        imagedestroy($img);
        $this->tempFiles[] = $path;

        return new UploadedFile($path, 'demo.jpg', 'image/jpeg', null, true);
    }
}
