<?php

namespace App\Modules\Attendance\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Attendance\Actions\CheckInTeacherAction;
use App\Modules\Attendance\Actions\CheckOutTeacherAction;
use App\Modules\Attendance\Services\TeacherLocationGuard;
use App\Modules\People\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Presensi masuk/pulang mandiri guru (tombol di dashboard).
 */
class SelfAttendanceController extends Controller
{
    public function checkIn(Request $request, TeacherLocationGuard $guard, CheckInTeacherAction $checkIn): RedirectResponse
    {
        $validated = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $user = $this->user($request);
        $teacher = $this->teacher($user);
        $meta = $guard->check(
            $request->ip(),
            isset($validated['lat']) ? (float) $validated['lat'] : null,
            isset($validated['lng']) ? (float) $validated['lng'] : null,
        );

        $record = $checkIn->handle($teacher, $user, $meta);

        return back()->with('success', "Presensi masuk tercatat pukul {$record->check_in_at?->format('H:i')} ({$record->status->label()}).");
    }

    public function checkOut(Request $request, CheckOutTeacherAction $checkOut): RedirectResponse
    {
        $record = $checkOut->handle($this->teacher($this->user($request)));

        return back()->with('success', "Presensi pulang tercatat pukul {$record->check_out_at?->format('H:i')}.");
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }

    private function teacher(User $user): Teacher
    {
        $teacher = Teacher::activeForUser($user);

        abort_if($teacher === null, 403, 'Akun belum terhubung ke data guru.');

        return $teacher;
    }
}
