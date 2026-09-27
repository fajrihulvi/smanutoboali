<?php

namespace App\Modules\Academic\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Actions\PromoteStudentsAction;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Services\AcademicOptions;
use App\Modules\Academic\Services\PromotionPlanner;
use App\Modules\School\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Naik Kelas: preview (GET ?dari=&ke=) lalu eksekusi (POST).
 */
class PromotionController extends Controller
{
    public function create(Request $request, AcademicOptions $options, PromotionPlanner $planner): Response
    {
        $this->authorize('create', Classroom::class);

        $from = $this->year($request->string('dari')->value());
        $to = $this->year($request->string('ke')->value());

        $backwards = $from && $to && $to->start_date->lessThanOrEqualTo($from->start_date);
        $plan = $from && $to && ! $backwards ? $planner->plan($from, $to) : null;

        if ($plan !== null) {
            // id internal rombel hanya dipakai action; jangan dikirim ke klien.
            $plan['rows'] = array_map(fn (array $row) => Arr::except($row, 'source_id'), $plan['rows']);
        }

        return Inertia::render('Academic::Operator/Promotion/Create', [
            'years' => $options->years(),
            'from' => $from?->public_id,
            'to' => $to?->public_id,
            'plan' => $plan,
            'error' => $backwards ? 'Tahun ajaran tujuan harus setelah tahun ajaran asal.' : null,
        ]);
    }

    public function store(Request $request, PromoteStudentsAction $promote): RedirectResponse
    {
        $this->authorize('create', Classroom::class);

        $validated = $request->validate([
            'from' => ['required', 'string', Rule::exists(AcademicYear::class, 'public_id')],
            'to' => ['required', 'string', 'different:from', Rule::exists(AcademicYear::class, 'public_id')],
            'graduate_final_grade' => ['boolean'],
        ], [], ['from' => 'tahun ajaran asal', 'to' => 'tahun ajaran tujuan']);

        $from = AcademicYear::query()->where('public_id', $validated['from'])->firstOrFail();
        $to = AcademicYear::query()->where('public_id', $validated['to'])->firstOrFail();

        $result = $promote->handle($from, $to, $request->boolean('graduate_final_grade'));

        return to_route('operator.classrooms.index', ['tahun' => $to->public_id])->with(
            'success',
            "Naik kelas selesai: {$result['promoted']} siswa naik, {$result['graduated']} lulus, {$result['classrooms_created']} rombel dibuat di {$to->name}.",
        );
    }

    private function year(string $publicId): ?AcademicYear
    {
        return $publicId !== '' ? AcademicYear::query()->where('public_id', $publicId)->first() : null;
    }
}
