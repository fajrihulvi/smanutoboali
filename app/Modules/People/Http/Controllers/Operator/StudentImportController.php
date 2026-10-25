<?php

namespace App\Modules\People\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\People\Actions\StartStudentImportAction;
use App\Modules\People\Exports\StudentImportTemplate;
use App\Modules\People\Http\Requests\StudentImportRequest;
use App\Modules\People\Http\Resources\StudentImportResource;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\StudentImport;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentImportController extends Controller
{
    public const PER_PAGE = 10;

    public function index(): Response
    {
        $this->authorize('create', Student::class);

        StudentImport::failStale();

        $imports = StudentImport::query()
            ->with('creator:id,name')
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return Inertia::render('People::Operator/Students/Import', [
            'imports' => StudentImportResource::collection($imports),
        ]);
    }

    public function store(StudentImportRequest $request, StartStudentImportAction $start): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $start->handle($request->file('file'), $user);

        return back()->with('success', 'File diterima dan sedang diproses. Hasil akan muncul di daftar di bawah.');
    }

    public function template(): BinaryFileResponse
    {
        $this->authorize('create', Student::class);

        return Excel::download(new StudentImportTemplate, 'template-import-siswa.xlsx');
    }
}
