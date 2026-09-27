<?php

use App\Modules\Core\Enums\Role;
use App\Modules\School\Actions\ActivateAcademicYearAction;
use App\Modules\School\Actions\ActivateSemesterAction;
use App\Modules\School\Enums\SemesterType;
use App\Modules\School\Models\AcademicYear;
use App\Modules\School\Models\Semester;
use App\Support\ActivePeriod;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

const TA_BASE = '/operator/master-data';

function activeYears(): array
{
    return AcademicYear::query()->where('is_active', true)->pluck('name')->all();
}

describe('aturan satu periode aktif', function () {
    it('mengaktifkan tahun ajaran baru menonaktifkan yang lama', function () {
        $old = AcademicYear::factory()->startingIn(2025)->create();
        $new = AcademicYear::factory()->startingIn(2026)->create();

        app(ActivateAcademicYearAction::class)->handle($old);
        expect(activeYears())->toBe(['2025/2026']);

        app(ActivateAcademicYearAction::class)->handle($new);

        expect(activeYears())->toBe(['2026/2027'])
            ->and(Semester::query()->where('is_active', true)->count())->toBe(1)
            ->and(Semester::query()->where('is_active', true)->first()->academic_year_id)->toBe($new->id)
            ->and(Semester::query()->where('is_active', true)->first()->type)->toBe(SemesterType::Ganjil);
    });

    it('mengaktifkan semester ikut mengaktifkan tahun ajarannya', function () {
        $old = AcademicYear::factory()->startingIn(2025)->create();
        $new = AcademicYear::factory()->startingIn(2026)->create();
        app(ActivateAcademicYearAction::class)->handle($old);

        $genap = $new->semesters()->where('type', SemesterType::Genap)->firstOrFail();
        app(ActivateSemesterAction::class)->handle($genap);

        expect(activeYears())->toBe(['2026/2027'])
            ->and(Semester::query()->where('is_active', true)->pluck('id')->all())->toBe([$genap->id]);
    });

    it('mempertahankan semester aktif saat tahun yang sama diaktifkan ulang', function () {
        $year = AcademicYear::factory()->startingIn(2026)->create();
        $genap = $year->semesters()->where('type', SemesterType::Genap)->firstOrFail();
        app(ActivateSemesterAction::class)->handle($genap);

        app(ActivateAcademicYearAction::class)->handle($year);

        expect(Semester::query()->where('is_active', true)->pluck('id')->all())->toBe([$genap->id]);
    });

    it('ActivePeriod mengikuti aktivasi (cache dibersihkan)', function () {
        $a = AcademicYear::factory()->startingIn(2025)->create();
        $b = AcademicYear::factory()->startingIn(2026)->create();
        $period = app(ActivePeriod::class);

        expect($period->academicYear())->toBeNull();

        app(ActivateAcademicYearAction::class)->handle($a);
        expect($period->academicYear()?->name)->toBe('2025/2026');

        app(ActivateAcademicYearAction::class)->handle($b);
        expect($period->academicYear()?->name)->toBe('2026/2027')
            ->and($period->semester()?->type)->toBe(SemesterType::Ganjil)
            ->and($period->academicYearId())->toBe($b->id);
    });
});

describe('halaman tahun ajaran', function () {
    it('operator melihat daftar tahun ajaran beserta semester', function () {
        AcademicYear::factory()->startingIn(2026)->create();
        $this->actingAsRole(Role::Operator);

        $this->get(TA_BASE.'/tahun-ajaran')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('School::Operator/AcademicYears/Index')
                ->where('academicYears.data.0.name', '2026/2027')
                ->has('academicYears.data.0.semesters', 2)
                ->where('academicYears.data.0.semesters.0.type', 'GANJIL')
                ->missing('academicYears.data.0.id'));
    });

    it('membuat tahun ajaran dengan dua semester default', function () {
        $this->actingAsRole(Role::Operator);

        $this->post(TA_BASE.'/tahun-ajaran', [
            'name' => '2027/2028',
            'start_date' => '2027-07-12',
            'end_date' => '2028-06-30',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $year = AcademicYear::query()->where('name', '2027/2028')->firstOrFail();
        $semesters = $year->semesters;

        expect($semesters)->toHaveCount(2)
            ->and($semesters[0]->end_date->toDateString())->toBe('2027-12-31')
            ->and($semesters[1]->start_date->toDateString())->toBe('2028-01-01')
            ->and($semesters[1]->end_date->toDateString())->toBe('2028-06-30')
            ->and($year->is_active)->toBeFalse();

        expect(Activity::query()->where('log_name', 'school')->where('event', 'created')->exists())->toBeTrue();
    });

    it('memvalidasi nama dan tanggal tahun ajaran', function () {
        AcademicYear::factory()->startingIn(2026)->create();
        $this->actingAsRole(Role::Operator);

        $this->post(TA_BASE.'/tahun-ajaran', ['name' => '2026-2027', 'start_date' => 'x', 'end_date' => ''])
            ->assertSessionHasErrors(['name', 'start_date', 'end_date']);

        $this->post(TA_BASE.'/tahun-ajaran', ['name' => '2026/2028', 'start_date' => '2026-07-01', 'end_date' => '2026-06-01'])
            ->assertSessionHasErrors(['name', 'end_date']);

        $this->post(TA_BASE.'/tahun-ajaran', ['name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30'])
            ->assertSessionHasErrors('name');
    });

    it('mengaktifkan tahun ajaran lewat halaman', function () {
        $old = AcademicYear::factory()->startingIn(2025)->create();
        $new = AcademicYear::factory()->startingIn(2026)->create();
        app(ActivateAcademicYearAction::class)->handle($old);
        $this->actingAsRole(Role::Operator);

        $this->post(TA_BASE."/tahun-ajaran/{$new->public_id}/aktifkan")->assertSessionHas('success');

        expect(activeYears())->toBe(['2026/2027']);

        $this->get('/operator/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('activePeriod.academic_year.name', '2026/2027')
            ->where('activePeriod.semester.type', 'GANJIL'));
    });

    it('mengubah tanggal semester di dalam rentang tahun ajaran', function () {
        $year = AcademicYear::factory()->startingIn(2026)->create();
        $ganjil = $year->semesters()->where('type', SemesterType::Ganjil)->firstOrFail();
        $this->actingAsRole(Role::Operator);

        $this->put(TA_BASE."/semester/{$ganjil->public_id}", ['start_date' => '2026-07-13', 'end_date' => '2026-12-20'])
            ->assertSessionHasNoErrors();
        expect($ganjil->fresh()->end_date->toDateString())->toBe('2026-12-20');

        // Di luar rentang tahun ajaran.
        $this->put(TA_BASE."/semester/{$ganjil->public_id}", ['start_date' => '2026-01-01', 'end_date' => '2026-12-20'])
            ->assertSessionHasErrors('start_date');

        // Bertumpang tindih dengan Genap.
        $this->put(TA_BASE."/semester/{$ganjil->public_id}", ['start_date' => '2026-07-13', 'end_date' => '2027-02-01'])
            ->assertSessionHasErrors('start_date');
    });

    it('tidak mengizinkan rentang tahun ajaran yang memotong semester', function () {
        $year = AcademicYear::factory()->startingIn(2026)->create();
        $this->actingAsRole(Role::Operator);

        $this->put(TA_BASE."/tahun-ajaran/{$year->public_id}", [
            'name' => '2026/2027', 'start_date' => '2026-08-01', 'end_date' => '2027-06-30',
        ])->assertSessionHasErrors('start_date');
    });

    it('menghapus tahun ajaran tidak aktif, tetapi menolak yang aktif', function () {
        $active = AcademicYear::factory()->startingIn(2026)->create();
        $inactive = AcademicYear::factory()->startingIn(2025)->create();
        app(ActivateAcademicYearAction::class)->handle($active);
        $this->actingAsRole(Role::Operator);

        $this->delete(TA_BASE."/tahun-ajaran/{$active->public_id}")->assertForbidden();
        $this->delete(TA_BASE."/tahun-ajaran/{$inactive->public_id}")->assertSessionHas('success');

        expect(AcademicYear::query()->pluck('name')->all())->toBe(['2026/2027'])
            ->and(Semester::query()->count())->toBe(2);
    });

    it('memakai public_id di URL', function () {
        $year = AcademicYear::factory()->startingIn(2026)->create();
        $this->actingAsRole(Role::Operator);

        $this->post(TA_BASE."/tahun-ajaran/{$year->id}/aktifkan")->assertNotFound();
    });

    it('role lain tidak dapat mengelola tahun ajaran', function (Role $role) {
        $year = AcademicYear::factory()->startingIn(2026)->create();
        $this->actingAsRole($role);

        $this->get(TA_BASE.'/tahun-ajaran')->assertForbidden();
        $this->post(TA_BASE."/tahun-ajaran/{$year->public_id}/aktifkan")->assertForbidden();
    })->with([Role::Principal, Role::Teacher, Role::Guardian]);

    it('periode aktif tidak dibagikan ke tamu', function () {
        $year = AcademicYear::factory()->startingIn(2026)->create();
        app(ActivateAcademicYearAction::class)->handle($year);

        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('activePeriod', null));
    });
});
