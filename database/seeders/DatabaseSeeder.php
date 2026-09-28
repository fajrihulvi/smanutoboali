<?php

namespace Database\Seeders;

use App\Modules\Academic\Database\Seeders\AcademicDemoSeeder;
use App\Modules\Access\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Activity\Database\Seeders\ActivityDemoSeeder;
use App\Modules\Assessment\Database\Seeders\AssessmentDemoSeeder;
use App\Modules\Attendance\Database\Seeders\AttendanceDemoSeeder;
use App\Modules\Auth\Database\Seeders\DemoUserSeeder;
use App\Modules\People\Database\Seeders\PeopleDemoSeeder;
use App\Modules\Portfolio\Database\Seeders\PortfolioDemoSeeder;
use App\Modules\ReportCard\Database\Seeders\ReportCardDemoSeeder;
use App\Modules\School\Database\Seeders\AcademicYearSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data demo lokal (php artisan migrate:fresh --seed).
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            DemoUserSeeder::class,
            AcademicYearSeeder::class,
            PeopleDemoSeeder::class,
            AcademicDemoSeeder::class,
            AttendanceDemoSeeder::class,
            AssessmentDemoSeeder::class,
            ReportCardDemoSeeder::class,
            PortfolioDemoSeeder::class,
            ActivityDemoSeeder::class,
        ]);
    }
}
