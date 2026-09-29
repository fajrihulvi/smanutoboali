<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Core\Enums\Role;
use App\Modules\Finance\Models\Invoice;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\ReportCard\Models\ReportCard;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Anggaran query (S33) — pengaman regresi N+1
|--------------------------------------------------------------------------
|
| Data demo lengkap di-seed, lalu halaman utama tiap role dibuka. Query yang
| sama (SQL identik, beda binding) tidak boleh berulang lebih dari
| MAX_REPEAT kali — pola khas N+1 yang lolos dari preventLazyLoading
| (query eksplisit di dalam loop).
|
*/

const MAX_REPEAT = 6;
const MAX_QUERIES = 80;

/**
 * @return array{total: int, repeats: array<string, int>}
 */
function profileQueries(Closure $request): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $request();
    $log = DB::getQueryLog();
    DB::disableQueryLog();

    $repeats = collect($log)
        ->map(fn (array $q) => preg_replace('/\s+/', ' ', $q['query']))
        ->countBy()
        ->filter(fn (int $n) => $n > MAX_REPEAT)
        ->sortDesc()
        ->all();

    return ['total' => count($log), 'repeats' => $repeats];
}

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
    $this->travelTo('2026-09-29 09:00:00');
    $this->seed(DatabaseSeeder::class);
});

it('halaman utama setiap role tidak memiliki pola N+1', function () {
    $pages = [
        [Role::Principal, 'kepsek@skoolia.test', ['/kepala-sekolah/dashboard', '/kepala-sekolah/presensi', '/kepala-sekolah/nilai', '/kepala-sekolah/raport', '/kepala-sekolah/siswa', '/kepala-sekolah/laporan', '/kepala-sekolah/keuangan', '/kepala-sekolah/kondisi-anak', '/kepala-sekolah/pengumuman']],
        [Role::Operator, 'operator@skoolia.test', ['/operator/dashboard', '/operator/master-data/siswa', '/operator/master-data/guru', '/operator/master-data/orang-tua', '/operator/master-data/kelas', '/operator/keuangan/tagihan', '/operator/keuangan/rekap', '/operator/ppdb/pendaftar', '/operator/raport', '/operator/cms/berita', '/operator/cms/galeri', '/operator/laporan', '/operator/audit-log', '/operator/users']],
        [Role::Teacher, 'guru@skoolia.test', ['/guru/dashboard', '/guru/kelas', '/guru/siswa', '/guru/presensi', '/guru/akademik/nilai', '/guru/raport', '/guru/portofolio', '/guru/kegiatan', '/guru/kondisi-anak', '/guru/chat', '/guru/laporan']],
        [Role::Guardian, 'ortu@skoolia.test', ['/orang-tua/dashboard', '/orang-tua/anak', '/orang-tua/presensi', '/orang-tua/nilai', '/orang-tua/raport', '/orang-tua/keuangan', '/orang-tua/portofolio', '/orang-tua/kegiatan', '/orang-tua/timeline', '/orang-tua/chat', '/orang-tua/pengumuman']],
    ];

    $problems = [];

    foreach ($pages as [$role, $email, $urls]) {
        $this->actingAsRole($role, User::query()->where('email', $email)->firstOrFail());

        foreach ($urls as $url) {
            $result = profileQueries(fn () => $this->get($url)->assertOk());

            if ($result['total'] > MAX_QUERIES || $result['repeats'] !== []) {
                $problems[$url] = $result;
            }
        }
    }

    // Halaman detail terberat (id dari data demo).
    $teacher = User::query()->where('email', 'guru@skoolia.test')->firstOrFail();
    $teacherProfile = Teacher::activeForUser($teacher);
    $assignment = TeachingAssignment::query()->where('teacher_id', $teacherProfile?->id)->firstOrFail();
    $homeroomClass = Classroom::query()->where('homeroom_teacher_id', $teacherProfile?->id)->firstOrFail();
    $parent = User::query()->where('email', 'ortu@skoolia.test')->firstOrFail();
    $child = Student::query()->visibleTo($parent, Role::Guardian)->firstOrFail();
    $card = ReportCard::query()->where('classroom_id', $homeroomClass->id)->firstOrFail();

    $details = [
        [Role::Operator, 'operator@skoolia.test', [
            '/operator/keuangan/tagihan/'.Invoice::query()->firstOrFail()->public_id,
            '/operator/raport/rombel/'.$homeroomClass->public_id,
            '/operator/raport/'.$card->public_id,
            '/operator/ppdb/pendaftar/'.PpdbApplicant::query()->where('status', '!=', 'DRAFT')->firstOrFail()->public_id,
            '/operator/master-data/kelas/'.$homeroomClass->public_id,
        ]],
        [Role::Teacher, 'guru@skoolia.test', [
            '/guru/akademik/nilai/'.$assignment->public_id,
            '/guru/presensi/rombel/'.$homeroomClass->public_id.'?tanggal=2026-09-28',
            '/guru/raport/rombel/'.$homeroomClass->public_id,
        ]],
        [Role::Guardian, 'ortu@skoolia.test', [
            '/orang-tua/anak/'.$child->public_id,
        ]],
    ];

    foreach ($details as [$role, $email, $urls]) {
        $this->actingAsRole($role, User::query()->where('email', $email)->firstOrFail());

        foreach ($urls as $url) {
            $result = profileQueries(fn () => $this->get($url)->assertOk());

            if ($result['total'] > MAX_QUERIES || $result['repeats'] !== []) {
                $problems[$url] = $result;
            }
        }
    }

    // Website publik
    auth()->logout();
    foreach (['/', '/berita', '/agenda', '/galeri', '/guru', '/ppdb', '/sitemap.xml'] as $url) {
        $result = profileQueries(fn () => $this->get($url)->assertOk());

        if ($result['total'] > MAX_QUERIES || $result['repeats'] !== []) {
            $problems[$url] = $result;
        }
    }

    expect($problems)->toBe([], json_encode($problems, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
});
