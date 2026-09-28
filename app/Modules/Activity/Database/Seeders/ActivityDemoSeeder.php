<?php

namespace App\Modules\Activity\Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Activity\Actions\SaveActivityAction;
use App\Modules\Activity\Enums\ActivityStatus;
use App\Modules\Activity\Enums\ActivityType;
use App\Modules\Activity\Models\Activity;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Kegiatan demo: sekolah, kelas VII-A (dengan foto), harian, draf, dan acara bulan depan.
 */
class ActivityDemoSeeder extends Seeder
{
    public function run(SaveActivityAction $save): void
    {
        if (app()->isProduction() || Activity::query()->exists()) {
            return;
        }

        $operator = User::query()->where('email', 'operator@skoolia.test')->first();
        $guru = User::query()->where('email', 'guru@skoolia.test')->first();
        $vii = Classroom::query()->where('academic_year_id', app(ActivePeriod::class)->academicYearId())->where('name', 'VII-A')->value('id');

        if (! $operator || ! $guru || ! $vii) {
            return;
        }

        $today = CarbonImmutable::today();
        $rows = [
            [$operator, 'Upacara Hari Kesaktian Pancasila', ActivityType::School, $today->subDays(3), '07:00', '08:00', 'Lapangan sekolah', 'Seluruh siswa mengikuti upacara dengan khidmat.', [], [[30, 64, 175], [59, 130, 246]], ActivityStatus::Published],
            [$guru, 'Kunjungan Museum Timah', ActivityType::Classroom, $today->subDays(6), '08:00', '12:00', 'Museum Timah Indonesia', 'Belajar sejarah pertambangan timah di Bangka.', [$vii], [[14, 165, 233], [16, 185, 129], [245, 158, 11], [99, 102, 241], [244, 63, 94]], ActivityStatus::Published],
            [$guru, 'Literasi pagi', ActivityType::Daily, $today->isSunday() ? $today->subDay() : $today, '07:00', '07:15', 'Kelas VII-A', 'Membaca buku cerita 15 menit sebelum pelajaran.', [$vii], [], ActivityStatus::Published],
            [$guru, 'Lomba kebersihan kelas (draf)', ActivityType::Classroom, $today->addDays(2), null, null, null, null, [$vii], [], ActivityStatus::Draft],
            [$operator, 'Pentas Seni Akhir Semester', ActivityType::School, $today->addMonth()->startOfMonth()->addDays(9), '08:00', '12:00', 'Aula sekolah', 'Penampilan seni setiap kelas.', [], [], ActivityStatus::Published],
        ];

        foreach ($rows as [$actor, $title, $type, $date, $start, $end, $location, $description, $classrooms, $colors, $status]) {
            $save->handle([
                'title' => $title, 'type' => $type, 'date' => $date, 'start_time' => $start, 'end_time' => $end,
                'location' => $location, 'description' => $description, 'status' => $status,
                'classroom_ids' => $classrooms, 'student_ids' => [],
            ], $actor, array_map(fn (array $rgb) => $this->photo($title, $rgb), $colors));
        }
    }

    /**
     * @param  array{int, int, int}  $rgb
     */
    private function photo(string $label, array $rgb): UploadedFile
    {
        $image = imagecreatetruecolor(960, 720);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]));
        imagefilledrectangle($image, 0, 560, 960, 720, (int) imagecolorallocatealpha($image, 0, 0, 0, 80));
        imagestring($image, 5, 32, 620, mb_substr($label, 0, 70), (int) imagecolorallocate($image, 255, 255, 255));

        $path = tempnam(sys_get_temp_dir(), 'kegiatan').'.jpg';
        imagejpeg($image, $path, 85);
        imagedestroy($image);

        return new UploadedFile($path, 'foto.jpg', 'image/jpeg', null, true);
    }
}
