<?php

namespace App\Modules\ReportCard\Services;

use App\Modules\Academic\Enums\SubjectGroup;
use App\Modules\Core\Models\File;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\ReportCard\Models\ReportCardDetail;
use App\Modules\School\Models\School;
use App\Modules\School\Services\SchoolLogo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Render PDF raport (dompdf, A4). Gambar (logo, tanda tangan) disematkan sebagai data URI
 * karena dompdf tidak mengambil URL remote dan file tanda tangan bersifat privat.
 */
class ReportCardPdf
{
    public function render(ReportCard $card): string
    {
        $card->loadMissing([
            'student:id,public_id,nis,nisn,name',
            'classroom.homeroomTeacher.signature',
            'semester.academicYear',
            'details', 'extracurriculars', 'achievements',
        ]);

        $school = School::current()->loadMissing(['logo', 'principalSignature']);
        $homeroom = $card->classroom->homeroomTeacher;

        return Pdf::loadView('report-card::pdf', [
            'card' => $card,
            'school' => $school,
            'logo' => SchoolLogo::dataUri($school->logo),
            'principalSignature' => $this->dataUri($school->principalSignature),
            'homeroom' => $homeroom,
            'homeroomSignature' => $this->dataUri($homeroom?->signature),
            'groups' => $card->details
                ->groupBy('subject_group')
                ->sortBy(fn ($details, string $group) => array_search(SubjectGroup::tryFrom($group), SubjectGroup::cases(), true))
                ->mapWithKeys(fn ($details, string $group) => [SubjectGroup::tryFrom($group)?->label() ?? $group => $details])
                ->map(fn ($details) => $details->sortBy(fn (ReportCardDetail $d) => $d->position)->values()),
            'isDraft' => $card->status !== ReportCardStatus::Published,
            'date' => ($card->published_at ?? now())->locale('id')->translatedFormat('j F Y'),
        ])->setPaper('a4')->output();
    }

    public function filename(ReportCard $card): string
    {
        $card->loadMissing(['student:id,nis,name', 'semester']);

        return Str::slug("raport {$card->student->nis} {$card->student->name} {$card->semester->type->value}").'.pdf';
    }

    private function dataUri(?File $file): ?string
    {
        if ($file === null || ! $file->isImage()) {
            return null;
        }

        $contents = Storage::disk($file->disk)->get($file->path);

        return $contents === null ? null : 'data:'.$file->mime_type.';base64,'.base64_encode($contents);
    }
}
