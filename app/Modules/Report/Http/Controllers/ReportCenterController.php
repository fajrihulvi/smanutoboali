<?php

namespace App\Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Report\Enums\ExportFormat;
use App\Modules\Report\Models\ReportExport;
use App\Modules\Report\Reports\Report;
use App\Modules\Report\Reports\ReportContext;
use App\Modules\Report\Reports\ReportData;
use App\Modules\Report\Reports\ReportRegistry;
use App\Modules\Report\Services\ReportRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Pusat laporan (kepala sekolah, operator, guru). Route default `base` & `role` per portal.
 */
class ReportCenterController extends Controller
{
    public const PREVIEW_ROWS = 50;

    public function __construct(private ReportRegistry $registry, private ReportRunner $runner) {}

    public function index(Request $request): Response
    {
        $context = $this->context($request);
        $reports = $this->registry->available($context);
        abort_if($reports === [], 403);

        $report = $this->pick($reports, $request->string('laporan')->value());
        $fields = $report->fields($context);
        $values = [];

        foreach ($fields as $field) {
            $input = $request->query($field['name']);
            $values[$field['name']] = is_string($input) ? $input : $field['default'];
        }

        $preview = null;
        $errors = [];

        try {
            $preview = $this->preview($report->build($context, $this->runner->validate($report, $context, $values)));
        } catch (ValidationException $e) {
            $errors = array_map(fn (array $messages) => $messages[0], $e->errors());
        }

        return Inertia::render('Report::Center', [
            'base' => $this->base($request),
            'reports' => array_map(fn (Report $r) => ['key' => $r->key(), 'label' => $r->label(), 'description' => $r->description()], $reports),
            'selected' => $report->key(),
            'fields' => $fields,
            'values' => $values,
            'preview' => $preview,
            'previewErrors' => (object) $errors,
            'directLimit' => ReportRunner::directLimit(),
            'exports' => $this->exports($context->user),
        ]);
    }

    /**
     * POST: unduhan langsung (file) atau 202 JSON bila diproses antrean. Dipanggil lewat fetch.
     */
    public function export(Request $request): HttpResponse|JsonResponse
    {
        $context = $this->context($request);
        $request->validate([
            'laporan' => ['required', 'string'],
            'format' => ['required', Rule::enum(ExportFormat::class)],
        ]);

        $report = $this->registry->find($context, $request->string('laporan')->value());
        abort_if($report === null, 403);

        $filters = $this->runner->validate($report, $context, $request->except(['laporan', 'format']));
        $result = $this->runner->export($report, $context, $filters, ExportFormat::from($request->string('format')->value()));

        if ($result['mode'] === 'queued') {
            return response()->json([
                'queued' => true,
                'message' => "Laporan berisi {$result['export']->rows} baris sedang disiapkan. Anda akan diberi tahu saat siap diunduh.",
            ], 202);
        }

        return response($result['content'], 200, [
            'Content-Type' => $result['mime'],
            'Content-Disposition' => 'attachment; filename="'.$result['filename'].'"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * @param  list<Report>  $reports
     */
    private function pick(array $reports, string $key): Report
    {
        foreach ($reports as $report) {
            if ($report->key() === $key) {
                return $report;
            }
        }

        return $reports[0];
    }

    /**
     * @return array{columns: list<string>, rows: list<list<string>>, footer: list<string>|null, numeric: list<int>, total: int}
     */
    private function preview(ReportData $data): array
    {
        $format = fn (array $row) => array_map(fn ($cell, int $i) => $data->display($i, $cell), $row, array_keys($row));

        return [
            'title' => $data->title,
            'meta' => $data->meta,
            'columns' => $data->columns,
            'rows' => array_map($format, array_slice($data->rows, 0, self::PREVIEW_ROWS)),
            'footer' => $data->footer ? array_map(fn ($cell, int $i) => $cell === null ? '' : $data->display($i, $cell), $data->footer, array_keys($data->footer)) : null,
            'numeric' => array_values(array_filter(array_keys($data->columns), fn (int $i) => $data->isNumeric($i))),
            'total' => $data->count(),
        ];
    }

    /**
     * Ekspor antrean milik pengguna 24 jam terakhir.
     *
     * @return list<array<string, mixed>>
     */
    private function exports(User $user): array
    {
        return ReportExport::query()
            ->where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (ReportExport $e) => [
                'public_id' => $e->public_id,
                'title' => $e->title,
                'format' => $e->format->label(),
                'status' => ['value' => $e->status->value, 'label' => $e->status->label()],
                'rows' => $e->rows,
                'created_at' => $e->created_at->toIso8601String(),
                'expires_at' => $e->expires_at->toIso8601String(),
                'download_url' => $e->isDownloadable() ? "/laporan/ekspor/{$e->public_id}" : null,
            ])->values()->all();
    }

    private function context(Request $request): ReportContext
    {
        /** @var User $user */
        $user = $request->user();
        /** @var string $role */
        $role = $request->route()?->defaults['role'] ?? Role::Operator->value;

        return new ReportContext($user, Role::from($role));
    }

    private function base(Request $request): string
    {
        /** @var string $base */
        $base = $request->route()?->defaults['base'] ?? '/operator/laporan';

        return $base;
    }
}
