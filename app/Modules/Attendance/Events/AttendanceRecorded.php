<?php

namespace App\Modules\Attendance\Events;

use App\Modules\Attendance\Models\AttendanceSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Presensi rombel disimpan. $newlyFlagged = id catatan yang BARU berstatus ALPA/TERLAMBAT
 * (sebelumnya belum ada atau berstatus lain) — hanya ini yang memicu notifikasi.
 */
class AttendanceRecorded
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<int>  $newlyFlagged
     */
    public function __construct(
        public AttendanceSession $session,
        public array $newlyFlagged,
    ) {}
}
