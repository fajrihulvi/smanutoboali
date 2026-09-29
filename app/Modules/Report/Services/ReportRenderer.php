<?php

namespace App\Modules\Report\Services;

use App\Models\User;
use App\Modules\Core\Models\File;
use App\Modules\Report\Enums\ExportFormat;
use App\Modules\Report\Exports\ReportSheet;
use App\Modules\Report\Reports\ReportData;
use App\Modules\School\Models\School;
use App\Support\Excel\SafeValueBinder;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Render ReportData ke PDF (dompdf, kop sekolah) atau Excel (xlsx).
 */
class ReportRenderer
{
    public function render(ReportData $data, ExportFormat $format, User $requester): string
    {
        $school = School::current()->loadMissing('logo');

        return match ($format) {
            ExportFormat::Excel => tap(Excel::raw(new ReportSheet($data, $school->name), ExcelWriter::XLSX), fn () => SafeValueBinder::reset()),
            ExportFormat::Pdf => Pdf::loadView('report::pdf', [
                'data' => $data,
                'school' => $school,
                'logo' => $this->dataUri($school->logo),
                'printedAt' => CarbonImmutable::now()->translatedFormat('j F Y, H.i'),
                'printedBy' => $requester->name,
            ])->setPaper('a4', $data->landscape ? 'landscape' : 'portrait')->output(),
        };
    }

    public function filename(ReportData $data, ExportFormat $format): string
    {
        return Str::slug($data->title.' '.CarbonImmutable::now()->format('Y-m-d Hi')).'.'.$format->value;
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
