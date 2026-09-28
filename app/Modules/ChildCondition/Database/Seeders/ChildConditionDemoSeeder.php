<?php

namespace App\Modules\ChildCondition\Database\Seeders;

use App\Models\User;
use App\Modules\ChildCondition\Actions\RespondConditionAction;
use App\Modules\ChildCondition\Actions\SubmitConditionAction;
use App\Modules\ChildCondition\Actions\TransitionConditionAction;
use App\Modules\ChildCondition\Enums\ConditionStatus;
use App\Modules\ChildCondition\Enums\ConditionType;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\ChildCondition\Services\ConditionTeachers;
use App\Modules\People\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Laporan demo: satu baru (sakit hari ini), satu sudah ditanggapi, satu selesai.
 * Notifikasi in-app ikut terbentuk agar lonceng guru/orang tua berisi contoh.
 */
class ChildConditionDemoSeeder extends Seeder
{
    public function run(SubmitConditionAction $submit, RespondConditionAction $respond, TransitionConditionAction $transition, ConditionTeachers $teachers): void
    {
        if (app()->isProduction() || ChildCondition::query()->exists()) {
            return;
        }

        $ortu = User::query()->where('email', 'ortu@skoolia.test')->first();
        $aisyah = Student::query()->where('name', 'Aisyah Putri Hidayat')->first();
        $homeroom = $aisyah ? $teachers->homeroomOf($aisyah) : null;
        $guru = $homeroom?->user;

        if (! $ortu || ! $aisyah || ! $guru) {
            return;
        }

        $today = CarbonImmutable::today();

        $closed = $submit->handle($ortu, $aisyah, [
            'type' => ConditionType::FoodAllergy,
            'description' => 'Aisyah alergi udang dan kacang. Mohon diperhatikan saat ada kegiatan makan bersama.',
            'valid_from' => $today->subDays(6),
            'valid_until' => $today->addDays(24),
        ]);
        $respond->handle($closed, $guru, 'Baik Pak, sudah kami catat dan sampaikan ke guru lain.');
        $transition->handle($closed, ConditionStatus::Closed, $guru);

        $responded = $submit->handle($ortu, $aisyah, [
            'type' => ConditionType::Emotional,
            'description' => 'Beberapa hari ini Aisyah terlihat murung karena neneknya dirawat di rumah sakit.',
            'valid_from' => $today->subDays(2),
            'valid_until' => null,
        ]);
        $respond->handle($responded, $guru, 'Terima kasih infonya, Pak. Kami akan lebih memperhatikan Aisyah di kelas.');

        $submit->handle($ortu, $aisyah, [
            'type' => ConditionType::Sick,
            'description' => 'Demam sejak semalam, sudah diperiksa dokter. Mohon izin istirahat 2 hari.',
            'valid_from' => $today,
            'valid_until' => $today->addDay(),
        ]);
    }
}
