<?php

namespace App\Modules\School\Database\Seeders;

use App\Modules\School\Actions\ActivateAcademicYearAction;
use App\Modules\School\Actions\CreateAcademicYearAction;
use App\Modules\School\Models\AcademicYear;
use Illuminate\Database\Seeder;

/**
 * Data demo: 2025/2026 (lalu) dan 2026/2027 (aktif, semester Ganjil). Idempoten.
 * Di produksi periode dibuat operator lewat Master Data › Tahun Ajaran.
 */
class AcademicYearSeeder extends Seeder
{
    public function run(CreateAcademicYearAction $create, ActivateAcademicYearAction $activate): void
    {
        if (app()->isProduction()) {
            return;
        }

        foreach ([2025, 2026] as $start) {
            $name = $start.'/'.($start + 1);

            if (! AcademicYear::query()->where('name', $name)->exists()) {
                $create->handle([
                    'name' => $name,
                    'start_date' => "{$start}-07-13",
                    'end_date' => ($start + 1).'-06-26',
                ]);
            }
        }

        if (! AcademicYear::query()->where('is_active', true)->exists()) {
            $activate->handle(AcademicYear::query()->where('name', '2026/2027')->firstOrFail());
        }
    }
}
