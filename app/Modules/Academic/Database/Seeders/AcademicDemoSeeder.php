<?php

namespace App\Modules\Academic\Database\Seeders;

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Enums\SubjectGroup;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\School\Models\AcademicYear;
use Illuminate\Database\Seeder;

/**
 * Data demo akademik SMP tahun 2026/2027: mapel, 5 rombel, penugasan, siswa per rombel.
 * guru@ = wali kelas VII-A & mengajar Matematika; guru.ortu@ mengajar Bahasa Indonesia di VIII-A.
 * Idempoten; dilewati di produksi.
 */
class AcademicDemoSeeder extends Seeder
{
    private const SUBJECTS = [
        ['PAI', 'Pendidikan Agama Islam', SubjectGroup::General],
        ['PPKN', 'Pendidikan Pancasila', SubjectGroup::General],
        ['BIND', 'Bahasa Indonesia', SubjectGroup::General],
        ['MTK', 'Matematika', SubjectGroup::General],
        ['IPA', 'Ilmu Pengetahuan Alam', SubjectGroup::General],
        ['IPS', 'Ilmu Pengetahuan Sosial', SubjectGroup::General],
        ['BING', 'Bahasa Inggris', SubjectGroup::General],
        ['PJOK', 'Pendidikan Jasmani, Olahraga, dan Kesehatan', SubjectGroup::General],
        ['INF', 'Informatika', SubjectGroup::General],
        ['SBD', 'Seni Budaya', SubjectGroup::Elective],
        ['BDAE', 'Bahasa Daerah Bangka', SubjectGroup::LocalContent],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        foreach (self::SUBJECTS as [$code, $name, $group]) {
            Subject::query()->firstOrCreate(['code' => $code], ['name' => $name, 'group' => $group, 'is_active' => true]);
        }

        $year = AcademicYear::query()->where('name', '2026/2027')->first();

        if (! $year || Classroom::query()->where('academic_year_id', $year->id)->exists()) {
            return;
        }

        $guru = Teacher::query()->whereHas('user', fn ($q) => $q->where('email', 'guru@skoolia.test'))->first();
        $guruOrtu = Teacher::query()->whereHas('user', fn ($q) => $q->where('email', 'guru.ortu@skoolia.test'))->first();
        $others = Teacher::query()->where('is_active', true)->whereNull('user_id')->orderBy('id')->get();

        $classrooms = collect([['VII-A', 7], ['VII-B', 7], ['VIII-A', 8], ['VIII-B', 8], ['IX-A', 9]])
            ->map(fn (array $row, int $i) => Classroom::query()->create([
                'academic_year_id' => $year->id,
                'name' => $row[0],
                'grade' => $row[1],
                'capacity' => 32,
                'homeroom_teacher_id' => $i === 0 ? $guru?->id : $others->get($i)?->id,
            ]));

        $subjects = Subject::query()->orderBy('id')->get();

        foreach ($classrooms as $c => $classroom) {
            foreach ($subjects as $s => $subject) {
                $teacher = match (true) {
                    $subject->code === 'MTK' && in_array($classroom->name, ['VII-A', 'VII-B'], true) => $guru,
                    $subject->code === 'BIND' && $classroom->name === 'VIII-A' => $guruOrtu,
                    default => $others->get(($c + $s) % max(1, $others->count())),
                };

                if ($teacher) {
                    TeachingAssignment::query()->create([
                        'teacher_id' => $teacher->id,
                        'classroom_id' => $classroom->id,
                        'subject_id' => $subject->id,
                        'academic_year_id' => $year->id,
                    ]);
                }
            }
        }

        // Siswa demo dibagi merata; kakak (Aisyah) di VII-A, adik (Rafi) di VIII-A.
        $students = Student::query()->orderBy('id')->get();
        $byName = ['Aisyah Putri Hidayat' => 'VII-A', 'Rafi Pratama Hidayat' => 'VIII-A', 'Nayla Lestari' => 'VIII-A'];
        $roundRobin = 0;

        foreach ($students as $student) {
            $name = $byName[$student->name] ?? $classrooms[$roundRobin++ % $classrooms->count()]->name;
            $classroom = $classrooms->firstWhere('name', $name);

            ClassEnrollment::query()->create([
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'academic_year_id' => $year->id,
                'status' => EnrollmentStatus::Active,
            ]);
        }
    }
}
