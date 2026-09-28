<?php

use App\Modules\Academic\AcademicServiceProvider;
use App\Modules\Access\AccessServiceProvider;
use App\Modules\Activity\ActivityServiceProvider;
use App\Modules\Announcement\AnnouncementServiceProvider;
use App\Modules\Assessment\AssessmentServiceProvider;
use App\Modules\Attendance\AttendanceServiceProvider;
use App\Modules\Auth\AuthServiceProvider;
use App\Modules\Chat\ChatServiceProvider;
use App\Modules\ChildCondition\ChildConditionServiceProvider;
use App\Modules\Core\CoreServiceProvider;
use App\Modules\Dashboard\DashboardServiceProvider;
use App\Modules\People\PeopleServiceProvider;
use App\Modules\Portfolio\PortfolioServiceProvider;
use App\Modules\ReportCard\ReportCardServiceProvider;
use App\Modules\School\SchoolServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AcademicServiceProvider::class,
    AccessServiceProvider::class,
    ActivityServiceProvider::class,
    AnnouncementServiceProvider::class,
    AssessmentServiceProvider::class,
    AttendanceServiceProvider::class,
    AuthServiceProvider::class,
    ChatServiceProvider::class,
    ChildConditionServiceProvider::class,
    CoreServiceProvider::class,
    DashboardServiceProvider::class,
    PeopleServiceProvider::class,
    PortfolioServiceProvider::class,
    ReportCardServiceProvider::class,
    SchoolServiceProvider::class,
    AppServiceProvider::class,
];
