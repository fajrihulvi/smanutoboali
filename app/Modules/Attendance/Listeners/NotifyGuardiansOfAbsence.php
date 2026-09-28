<?php

namespace App\Modules\Attendance\Listeners;

use App\Models\User;
use App\Modules\Attendance\Events\AttendanceRecorded;
use App\Modules\Attendance\Models\AttendanceRecord;
use App\Modules\Attendance\Notifications\StudentAbsenceNotification;
use App\Modules\People\Models\Guardian;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Kirim notifikasi ke akun orang tua (aktif) siswa yang baru berstatus ALPA/TERLAMBAT.
 * Berjalan di antrean (driver database, diproses scheduler).
 */
class NotifyGuardiansOfAbsence implements ShouldQueue
{
    public function handle(AttendanceRecorded $event): void
    {
        if ($event->newlyFlagged === []) {
            return;
        }

        $session = $event->session->loadMissing(['classroom:id,name', 'schedule.teachingAssignment.subject:id,name']);
        $context = 'Rombel '.$session->classroom->name
            .($session->schedule ? ' · '.$session->schedule->teachingAssignment->subject->name : '');

        $records = AttendanceRecord::query()
            ->whereKey($event->newlyFlagged)
            ->with(['student:id,public_id,name', 'student.guardians.user'])
            ->get();

        foreach ($records as $record) {
            $users = $record->student->guardians
                ->map(fn (Guardian $guardian) => $guardian->user)
                ->filter(fn (?User $user) => $user !== null && $user->is_active);

            foreach ($users as $user) {
                $user->notify(new StudentAbsenceNotification(
                    $record->student->name,
                    $record->student->public_id,
                    $record->status,
                    $session->date,
                    $context,
                    $record->note,
                ));
            }
        }
    }
}
