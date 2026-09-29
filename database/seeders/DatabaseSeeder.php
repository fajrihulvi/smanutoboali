<?php

namespace Database\Seeders;

use App\Modules\Academic\Database\Seeders\AcademicDemoSeeder;
use App\Modules\Access\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Activity\Database\Seeders\ActivityDemoSeeder;
use App\Modules\Announcement\Database\Seeders\AnnouncementDemoSeeder;
use App\Modules\Assessment\Database\Seeders\AssessmentDemoSeeder;
use App\Modules\Attendance\Database\Seeders\AttendanceDemoSeeder;
use App\Modules\Auth\Database\Seeders\DemoUserSeeder;
use App\Modules\Chat\Database\Seeders\ChatDemoSeeder;
use App\Modules\ChildCondition\Database\Seeders\ChildConditionDemoSeeder;
use App\Modules\Cms\Database\Seeders\CmsDemoSeeder;
use App\Modules\Finance\Database\Seeders\FinanceDemoSeeder;
use App\Modules\People\Database\Seeders\PeopleDemoSeeder;
use App\Modules\Portfolio\Database\Seeders\PortfolioDemoSeeder;
use App\Modules\Ppdb\Database\Seeders\PpdbDemoSeeder;
use App\Modules\ReportCard\Database\Seeders\ReportCardDemoSeeder;
use App\Modules\School\Database\Seeders\AcademicYearSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Lokal/staging: data demo (php artisan migrate:fresh --seed).
     * Produksi: hanya ProductionSeeder (role, profil sekolah, operator dari .env) — S35.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->call(ProductionSeeder::class);

            return;
        }

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
            ChatDemoSeeder::class,
            ChildConditionDemoSeeder::class,
            AnnouncementDemoSeeder::class,
            FinanceDemoSeeder::class,
            PpdbDemoSeeder::class,
            CmsDemoSeeder::class,
        ]);
    }
}
