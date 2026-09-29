<?php

namespace App\Modules\Report\Services;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\Report\Enums\ExportFormat;
use App\Modules\Report\Enums\ExportStatus;
use App\Modules\Report\Jobs\GenerateReportExport;
use App\Modules\Report\Models\ReportExport;
use App\Modules\Report\Reports\Report;
use App\Modules\Report\Reports\ReportContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Validasi filter & ekspor laporan: kecil langsung diunduh, besar lewat antrean.
 */
class ReportRunner
{
    public function __construct(private ReportRenderer $renderer, private AuditLogger $audit) {}

    /**
     * Batas baris untuk unduhan langsung; di atasnya diproses Job.
     */
    public static function directLimit(): int
    {
        return (int) config('skoolia.reports.direct_limit', 300);
    }

    /**
     * Filter tervalidasi (kunci yang tidak diisi bernilai null). Dipakai juga oleh Job.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(Report $report, ReportContext $context, array $input): array
    {
        $names = array_column($report->fields($context), 'name');
        $data = array_map(fn ($v) => $v === '' ? null : $v, Arr::only($input, $names));

        $validated = Validator::make($data, $report->rules($context), [], $report->attributes())->validate();

        return array_merge(array_fill_keys($names, null), $validated);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{mode: 'direct', content: string, filename: string, mime: string}|array{mode: 'queued', export: ReportExport}
     */
    public function export(Report $report, ReportContext $context, array $filters, ExportFormat $format): array
    {
        $data = $report->build($context, $filters);

        if ($data->count() <= self::directLimit()) {
            $this->audit->record('report', 'exported', "Mengekspor laporan {$data->title} ({$format->label()})", properties: ['report' => $report->key(), 'filters' => $filters, 'rows' => $data->count()]);

            return [
                'mode' => 'direct',
                'content' => $this->renderer->render($data, $format, $context->user),
                'filename' => $this->renderer->filename($data, $format),
                'mime' => $format->mime(),
            ];
        }

        $export = ReportExport::query()->create([
            'user_id' => $context->user->id,
            'role' => $context->role->value,
            'report' => $report->key(),
            'format' => $format,
            'filters' => $filters,
            'title' => $data->title,
            'status' => ExportStatus::Pending,
            'rows' => $data->count(),
            'expires_at' => now()->addHours(ReportExport::TTL_HOURS),
        ]);

        GenerateReportExport::dispatch($export->id);
        $this->audit->record('report', 'export_queued', "Meminta ekspor laporan {$data->title} ({$format->label()}, {$data->count()} baris)", $export);

        return ['mode' => 'queued', 'export' => $export];
    }
}
