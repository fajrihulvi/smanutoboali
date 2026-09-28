<?php

namespace App\Modules\Portfolio\Database\Seeders;

use App\Models\User;
use App\Modules\People\Models\Student;
use App\Modules\Portfolio\Actions\SavePortfolioAction;
use App\Modules\Portfolio\Enums\PortfolioVisibility;
use App\Modules\Portfolio\Events\PortfolioPublished;
use App\Modules\Portfolio\Models\Portfolio;
use App\Modules\Portfolio\Models\PortfolioCategory;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;

/**
 * Portofolio demo dengan gambar sederhana buatan GD (tanpa file eksternal).
 */
class PortfolioDemoSeeder extends Seeder
{
    public function run(SavePortfolioAction $save): void
    {
        if (app()->isProduction() || Portfolio::query()->exists()) {
            return;
        }

        $guru = User::query()->where('email', 'guru@skoolia.test')->first();
        $guruOrtu = User::query()->where('email', 'guru.ortu@skoolia.test')->first();
        $aisyah = Student::query()->where('name', 'Aisyah Putri Hidayat')->first();
        $rafi = Student::query()->where('name', 'Rafi Pratama Hidayat')->first();

        if (! $guru || ! $aisyah) {
            return;
        }

        $classmates = Student::query()
            ->whereHas('enrollments', fn ($q) => $q->where('classroom_id', $aisyah->enrollments()->value('classroom_id')))
            ->whereKeyNot($aisyah->id)->limit(2)->pluck('id')->all();

        // Tanpa notifikasi saat seeding (hanya event ini yang dipalsukan; event model tetap jalan).
        Event::fakeFor(function () use ($save, $guru, $guruOrtu, $aisyah, $rafi, $classmates) {
            $this->create($save, $guru, 'PROYEK', 'Maket tata surya', 'Proyek kelompok IPA: maket tata surya dari bahan daur ulang.', 12, [$aisyah->id, ...$classmates], [[30, 64, 175], [14, 165, 233], [16, 185, 129]], true);
            $this->create($save, $guru, 'PRESTASI', 'Juara 2 Olimpiade Matematika', 'Tingkat kabupaten, Bangka Barat.', 5, [$aisyah->id], [[245, 158, 11]], true);
            $this->create($save, $guru, 'TUGAS', 'Poster pecahan (draf)', 'Belum dinilai.', 1, [$aisyah->id], [[99, 102, 241]], false);

            if ($guruOrtu && $rafi) {
                $this->create($save, $guruOrtu, 'KARYA', 'Cerpen "Pantai Pasir Padi"', 'Karya tulis Bahasa Indonesia.', 8, [$rafi->id], [[244, 63, 94]], true);
            }
        }, [PortfolioPublished::class]);
    }

    /**
     * @param  list<int>  $studentIds
     * @param  list<array{int, int, int}>  $colors
     */
    private function create(SavePortfolioAction $save, User $actor, string $category, string $title, string $description, int $daysAgo, array $studentIds, array $colors, bool $published): void
    {
        $save->handle([
            'category_id' => (int) PortfolioCategory::query()->where('code', $category)->value('id'),
            'title' => $title,
            'description' => $description,
            'date' => CarbonImmutable::today()->subDays($daysAgo),
            'visibility' => $published ? PortfolioVisibility::Published : PortfolioVisibility::Draft,
            'student_ids' => $studentIds,
        ], $actor, array_map(fn (array $rgb) => $this->image($title, $rgb), $colors));
    }

    /**
     * @param  array{int, int, int}  $rgb
     */
    private function image(string $label, array $rgb): UploadedFile
    {
        $image = imagecreatetruecolor(800, 600);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]));
        $white = (int) imagecolorallocate($image, 255, 255, 255);
        imagefilledellipse($image, 600, 180, 260, 260, (int) imagecolorallocatealpha($image, 255, 255, 255, 90));
        imagestring($image, 5, 40, 520, mb_substr($label, 0, 60), $white);

        $path = tempnam(sys_get_temp_dir(), 'porto').'.png';
        imagepng($image, $path);
        imagedestroy($image);

        return new UploadedFile($path, 'foto.png', 'image/png', null, true);
    }
}
