<?php

namespace App\Modules\Finance\Services;

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Finance\Enums\InvoiceStatus;
use App\Modules\Finance\Enums\PaymentStatus;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Rekap keuangan: ringkasan, tunggakan per rombel & per siswa, penerimaan per bulan & jenis biaya.
 * Tunggakan = sisa (total − terbayar) tagihan UNPAID/PENDING/FAILED.
 */
class FinanceReport
{
    public const PER_PAGE = 20;

    public function __construct(private ActivePeriod $period) {}

    /**
     * @return array{outstanding: int, outstanding_invoices: int, overdue: int, received_this_month: int, pending_verification: int}
     */
    public function summary(CarbonImmutable $today): array
    {
        $outstanding = Invoice::query()->outstanding();

        return [
            'outstanding' => (int) (clone $outstanding)->sum(DB::raw('total - paid_amount')),
            'outstanding_invoices' => (clone $outstanding)->count(),
            'overdue' => (int) (clone $outstanding)->where('due_date', '<', $today->toDateString())->sum(DB::raw('total - paid_amount')),
            'received_this_month' => (int) Payment::query()->where('status', PaymentStatus::Confirmed)
                ->whereDateBetween('paid_on', $today->startOfMonth()->toDateString(), $today->endOfMonth()->toDateString())
                ->sum('amount'),
            'pending_verification' => Payment::query()->where('status', PaymentStatus::Pending)->count(),
        ];
    }

    /**
     * @return list<array{classroom: string, students: int, invoices: int, amount: int}>
     */
    public function outstandingByClassroom(): array
    {
        return $this->outstandingWithClassroom()
            ->selectRaw("COALESCE(classrooms.name, '(tanpa rombel)') as classroom_name, COUNT(DISTINCT invoices.student_id) as students, COUNT(*) as invoices, SUM(invoices.total - invoices.paid_amount) as amount")
            ->groupBy('classrooms.name', 'classrooms.grade')
            ->orderBy('classrooms.grade')->orderBy('classrooms.name')
            ->get()
            ->map(fn ($row) => [
                'classroom' => (string) $row->classroom_name,
                'students' => (int) $row->students,
                'invoices' => (int) $row->invoices,
                'amount' => (int) $row->amount,
            ])->values()->all();
    }

    /**
     * Tunggakan per siswa (terbesar dulu), opsional per rombel & cari nama/NIS.
     *
     * @return LengthAwarePaginator<int, array{nis: string, name: string, classroom: string|null, invoices: int, amount: int, oldest_due: string|null}>
     */
    public function outstandingStudents(?string $search, ?string $classroomPublicId, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $paginator = $this->outstandingStudentsQuery($search, $classroomPublicId)->paginate($perPage)->withQueryString();

        return $paginator->through(fn ($row) => $this->studentRow($row));
    }

    /**
     * Semua baris tunggakan per siswa (untuk export).
     *
     * @return list<array{nis: string, name: string, classroom: string|null, invoices: int, amount: int, oldest_due: string|null}>
     */
    public function allOutstandingStudents(?string $classroomPublicId = null): array
    {
        return $this->outstandingStudentsQuery(null, $classroomPublicId)->get()->map(fn ($row) => $this->studentRow($row))->values()->all();
    }

    /**
     * Penerimaan (pembayaran terkonfirmasi) per bulan dan jenis biaya dalam rentang.
     *
     * @return array{months: list<array{month: string, total: int}>, fee_types: list<array{name: string, total: int}>, total: int}
     */
    public function receipts(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $base = fn () => DB::table('payments')
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->join('fee_types', 'fee_types.id', '=', 'invoices.fee_type_id')
            ->where('payments.status', PaymentStatus::Confirmed->value)
            ->whereNull('payments.deleted_at')
            ->whereDateBetween('payments.paid_on', $from->toDateString(), $to->toDateString());

        $months = $base()
            ->selectRaw('SUBSTR(payments.paid_on, 1, 7) as month, SUM(payments.amount) as total')
            ->groupByRaw('SUBSTR(payments.paid_on, 1, 7)')
            ->orderBy('month')
            ->get()
            ->map(fn ($r) => ['month' => (string) $r->month, 'total' => (int) $r->total])->values()->all();

        $feeTypes = $base()
            ->selectRaw('fee_types.name as name, SUM(payments.amount) as total')
            ->groupBy('fee_types.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['name' => (string) $r->name, 'total' => (int) $r->total])->values()->all();

        return ['months' => $months, 'fee_types' => $feeTypes, 'total' => array_sum(array_column($months, 'total'))];
    }

    /**
     * Tagihan belum lunas + rombel aktif siswanya (tahun ajaran aktif).
     */
    private function outstandingWithClassroom(): QueryBuilder
    {
        $yearId = $this->period->academicYearId() ?? 0;

        return DB::table('invoices')
            ->whereNull('invoices.deleted_at')
            ->whereIn('invoices.status', [InvoiceStatus::Unpaid->value, InvoiceStatus::Pending->value, InvoiceStatus::Failed->value])
            ->leftJoin('class_enrollments', fn (JoinClause $j) => $j
                ->on('class_enrollments.student_id', '=', 'invoices.student_id')
                ->where('class_enrollments.academic_year_id', $yearId)
                ->where('class_enrollments.status', EnrollmentStatus::Active->value))
            ->leftJoin('classrooms', 'classrooms.id', '=', 'class_enrollments.classroom_id');
    }

    private function outstandingStudentsQuery(?string $search, ?string $classroomPublicId): QueryBuilder
    {
        return $this->outstandingWithClassroom()
            ->join('students', 'students.id', '=', 'invoices.student_id')
            ->when(filled($search), fn (QueryBuilder $q) => $q->where(fn (QueryBuilder $w) => $w
                ->where('students.name', 'like', "%{$search}%")->orWhere('students.nis', 'like', "%{$search}%")))
            ->when(filled($classroomPublicId), fn (QueryBuilder $q) => $q->where('classrooms.public_id', $classroomPublicId))
            ->selectRaw('students.nis, students.name, classrooms.name as classroom_name, COUNT(*) as invoices, SUM(invoices.total - invoices.paid_amount) as amount, MIN(invoices.due_date) as oldest_due')
            ->groupBy('students.id', 'students.nis', 'students.name', 'classrooms.name')
            ->orderByDesc('amount')
            ->orderBy('students.name');
    }

    /**
     * @return array{nis: string, name: string, classroom: string|null, invoices: int, amount: int, oldest_due: string|null}
     */
    private function studentRow(object $row): array
    {
        return [
            'nis' => (string) $row->nis,
            'name' => (string) $row->name,
            'classroom' => $row->classroom_name !== null ? (string) $row->classroom_name : null,
            'invoices' => (int) $row->invoices,
            'amount' => (int) $row->amount,
            'oldest_due' => $row->oldest_due !== null ? substr((string) $row->oldest_due, 0, 10) : null,
        ];
    }
}
