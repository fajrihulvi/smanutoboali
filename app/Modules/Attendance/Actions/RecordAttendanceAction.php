<?php

namespace App\Modules\Attendance\Actions;

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Events\AttendanceRecorded;
use App\Modules\Attendance\Models\AttendanceRecord;
use App\Modules\Attendance\Models\AttendanceSession;
use App\Modules\Attendance\Services\AttendanceAccess;
use App\Modules\Attendance\Services\AttendanceRoster;
use App\Modules\Attendance\Services\AttendanceTarget;
use App\Modules\Core\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Simpan presensi satu rombel (satu tanggal / satu jadwal). Membuat sesi bila belum ada,
 * menulis status seluruh anggota aktif rombel, lalu memicu AttendanceRecorded.
 */
class RecordAttendanceAction
{
    public function __construct(
        private AttendanceTarget $target,
        private AttendanceAccess $access,
        private AttendanceRoster $roster,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, array{status: AttendanceStatus, note: string|null}>  $entries  key = public_id siswa
     *
     * @throws ValidationException
     */
    public function handle(Classroom $classroom, CarbonImmutable $date, ?Schedule $schedule, array $entries, User $actor): AttendanceSession
    {
        $semester = $this->target->resolve($classroom, $date, $schedule);

        if (! $this->access->withinEditWindow($actor, $date)) {
            throw ValidationException::withMessages([
                'date' => 'Batas waktu mengisi/mengubah presensi tanggal ini sudah lewat. Hubungi operator untuk koreksi.',
            ]);
        }
        $students = $this->roster->students($classroom);

        $expected = $students->pluck('public_id')->sort()->values()->all();
        $given = collect(array_keys($entries))->sort()->values()->all();

        if ($expected !== $given) {
            throw ValidationException::withMessages(['records' => 'Daftar siswa berubah. Muat ulang halaman lalu isi kembali presensi.']);
        }

        return DB::transaction(function () use ($classroom, $date, $schedule, $entries, $actor, $semester, $students) {
            // Kunci baris rombel agar dua guru yang menyimpan bersamaan tidak membuat sesi ganda.
            Classroom::query()->whereKey($classroom->id)->lockForUpdate()->first();

            $session = $this->roster->session($classroom, $date, $schedule);
            $isNew = $session === null;

            $session ??= AttendanceSession::query()->create([
                'academic_year_id' => $classroom->academic_year_id,
                'semester_id' => $semester->id,
                'classroom_id' => $classroom->id,
                'date' => $date->toDateString(),
                'schedule_id' => $schedule?->id,
                'recorded_by' => $actor->id,
            ]);

            $previous = $session->records()->pluck('status', 'student_id');
            $flagged = [];

            foreach ($students as $student) {
                $entry = $entries[$student->public_id];
                $before = $previous->get($student->id);

                $record = AttendanceRecord::query()->updateOrCreate(
                    ['attendance_session_id' => $session->id, 'student_id' => $student->id],
                    ['status' => $entry['status'], 'note' => $entry['note']],
                );

                if ($entry['status']->notifiesGuardian() && $before !== $entry['status']) {
                    $flagged[] = $record->id;
                }
            }

            if (! $isNew) {
                $session->forceFill(['updated_by' => $actor->id])->touch();
            }

            $counts = collect($entries)->countBy(fn (array $entry) => $entry['status']->label());

            $this->audit->record(
                'attendance',
                $isNew ? 'recorded' : 'updated',
                ($isNew ? 'Mengisi' : 'Mengubah')." presensi {$classroom->name} {$date->toDateString()}"
                    .($schedule ? " ({$schedule->teachingAssignment->subject->name})" : ''),
                $session,
                ['counts' => $counts->all()],
            );

            AttendanceRecorded::dispatch($session, $flagged);

            return $session;
        });
    }
}
