<?php

namespace App\Modules\Attendance\Actions;

use App\Models\User;
use App\Modules\Attendance\Enums\TeacherAttendanceStatus;
use App\Modules\Attendance\Models\TeacherAttendance;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Teacher;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Koreksi/input operator: status, jam masuk/pulang (hanya untuk HADIR/TERLAMBAT), catatan.
 * Nilai lama & baru dicatat di audit log.
 */
class CorrectTeacherAttendanceAction
{
    public function __construct(
        private AuditLogger $audit,
        private ActivePeriod $period,
    ) {}

    /**
     * @param  array{status: TeacherAttendanceStatus, check_in: string|null, check_out: string|null, note: string|null}  $data  jam HH:MM
     *
     * @throws ValidationException
     */
    public function handle(Teacher $teacher, CarbonImmutable $date, array $data, User $actor): TeacherAttendance
    {
        if ($date->isAfter(CarbonImmutable::today())) {
            throw ValidationException::withMessages(['date' => 'Tanggal tidak boleh di masa depan.']);
        }

        $onSite = $data['status']->isOnSite();
        $checkIn = $onSite && $data['check_in'] ? $date->setTimeFromTimeString($data['check_in']) : null;
        $checkOut = $onSite && $data['check_out'] ? $date->setTimeFromTimeString($data['check_out']) : null;

        if ($onSite && $checkIn === null) {
            throw ValidationException::withMessages(['check_in' => 'Isi jam masuk untuk status hadir/terlambat.']);
        }

        if ($checkIn !== null && $checkOut !== null && $checkOut->lessThanOrEqualTo($checkIn)) {
            throw ValidationException::withMessages(['check_out' => 'Jam pulang harus setelah jam masuk.']);
        }

        return DB::transaction(function () use ($teacher, $date, $data, $actor, $checkIn, $checkOut) {
            $record = TeacherAttendance::query()
                ->where('teacher_id', $teacher->id)
                ->whereDate('date', $date->toDateString())
                ->first() ?? new TeacherAttendance([
                    'teacher_id' => $teacher->id,
                    'date' => $date->toDateString(),
                    'academic_year_id' => $this->period->academicYearId(),
                ]);

            $old = $record->exists ? $this->snapshot($record) : null;

            $record->fill([
                'status' => $data['status'],
                'check_in_at' => $checkIn,
                'check_out_at' => $checkOut,
                'note' => $data['note'],
                'source' => TeacherAttendance::SOURCE_OPERATOR,
                'recorded_by' => $actor->id,
            ])->save();

            $this->audit->record(
                'attendance',
                'teacher_corrected',
                "Koreksi presensi guru {$teacher->name} {$date->toDateString()}: {$data['status']->label()}",
                $record,
                ['old' => $old, 'attributes' => $this->snapshot($record)],
            );

            return $record;
        });
    }

    /**
     * @return array{status: string, check_in: string|null, check_out: string|null, note: string|null}
     */
    private function snapshot(TeacherAttendance $record): array
    {
        return [
            'status' => $record->status->value,
            'check_in' => $record->check_in_at?->format('H:i'),
            'check_out' => $record->check_out_at?->format('H:i'),
            'note' => $record->note,
        ];
    }
}
