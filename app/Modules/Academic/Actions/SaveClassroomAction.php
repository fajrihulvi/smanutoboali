<?php

namespace App\Modules\Academic\Actions;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Buat atau ubah rombel. Satu guru hanya boleh menjadi wali kelas satu rombel per tahun ajaran.
 */
class SaveClassroomAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{academic_year_id: int, name: string, grade: int, homeroom_teacher_id: int|null, capacity: int|null}  $data
     *
     * @throws ValidationException
     */
    public function handle(array $data, ?Classroom $classroom = null): Classroom
    {
        if ($data['homeroom_teacher_id'] !== null) {
            $taken = Classroom::query()
                ->where('academic_year_id', $data['academic_year_id'])
                ->where('homeroom_teacher_id', $data['homeroom_teacher_id'])
                ->when($classroom, fn ($q) => $q->whereKeyNot($classroom->getKey()))
                ->value('name');

            if ($taken) {
                throw ValidationException::withMessages([
                    'homeroom_teacher' => "Guru ini sudah menjadi wali kelas {$taken} pada tahun ajaran yang sama.",
                ]);
            }
        }

        if ($classroom && $data['capacity'] !== null && $classroom->activeEnrollments()->count() > $data['capacity']) {
            throw ValidationException::withMessages([
                'capacity' => 'Kapasitas lebih kecil dari jumlah siswa aktif di rombel ini.',
            ]);
        }

        $isNew = $classroom === null;
        $classroom ??= new Classroom;
        $original = $classroom->only(['name', 'grade', 'homeroom_teacher_id', 'capacity']);

        $classroom->fill($data)->save();

        $this->audit->record(
            'academic',
            $isNew ? 'created' : 'updated',
            ($isNew ? 'Membuat' : 'Mengubah')." rombel {$classroom->name}",
            $classroom,
            $isNew ? ['attributes' => $data] : ['old' => $original, 'attributes' => $classroom->only(array_keys($original))],
        );

        return $classroom;
    }
}
