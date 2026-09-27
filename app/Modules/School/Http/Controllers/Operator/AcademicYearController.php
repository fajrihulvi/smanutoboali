<?php

namespace App\Modules\School\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\School\Actions\ActivateAcademicYearAction;
use App\Modules\School\Actions\CreateAcademicYearAction;
use App\Modules\School\Actions\DeleteAcademicYearAction;
use App\Modules\School\Actions\UpdateAcademicYearAction;
use App\Modules\School\Http\Requests\AcademicYearRequest;
use App\Modules\School\Http\Resources\AcademicYearResource;
use App\Modules\School\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    public const PER_PAGE = 10;

    public function index(): Response
    {
        $this->authorize('viewAny', AcademicYear::class);

        $years = AcademicYear::query()
            ->with('semesters')
            ->orderByDesc('start_date')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('School::Operator/AcademicYears/Index', [
            'academicYears' => AcademicYearResource::collection($years),
        ]);
    }

    public function store(AcademicYearRequest $request, CreateAcademicYearAction $create): RedirectResponse
    {
        $year = $create->handle($request->payload());

        return back()->with('success', "Tahun ajaran {$year->name} berhasil dibuat.");
    }

    public function update(AcademicYearRequest $request, AcademicYear $academicYear, UpdateAcademicYearAction $update): RedirectResponse
    {
        $update->handle($academicYear, $request->payload());

        return back()->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    public function activate(AcademicYear $academicYear, ActivateAcademicYearAction $activate): RedirectResponse
    {
        $this->authorize('activate', $academicYear);

        $activate->handle($academicYear);

        return back()->with('success', "Tahun ajaran {$academicYear->name} sekarang aktif.");
    }

    public function destroy(AcademicYear $academicYear, DeleteAcademicYearAction $delete): RedirectResponse
    {
        $this->authorize('delete', $academicYear);

        $delete->handle($academicYear);

        return back()->with('success', 'Tahun ajaran berhasil dihapus.');
    }
}
