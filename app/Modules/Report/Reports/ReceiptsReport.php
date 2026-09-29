<?php

namespace App\Modules\Report\Reports;

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use App\Modules\Finance\Enums\PaymentStatus;
use App\Modules\Finance\Models\Payment;
use App\Support\ActivePeriod;
use Illuminate\Database\Eloquent\Builder;

/**
 * Penerimaan: pembayaran terkonfirmasi dalam rentang tanggal bayar.
 */
class ReceiptsReport extends Report
{
    public function __construct(private ActivePeriod $period) {}

    public function key(): string
    {
        return 'penerimaan';
    }

    public function label(): string
    {
        return 'Penerimaan keuangan';
    }

    public function description(): string
    {
        return 'Pembayaran yang sudah dikonfirmasi beserta nomor kwitansi dan totalnya.';
    }

    protected function roles(): array
    {
        return [Role::Principal, Role::Operator];
    }

    protected function permissions(): array
    {
        return [Permission::FinanceView, Permission::FinanceManage];
    }

    public function fields(ReportContext $context): array
    {
        return [...$this->dateRangeFields(), $this->classroomField($context)];
    }

    public function rules(ReportContext $context): array
    {
        return [...$this->dateRangeRules(), 'classroom' => $this->classroomRules($context)];
    }

    public function build(ReportContext $context, array $filters): ReportData
    {
        $classroom = $context->classroom($filters['classroom'] ?? null);
        $yearId = $this->period->academicYearId() ?? 0;

        $payments = Payment::query()
            ->where('status', PaymentStatus::Confirmed)
            ->whereDateBetween('paid_on', $filters['from'], $filters['to'])
            ->when($classroom, fn (Builder $q) => $q->whereHas('invoice.student.enrollments', fn (Builder $e) => $e
                ->where('classroom_id', $classroom?->id)
                ->where('status', EnrollmentStatus::Active)))
            ->with(['invoice:id,student_id,fee_type_id,period_label', 'invoice.student:id,nis,name', 'invoice.feeType:id,name'])
            ->orderBy('paid_on')->orderBy('id')
            ->get();

        $classrooms = ClassEnrollment::query()
            ->where('academic_year_id', $yearId)
            ->where('status', EnrollmentStatus::Active)
            ->whereIn('student_id', $payments->pluck('invoice.student_id')->unique())
            ->with('classroom:id,name')
            ->get()
            ->mapWithKeys(fn (ClassEnrollment $e) => [$e->student_id => $e->classroom->name]);

        $rows = $payments->values()->map(fn (Payment $p, int $i) => [
            $i + 1,
            $p->paid_on->format('d/m/Y'),
            $p->receipt_number,
            $p->invoice->student->nis,
            $p->invoice->student->name,
            $classrooms->get($p->invoice->student_id),
            $p->invoice->feeType->name.' '.$p->invoice->period_label,
            $p->method->label(),
            $p->amount,
        ])->all();

        return new ReportData(
            title: 'Laporan Penerimaan',
            meta: [
                $this->dateLabel($filters['from'], $filters['to']),
                'Rombel: '.($classroom->name ?? 'semua rombel'),
            ],
            columns: ['No', 'Tanggal bayar', 'No. kwitansi', 'NIS', 'Nama', 'Rombel', 'Tagihan', 'Metode', 'Nominal'],
            rows: $rows,
            footer: $rows === [] ? null : [null, null, null, null, 'Total', null, null, null, (int) $payments->sum('amount')],
            numeric: [0],
            currency: [8],
            landscape: true,
        );
    }
}
