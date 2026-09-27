<?php

namespace App\Modules\Academic\Actions;

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Masukkan siswa ke rombel. Satu siswa hanya punya satu enrollment AKTIF per tahun ajaran;
 * dengan $move = true, enrollment lama ditutup (PINDAH) dan dibuat yang baru.
 */
class EnrollStudentsAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  Collection<int, Student>  $students
     * @return int jumlah siswa yang dimasukkan
     *
     * @throws ValidationException
     */
    public function handle(Classroom $classroom, Collection $students, bool $move = false): int
    {
        $inactive = $students->reject(fn (Student $s) => $s->status === StudentStatus::Active);

        if ($inactive->isNotEmpty()) {
            throw ValidationException::withMessages([
                'students' => 'Hanya siswa berstatus aktif yang bisa dimasukkan ke rombel: '.$inactive->pluck('name')->join(', ').'.',
            ]);
        }

        return DB::transaction(function () use ($classroom, $students, $move) {
            // Kunci rombel agar pengecekan kapasitas tidak balapan.
            Classroom::query()->whereKey($classroom->getKey())->lockForUpdate()->first();

            $existing = ClassEnrollment::query()
                ->with('classroom:id,name')
                ->whereIn('student_id', $students->modelKeys())
                ->where('academic_year_id', $classroom->academic_year_id)
                ->where('status', EnrollmentStatus::Active)
                ->get()
                ->keyBy('student_id');

            $alreadyHere = $existing->filter(fn (ClassEnrollment $e) => $e->classroom_id === $classroom->getKey());
            $elsewhere = $existing->reject(fn (ClassEnrollment $e) => $e->classroom_id === $classroom->getKey());

            if ($elsewhere->isNotEmpty() && ! $move) {
                $names = $students->whereIn('id', $elsewhere->keys())
                    ->map(fn (Student $s) => $s->name.' ('.$elsewhere[$s->id]->classroom->name.')')
                    ->join(', ');

                throw ValidationException::withMessages([
                    'students' => "Siswa berikut sudah punya rombel tahun ini: {$names}. Centang \"pindahkan\" untuk memindahkan.",
                ]);
            }

            $toEnroll = $students->reject(fn (Student $s) => $alreadyHere->has($s->id));

            if ($classroom->capacity !== null) {
                $after = $classroom->activeEnrollments()->count() + $toEnroll->count();

                if ($after > $classroom->capacity) {
                    throw ValidationException::withMessages([
                        'students' => "Kapasitas rombel {$classroom->name} ({$classroom->capacity}) terlampaui.",
                    ]);
                }
            }

            if ($elsewhere->isNotEmpty()) {
                ClassEnrollment::query()->whereKey($elsewhere->pluck('id'))->update(['status' => EnrollmentStatus::Moved]);
            }

            foreach ($toEnroll as $student) {
                ClassEnrollment::query()->create([
                    'student_id' => $student->getKey(),
                    'classroom_id' => $classroom->getKey(),
                    'academic_year_id' => $classroom->academic_year_id,
                    'status' => EnrollmentStatus::Active,
                ]);
            }

            if ($toEnroll->isNotEmpty()) {
                $this->audit->record('academic', 'enrolled', "Memasukkan {$toEnroll->count()} siswa ke rombel {$classroom->name}", $classroom, [
                    'students' => $toEnroll->pluck('public_id')->values()->all(),
                    'moved' => $elsewhere->count(),
                ]);
            }

            return $toEnroll->count();
        });
    }
}
