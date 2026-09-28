<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Announcement\Notifications\AnnouncementPublishedNotification;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
    $this->travelTo('2026-09-28 10:00:00');
});

/**
 * Rombel VII-A (wali kelas + orang tua siswa) dan VIII-B (guru + orang tua lain).
 *
 * @return array<string, mixed>
 */
function announcementScenario(): array
{
    $year = activeAcademicYear();
    $teacherA = User::factory()->withRole(Role::Teacher)->create();
    $classA = Classroom::factory()->for($year)->create(['name' => 'VII-A', 'homeroom_teacher_id' => Teacher::factory()->withAccount($teacherA)->create()->id]);
    $parentA = User::factory()->withRole(Role::Guardian)->create();
    enrollIn($classA, Student::factory()->withGuardian(Guardian::factory()->withAccount($parentA)->create())->create());

    $teacherB = User::factory()->withRole(Role::Teacher)->create();
    $classB = Classroom::factory()->for($year)->create(['name' => 'VIII-B']);
    assignTeaching($classB, Teacher::factory()->withAccount($teacherB)->create());
    $parentB = User::factory()->withRole(Role::Guardian)->create();
    enrollIn($classB, Student::factory()->withGuardian(Guardian::factory()->withAccount($parentB)->create())->create());

    return [
        'classA' => $classA,
        'classB' => $classB,
        'teacherA' => $teacherA,
        'teacherB' => $teacherB,
        'parentA' => $parentA,
        'parentB' => $parentB,
        'operator' => User::factory()->withRole(Role::Operator)->create(),
        'principal' => User::factory()->withRole(Role::Principal)->create(),
    ];
}

/**
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function announcementPayload(array $extra = []): array
{
    return [
        'title' => 'Libur Maulid Nabi',
        'body' => "Sekolah libur pada Senin.\nMasuk kembali Selasa.",
        'audience' => 'SEMUA',
        'roles' => [],
        'classrooms' => [],
        'pinned' => false,
        'publish' => 'now',
        ...$extra,
    ];
}

describe('kelola pengumuman', function () {
    it('operator menerbitkan pengumuman untuk semua dengan lampiran; semua penerima diberi tahu', function () {
        $s = announcementScenario();
        Notification::fake();
        $this->actingAsRole(Role::Operator, $s['operator']);

        $this->post('/operator/pengumuman', announcementPayload([
            'files' => [UploadedFile::fake()->createWithContent('edaran.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF")],
        ]))->assertSessionHasNoErrors()->assertRedirect('/operator/pengumuman');

        $announcement = Announcement::query()->sole();
        expect($announcement->isPublished())->toBeTrue()
            ->and($announcement->notified_at)->not->toBeNull()
            ->and($announcement->attachments()->sole()->visibility->value)->toBe('private');

        Notification::assertSentTo([$s['teacherA'], $s['teacherB'], $s['parentA'], $s['parentB'], $s['principal']], AnnouncementPublishedNotification::class);
        Notification::assertNotSentTo($s['operator'], AnnouncementPublishedNotification::class);
        Notification::assertSentTo($s['parentA'], AnnouncementPublishedNotification::class,
            fn ($n) => $n->toArray($s['parentA'])['url'] === "/orang-tua/pengumuman/{$announcement->public_id}");
    });

    it('draf dan terjadwal tidak memberi tahu; scheduler menerbitkan tepat waktu, sekali saja', function () {
        $s = announcementScenario();
        Notification::fake();
        $this->actingAsRole(Role::Principal, $s['principal']);

        $this->post('/kepala-sekolah/pengumuman', announcementPayload(['publish' => 'draft', 'title' => 'Draf']))->assertSessionHasNoErrors();
        $this->post('/kepala-sekolah/pengumuman', announcementPayload(['publish' => 'schedule', 'publish_at' => '2026-09-29T07:00', 'title' => 'Terjadwal']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'dijadwalkan'));
        Notification::assertNothingSent();

        $this->artisan('announcements:publish-due')->assertSuccessful();
        Notification::assertNothingSent();

        $this->travelTo('2026-09-29 07:01:00');
        $this->artisan('announcements:publish-due')->assertSuccessful();
        $this->artisan('announcements:publish-due')->assertSuccessful();
        Notification::assertSentToTimes($s['parentA'], AnnouncementPublishedNotification::class, 1);
    });

    it('memvalidasi target & jadwal', function () {
        $s = announcementScenario();
        $this->actingAsRole(Role::Operator, $s['operator']);

        $this->post('/operator/pengumuman', announcementPayload(['audience' => 'PERAN']))->assertSessionHasErrors('roles');
        $this->post('/operator/pengumuman', announcementPayload(['audience' => 'PERAN', 'roles' => ['applicant']]))->assertSessionHasErrors('roles.0');
        $this->post('/operator/pengumuman', announcementPayload(['audience' => 'ROMBEL']))->assertSessionHasErrors('classrooms');
        $this->post('/operator/pengumuman', announcementPayload(['publish' => 'schedule', 'publish_at' => '2026-09-01T07:00']))->assertSessionHasErrors('publish_at');
        $this->post('/operator/pengumuman', announcementPayload(['title' => '']))->assertSessionHasErrors('title');

        expect(Announcement::query()->count())->toBe(0);
    });

    it('guru dan orang tua tidak bisa mengelola pengumuman', function () {
        $s = announcementScenario();

        $this->actingAsRole(Role::Teacher, $s['teacherA']);
        $this->get('/operator/pengumuman')->assertForbidden();
        $this->post('/operator/pengumuman', announcementPayload())->assertForbidden();

        $this->actingAsRole(Role::Guardian, $s['parentA']);
        $this->post('/kepala-sekolah/pengumuman', announcementPayload())->assertForbidden();
        expect(Announcement::query()->count())->toBe(0);
    });

    it('mengubah pengumuman terbit tidak mengirim notifikasi ulang; hapus ikut menghapus lampiran', function () {
        $s = announcementScenario();
        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post('/operator/pengumuman', announcementPayload(['files' => [UploadedFile::fake()->image('poster.jpg')]]));
        $announcement = Announcement::query()->sole();
        $publishedAt = $announcement->publish_at;

        Notification::fake();
        $this->travel(1)->hours();
        $this->put("/operator/pengumuman/{$announcement->public_id}", announcementPayload(['title' => 'Libur (revisi)', 'pinned' => true]))->assertSessionHasNoErrors();
        Notification::assertNothingSent();
        expect($announcement->fresh()->title)->toBe('Libur (revisi)')
            ->and($announcement->fresh()->pinned)->toBeTrue()
            ->and($announcement->fresh()->publish_at->equalTo($publishedAt))->toBeTrue();

        $this->get('/operator/pengumuman')->assertInertia(fn (Assert $page) => $page
            ->component('Announcement::Manage/Index')
            ->where('announcements.data.0.status.value', 'TERBIT')
            ->missing('announcements.data.0.id'));

        $this->delete("/operator/pengumuman/{$announcement->public_id}")->assertRedirect('/operator/pengumuman');
        expect(Announcement::query()->count())->toBe(0)
            ->and($announcement->attachments()->count())->toBe(0);
    });
});

describe('membaca pengumuman', function () {
    it('target rombel: hanya orang tua & guru rombel itu yang melihat dan diberi tahu', function () {
        $s = announcementScenario();
        Notification::fake();
        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post('/operator/pengumuman', announcementPayload(['audience' => 'ROMBEL', 'classrooms' => [$s['classA']->public_id], 'title' => 'Info VII-A']));
        $announcement = Announcement::query()->sole();

        Notification::assertSentTo([$s['parentA'], $s['teacherA']], AnnouncementPublishedNotification::class);
        Notification::assertNotSentTo([$s['parentB'], $s['teacherB'], $s['principal']], AnnouncementPublishedNotification::class);

        $this->actingAsRole(Role::Guardian, $s['parentA']);
        $this->get('/orang-tua/pengumuman')->assertInertia(fn (Assert $page) => $page
            ->component('Announcement::Read/Index')
            ->has('announcements.data', 1));
        $this->get("/orang-tua/pengumuman/{$announcement->public_id}")->assertOk();

        $this->actingAsRole(Role::Guardian, $s['parentB']);
        $this->get('/orang-tua/pengumuman')->assertInertia(fn (Assert $page) => $page->has('announcements.data', 0));
        $this->get("/orang-tua/pengumuman/{$announcement->public_id}")->assertForbidden();

        $this->actingAsRole(Role::Teacher, $s['teacherB']);
        $this->get("/guru/pengumuman/{$announcement->public_id}")->assertForbidden();
        $this->actingAsRole(Role::Teacher, $s['teacherA']);
        $this->get("/guru/pengumuman/{$announcement->public_id}")->assertOk();
    });

    it('target peran: guru saja; draf & terjadwal tidak terlihat pembaca; pin di atas', function () {
        $s = announcementScenario();
        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post('/operator/pengumuman', announcementPayload(['audience' => 'PERAN', 'roles' => ['teacher'], 'title' => 'Rapat guru']));
        $this->travel(1)->minutes();
        $this->post('/operator/pengumuman', announcementPayload(['title' => 'Umum baru']));
        $this->post('/operator/pengumuman', announcementPayload(['title' => 'Penting', 'pinned' => true]));
        $this->post('/operator/pengumuman', announcementPayload(['title' => 'Draf', 'publish' => 'draft']));
        $this->post('/operator/pengumuman', announcementPayload(['title' => 'Nanti', 'publish' => 'schedule', 'publish_at' => '2026-10-01T07:00']));
        $teacherOnly = Announcement::query()->where('title', 'Rapat guru')->sole();
        $draft = Announcement::query()->where('title', 'Draf')->sole();

        $this->actingAsRole(Role::Teacher, $s['teacherA']);
        $this->get('/guru/pengumuman')->assertInertia(fn (Assert $page) => $page
            ->has('announcements.data', 3)
            ->where('announcements.data.0.title', 'Penting'));
        $this->get("/guru/pengumuman/{$draft->public_id}")->assertForbidden();

        $this->actingAsRole(Role::Guardian, $s['parentA']);
        $this->get('/orang-tua/pengumuman')->assertInertia(fn (Assert $page) => $page->has('announcements.data', 2));
        $this->get("/orang-tua/pengumuman/{$teacherOnly->public_id}")->assertForbidden();
    });
});
