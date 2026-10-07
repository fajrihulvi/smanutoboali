<?php

namespace App\Modules\People\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\People\Actions\StartTeacherImportAction;
use App\Modules\People\Exports\TeacherImportTemplate;
use App\Modules\People\Http\Requests\TeacherImportRequest;
use App\Modules\People\Http\Resources\TeacherImportResource;
use App\Modules\People\Models\Teacher;
use App\Modules\People\Models\TeacherImport;
use App\Support\Excel\SafeValueBinder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TeacherImportController extends Controller
{
    public const PER_PAGE = 10;

    public function index(): Response
    {
        $this->authorize('create', Teacher::class);

        $imports = TeacherImport::query()
            ->with('creator:id,name')
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return Inertia::render('People::Operator/Teachers/Import', [
            'imports' => TeacherImportResource::collection($imports),
        ]);
    }

    public function store(TeacherImportRequest $request, StartTeacherImportAction $start): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $start->handle($request->file('file'), $user);

        return back()->with('success', 'File diterima dan sedang diproses. Hasil akan muncul di daftar di bawah.');
    }

    public function template(): BinaryFileResponse
    {
        $this->authorize('create', Teacher::class);

        // Binder kustom template bersifat global; kembalikan agar tidak memengaruhi impor berikutnya.
        return tap(Excel::download(new TeacherImportTemplate, 'template-import-guru.xlsx'), fn () => SafeValueBinder::reset());
    }
}
