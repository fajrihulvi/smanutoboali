<?php

use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\File;
use App\Modules\GuestBook\Enums\GuestCategory;
use App\Modules\GuestBook\Enums\GuestHost;
use App\Modules\GuestBook\Enums\GuestVisitSource;
use App\Modules\GuestBook\Models\GuestVisit;
use App\Modules\Report\Reports\GuestBookReport;
use App\Modules\Report\Reports\ReportContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
    $this->travelTo('2026-10-20 09:30:00');
});

/**
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function guestPayload(array $extra = []): array
{
    return [
        'full_name' => 'Siti Aminah',
        'institution' => 'Dinas Pendidikan Bangka Selatan',
        'whatsapp' => '+62 812-3456-7890',
        'category' => GuestCategory::Government->value,
        'purpose' => 'Monitoring program sekolah penggerak.',
        'host' => GuestHost::Principal->value,
        'notes' => '',
        ...$extra,
    ];
}

describe('form publik /buku-tamu', function () {
    it('beranda menautkan buku tamu dan form tampil tanpa login', function () {
        $this->get('/')->assertOk()->assertSee('href="'.url('buku-tamu').'"', false);

        $this->get('/buku-tamu')->assertOk()
            ->assertSee('Buku Tamu')
            ->assertSee('Instansi Pemerintahan')
            ->assertSee('Wakil Kepala Sekolah')
            ->assertSee('capture="user"', false);
    });

    it('tamu mengisi buku tamu beserta foto; nomor WA dinormalkan', function () {
        $this->post('/buku-tamu', guestPayload(['photo' => UploadedFile::fake()->image('wajah.jpg', 800, 600)]))
            ->assertRedirect('/buku-tamu')
            ->assertSessionHas('guest_book_success', 'Siti Aminah');

        $visit = GuestVisit::query()->with('photo')->sole();
        expect($visit->whatsapp)->toBe('081234567890')
            ->and($visit->category)->toBe(GuestCategory::Government)
            ->and($visit->host)->toBe(GuestHost::Principal)
            ->and($visit->source)->toBe(GuestVisitSource::Website)
            ->and($visit->notes)->toBeNull()
            ->and($visit->visited_at->toDateTimeString())->toBe('2026-10-20 09:30:00')
            ->and($visit->photo?->visibility)->toBe(FileVisibility::Private);
        Storage::disk('private')->assertExists($visit->photo->path);

        $this->get('/buku-tamu')->assertSee('Terima kasih, Siti Aminah!');
    });

    it('menolak isian tidak lengkap, nomor WA salah, kategori asing, dan bot (honeypot)', function () {
        $this->post('/buku-tamu', ['full_name' => ''])
            ->assertSessionHasErrors(['full_name', 'institution', 'whatsapp', 'category', 'purpose', 'host']);
        $this->post('/buku-tamu', guestPayload(['whatsapp' => '12345', 'category' => 'ARTIS']))
            ->assertSessionHasErrors(['whatsapp', 'category']);
        $this->post('/buku-tamu', guestPayload(['photo' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors('photo');
        $this->post('/buku-tamu', guestPayload(['website' => 'http://spam.test']))
            ->assertSessionHasErrors('website');

        expect(GuestVisit::query()->count())->toBe(0);
    });

    it('pengiriman form dibatasi laju per menit', function () {
        foreach (range(1, 5) as $i) {
            $this->post('/buku-tamu', guestPayload())->assertRedirect();
        }

        $this->post('/buku-tamu', guestPayload())->assertTooManyRequests();
        expect(GuestVisit::query()->count())->toBe(5);
    });
});

describe('operator', function () {
    it('melihat daftar dengan statistik dan filter tanggal, kategori, tujuan, serta pencarian', function () {
        GuestVisit::factory()->create(['full_name' => 'Hari Ini', 'category' => GuestCategory::Parent, 'host' => GuestHost::HomeroomTeacher]);
        GuestVisit::factory()->create(['full_name' => 'Awal Bulan', 'visited_at' => '2026-10-02 10:00:00', 'category' => GuestCategory::Alumni]);
        GuestVisit::factory()->create(['full_name' => 'Bulan Lalu', 'visited_at' => '2026-09-15 10:00:00']);
        $this->actingAsRole(Role::Operator);

        $this->get('/operator/buku-tamu')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('GuestBook::Index')
            ->has('visits.data', 3)
            ->where('visits.data.0.full_name', 'Hari Ini')
            ->where('stats', ['today' => 1, 'month' => 2])
            ->where('can', ['create' => true, 'recap' => true]));

        $this->get('/operator/buku-tamu?from=2026-10-01&to=2026-10-10')
            ->assertInertia(fn (Assert $page) => $page->has('visits.data', 1)->where('visits.data.0.full_name', 'Awal Bulan'));
        $this->get('/operator/buku-tamu?category=ORANG_TUA&host=WALI_KELAS')
            ->assertInertia(fn (Assert $page) => $page->has('visits.data', 1)->where('visits.data.0.full_name', 'Hari Ini'));
        $this->get('/operator/buku-tamu?search=lalu')
            ->assertInertia(fn (Assert $page) => $page->has('visits.data', 1)->where('visits.data.0.full_name', 'Bulan Lalu'));
    });

    it('mencatat, mengubah (ganti/hapus foto), dan menghapus tamu', function () {
        $operator = $this->actingAsRole(Role::Operator);

        $this->post('/operator/buku-tamu', guestPayload([
            'visited_at' => '2026-10-20T08:15',
            'photo' => UploadedFile::fake()->image('a.jpg'),
        ]))->assertRedirect('/operator/buku-tamu')->assertSessionHasNoErrors();

        $visit = GuestVisit::query()->sole();
        expect($visit->source)->toBe(GuestVisitSource::Operator)
            ->and($visit->created_by)->toBe($operator->id)
            ->and($visit->visited_at->format('H:i'))->toBe('08:15');
        $firstPhoto = $visit->photo_file_id;

        $this->get("/operator/buku-tamu/{$visit->public_id}/ubah")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('GuestBook::Operator/Form')->where('visit.full_name', 'Siti Aminah'));

        $this->post("/operator/buku-tamu/{$visit->public_id}", guestPayload([
            'full_name' => 'Siti Aminah, S.Pd.',
            'visited_at' => '2026-10-20T08:15',
            'photo' => UploadedFile::fake()->image('b.jpg'),
        ]))->assertSessionHasNoErrors();
        $visit->refresh();
        expect($visit->full_name)->toBe('Siti Aminah, S.Pd.')
            ->and($visit->photo_file_id)->not->toBe($firstPhoto)
            ->and(File::query()->find($firstPhoto))->toBeNull();

        $this->post("/operator/buku-tamu/{$visit->public_id}", guestPayload(['visited_at' => '2026-10-20T08:15', 'remove_photo' => true]))
            ->assertSessionHasNoErrors();
        expect($visit->fresh()->photo_file_id)->toBeNull();

        $this->post("/operator/buku-tamu/{$visit->public_id}", guestPayload(['visited_at' => '2026-10-21T08:15']))
            ->assertSessionHasErrors('visited_at');

        $this->delete("/operator/buku-tamu/{$visit->public_id}")->assertRedirect();
        expect(GuestVisit::query()->count())->toBe(0)
            ->and(GuestVisit::withTrashed()->count())->toBe(1);
    });

    it('rekap PDF berdasarkan rentang tanggal lewat pusat laporan', function () {
        GuestVisit::factory()->create(['full_name' => 'Dalam Rentang', 'visited_at' => '2026-10-05 10:00:00']);
        GuestVisit::factory()->create(['full_name' => 'Luar Rentang', 'visited_at' => '2026-09-05 10:00:00']);
        $operator = $this->actingAsRole(Role::Operator);

        $pdf = $this->postJson('/operator/laporan/ekspor', ['laporan' => 'buku-tamu', 'format' => 'pdf', 'from' => '2026-10-01', 'to' => '2026-10-20'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        expect($pdf->headers->get('Content-Disposition'))->toContain('rekap-buku-tamu')
            ->and(substr((string) $pdf->getContent(), 0, 4))->toBe('%PDF');

        $data = app(GuestBookReport::class)->build(new ReportContext($operator, Role::Operator), ['from' => '2026-10-01', 'to' => '2026-10-20', 'category' => null, 'host' => null]);
        expect($data->count())->toBe(1)
            ->and($data->rows[0][3])->toBe('Dalam Rentang')
            ->and($data->meta)->toContain('Jumlah tamu: 1');

        $this->postJson('/operator/laporan/ekspor', ['laporan' => 'buku-tamu', 'format' => 'pdf', 'from' => '2026-10-20', 'to' => '2026-10-01'])
            ->assertUnprocessable()->assertJsonValidationErrors('to');
    });
});

describe('kepala sekolah & role lain', function () {
    it('kepala sekolah hanya melihat daftar dan foto, tidak bisa mengelola atau merekap', function () {
        $visit = GuestVisit::factory()->create();
        $this->actingAsRole(Role::Operator);
        $this->post("/operator/buku-tamu/{$visit->public_id}", guestPayload(['visited_at' => '2026-10-20T08:00', 'photo' => UploadedFile::fake()->image('c.jpg')]));
        $photo = $visit->fresh()->photo;

        $this->actingAsRole(Role::Principal);
        $this->get('/kepala-sekolah/buku-tamu')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('GuestBook::Index')
            ->where('base', '/kepala-sekolah/buku-tamu')
            ->where('can', ['create' => false, 'recap' => false])
            ->where('visits.data.0.can', ['update' => false, 'delete' => false]));
        $this->get("/files/{$photo->public_id}")->assertOk();

        $this->get('/operator/buku-tamu')->assertForbidden();
        $this->post('/operator/buku-tamu', guestPayload(['visited_at' => '2026-10-20T08:00']))->assertForbidden();
        $this->delete("/operator/buku-tamu/{$visit->public_id}")->assertForbidden();
        $this->postJson('/kepala-sekolah/laporan/ekspor', ['laporan' => 'buku-tamu', 'format' => 'pdf', 'from' => '2026-10-01', 'to' => '2026-10-20'])->assertForbidden();
        expect(GuestVisit::query()->count())->toBe(1);
    });

    it('guru dan orang tua tidak dapat membuka buku tamu maupun foto tamu', function (Role $role) {
        $this->actingAsRole(Role::Operator);
        $this->post('/operator/buku-tamu', guestPayload(['visited_at' => '2026-10-20T08:00', 'photo' => UploadedFile::fake()->image('d.jpg')]));
        $visit = GuestVisit::query()->with('photo')->sole();

        $this->actingAsRole($role);
        $this->get('/operator/buku-tamu')->assertForbidden();
        $this->get('/kepala-sekolah/buku-tamu')->assertForbidden();
        $this->get("/operator/buku-tamu/{$visit->public_id}/ubah")->assertForbidden();
        $this->get("/files/{$visit->photo->public_id}")->assertForbidden();
    })->with([Role::Teacher, Role::Guardian]);
});
