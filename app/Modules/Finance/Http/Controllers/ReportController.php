<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Finance\Exports\OutstandingExport;
use App\Modules\Finance\Exports\ReceiptsExport;
use App\Modules\Finance\Services\FinanceReport;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Rekap keuangan (operator & kepala sekolah, hanya-baca) + export Excel.
 * Rentang penerimaan default = tahun ajaran aktif.
 */
class ReportController extends Controller
{
    public function index(Request $request, FinanceReport $report, ActivePeriod $period): Response
    {
        [$from, $to] = $this->range($request, $period);
        $search = $request->string('q')->trim()->value();
        $classroom = $request->string('rombel')->value();
        /** @var string $base */
        $base = $request->route()?->defaults['base'] ?? '/operator/keuangan/rekap';

        return Inertia::render('Finance::Shared/Report', [
            'base' => $base,
            'summary' => $report->summary(CarbonImmutable::today()),
            'byClassroom' => $report->outstandingByClassroom(),
            'students' => JsonResource::collection($report->outstandingStudents($search ?: null, $classroom ?: null)),
            'receipts' => $report->receipts($from, $to),
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'filters' => array_filter(['q' => $search, 'rombel' => $classroom]),
            'classrooms' => Classroom::query()->where('academic_year_id', $period->academicYearId() ?? 0)->orderBy('grade')->orderBy('name')
                ->get(['public_id', 'name'])->map(fn (Classroom $c) => ['value' => $c->public_id, 'label' => $c->name])->values(),
        ]);
    }

    public function exportOutstanding(Request $request, FinanceReport $report): BinaryFileResponse
    {
        $classroom = $request->string('rombel')->value();

        return Excel::download(new OutstandingExport($report->allOutstandingStudents($classroom ?: null)), 'tunggakan-'.now()->format('Ymd').'.xlsx');
    }

    public function exportReceipts(Request $request, ActivePeriod $period): BinaryFileResponse
    {
        [$from, $to] = $this->range($request, $period);

        return Excel::download(new ReceiptsExport($from, $to), "penerimaan-{$from->format('Ymd')}-{$to->format('Ymd')}.xlsx");
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function range(Request $request, ActivePeriod $period): array
    {
        $parse = fn (string $key) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->string($key)->value()) === 1
            ? CarbonImmutable::parse($request->string($key)->value())
            : null;

        $year = $period->academicYear();
        $from = $parse('dari') ?? $year->start_date ?? CarbonImmutable::today()->startOfYear();
        $to = $parse('sampai') ?? $year->end_date ?? CarbonImmutable::today()->endOfYear();

        return $from->lte($to) ? [$from, $to] : [$to, $from];
    }
}
