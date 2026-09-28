<?php

namespace App\Modules\Attendance;

use App\Modules\Attendance\Events\AttendanceRecorded;
use App\Modules\Attendance\Listeners\NotifyGuardiansOfAbsence;
use App\Providers\ModuleServiceProvider;

class AttendanceServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [
        AttendanceRecorded::class => [NotifyGuardiansOfAbsence::class],
    ];
}
