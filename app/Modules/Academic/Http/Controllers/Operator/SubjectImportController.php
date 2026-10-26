<?php

namespace App\Modules\Academic\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Actions\StartSubjectImportAction;
use App\Modules\Academic\Exports\SubjectImportTemplate;
use App\Modules\Academic\Http\Requests\SubjectImportRequest;
use App\Modules\Academic\Http\Resources\SubjectImportResource;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\SubjectImport;
use App\Support\Excel\SafeValueBinder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubjectImportController extends Controller
{
    public const PER_PAGE = 10;

    public function index(): Response
    {
        $this->authorize('create', Subject::class);

        SubjectImport::failStale();

        $imports = SubjectImport::query()
            ->with('creator:id,name')
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return Inertia::render('Academic::Operator/Subjects/Import', [
            'imports' => SubjectImportResource::collection($imports),
        ]);
    }

    public function store(SubjectImportRequest $request, StartSubjectImportAction $start): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $start->handle($request->file('file'), $user);

        return back()->with('success', 'File diterima dan sedang diproses. Hasil akan muncul di daftar di bawah.');
    }

    public function template(): BinaryFileResponse
    {
        $this->authorize('create', Subject::class);

        // Binder kustom template bersifat global; kembalikan agar tidak memengaruhi impor berikutnya.
        return tap(Excel::download(new SubjectImportTemplate, 'template-import-mapel.xlsx'), fn () => SafeValueBinder::reset());
    }
}
