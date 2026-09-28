<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\People\Models\Guardian;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Akun orang tua aktif per siswa (penerima notifikasi keuangan).
 */
class StudentGuardians
{
    /**
     * @param  list<int>  $studentIds
     * @return Collection<int, Collection<int, User>> key = student_id
     */
    public function accounts(array $studentIds): Collection
    {
        $guardians = Guardian::query()
            ->whereNotNull('user_id')
            ->whereHas('user', fn (Builder $u) => $u->where('is_active', true))
            ->whereHas('students', fn (Builder $s) => $s->whereIn('students.id', $studentIds))
            ->with(['user', 'students:id'])
            ->get();

        $map = collect();

        foreach ($guardians as $guardian) {
            foreach ($guardian->students as $student) {
                if (in_array($student->id, $studentIds, true) && $guardian->user !== null) {
                    $map[$student->id] = ($map[$student->id] ?? collect())->push($guardian->user)->unique('id')->values();
                }
            }
        }

        return $map;
    }
}
