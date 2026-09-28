<?php

namespace App\Modules\ReportCard\Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Actions\SaveGradeDescriptionAction;
use App\Modules\Assessment\Services\Gradebook;
use App\Modules\ReportCard\Actions\TransitionReportCardAction;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\ReportCard\Services\ReportCardGenerator;
use App\Modules\ReportCard\Services\SubjectConfirmations;
use App\Modules\School\Models\Semester;
use App\Support\ActivePeriod;
use Illuminate\Database\Seeder;

/**
 * Demo raport semester aktif:
 * - VIII-A: semua mapel dikonfirmasi, raport melewati seluruh alur sampai TERBIT (Rafi, anak ortu@).
 * - VII-A (wali: guru@): semua mapel dikonfirmasi kecuali Matematika (MTK, milik guru@) → raport masih DRAF.
 */
class ReportCardDemoSeeder extends Seeder
{
    public function run(
        SaveGradeDescriptionAction $descriptions,
        SubjectConfirmations $confirmations,
        ReportCardGenerator $generator,
        TransitionReportCardAction $transition,
    ): void {
        if (app()->isProduction() || ReportCard::query()->exists()) {
            return;
        }

        $period = app(ActivePeriod::class);
        $period->flush();
        $semester = Semester::query()->find($period->semesterId());
        $operator = User::query()->where('email', 'operator@skoolia.test')->first();

        if ($semester === null || $operator === null) {
            return;
        }

        foreach (['VIII-A', 'VII-A'] as $name) {
            $classroom = Classroom::query()->where('academic_year_id', $period->academicYearId())->where('name', $name)->first();

            if ($classroom === null) {
                continue;
            }

            $assignments = TeachingAssignment::query()->where('classroom_id', $classroom->id)->with('subject:id,code,name')->get();

            foreach ($assignments as $assignment) {
                if ($name === 'VII-A' && $assignment->subject->code === 'MTK') {
                    continue;
                }

                $descriptions->fillFromTemplates(Gradebook::make($assignment, $semester), $operator, overwrite: false);
                $confirmations->confirm($assignment, $semester, $operator);
            }

            $generator->generate($classroom, $semester, $operator);
            $confirmations->submitIfReady($classroom, $semester);

            ReportCard::query()->where('classroom_id', $classroom->id)->where('semester_id', $semester->id)
                ->with('student:id,name')->get()
                ->each(function (ReportCard $card) use ($name, $transition) {
                    $card->update(['homeroom_note' => "{$card->student->name} menunjukkan semangat belajar yang baik. Pertahankan dan tingkatkan kedisiplinan."]);

                    if ($name !== 'VIII-A') {
                        return;
                    }

                    foreach ([ReportCardStatus::HomeroomReviewed, ReportCardStatus::Validated, ReportCardStatus::Approved, ReportCardStatus::Published] as $target) {
                        $transition->handle($card, $target, null, 'Data demo.');
                    }
                });
        }
    }
}
