<?php

namespace App\Modules\Finance\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Finance\Actions\DeleteDiscountAction;
use App\Modules\Finance\Actions\SaveDiscountAction;
use App\Modules\Finance\Enums\DiscountKind;
use App\Modules\Finance\Http\Requests\DiscountRequest;
use App\Modules\Finance\Http\Resources\DiscountResource;
use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\StudentDiscount;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Student;
use App\Support\ActivePeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Aturan potongan/beasiswa per siswa.
 */
class DiscountController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('manage', Invoice::class);
        $search = $request->string('q')->trim()->value();

        $discounts = StudentDiscount::query()
            ->with(['student', 'feeType'])
            ->when($search !== '', fn (Builder $q) => $q->whereHas('student', fn (Builder $s) => $s->search($search)))
            ->orderByDesc('is_active')->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Finance::Operator/Discounts', [
            'discounts' => DiscountResource::collection($discounts),
            'filters' => array_filter(['q' => $search]),
            'feeTypes' => FeeType::query()->orderBy('name')->get(['public_id', 'name'])->map(fn (FeeType $f) => ['value' => $f->public_id, 'label' => $f->name])->values(),
            'kinds' => array_map(fn (DiscountKind $k) => ['value' => $k->value, 'label' => $k->label()], DiscountKind::cases()),
        ]);
    }

    public function store(DiscountRequest $request, SaveDiscountAction $save): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $studentIds = Student::query()->whereIn('public_id', (array) $request->input('students', []))->pluck('id');

        DB::transaction(function () use ($studentIds, $request, $save, $user) {
            foreach ($studentIds as $studentId) {
                $save->handle(['student_id' => (int) $studentId, ...$request->payload()], $user);
            }
        });

        return back()->with('success', "Potongan disimpan untuk {$studentIds->count()} siswa.");
    }

    public function update(DiscountRequest $request, StudentDiscount $discount, SaveDiscountAction $save): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $save->handle(['student_id' => $discount->student_id, ...$request->payload()], $user, $discount);

        return back()->with('success', 'Potongan diperbarui.');
    }

    public function destroy(StudentDiscount $discount, DeleteDiscountAction $delete): RedirectResponse
    {
        $this->authorize('manage', Invoice::class);
        $delete->handle($discount);

        return back()->with('success', 'Potongan dihapus.');
    }

    /**
     * Pencarian siswa aktif untuk StudentPicker.
     */
    public function students(Request $request, ActivePeriod $period): JsonResponse
    {
        $this->authorize('manage', Invoice::class);
        $term = $request->string('q')->trim()->value();

        $students = Student::query()
            ->where('students.status', StudentStatus::Active)
            ->search($term !== '' ? $term : null)
            ->with(['enrollments' => fn ($e) => $e->where('academic_year_id', $period->academicYearId() ?? 0)
                ->where('status', EnrollmentStatus::Active)->with('classroom:id,name')])
            ->orderBy('name')
            ->limit(30)
            ->get(['students.id', 'public_id', 'nis', 'name']);

        return response()->json(['data' => $students->map(fn (Student $s) => [
            'public_id' => $s->public_id,
            'name' => $s->name,
            'nis' => $s->nis,
            'classroom' => $s->enrollments->first()?->classroom->name,
        ])->values()]);
    }
}
