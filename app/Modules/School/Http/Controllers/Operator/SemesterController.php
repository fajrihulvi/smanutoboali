<?php

namespace App\Modules\School\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\School\Actions\ActivateSemesterAction;
use App\Modules\School\Actions\UpdateSemesterAction;
use App\Modules\School\Http\Requests\UpdateSemesterRequest;
use App\Modules\School\Models\Semester;
use Illuminate\Http\RedirectResponse;

class SemesterController extends Controller
{
    public function update(UpdateSemesterRequest $request, Semester $semester, UpdateSemesterAction $update): RedirectResponse
    {
        $update->handle($semester, $request->payload());

        return back()->with('success', "{$semester->label()} berhasil diperbarui.");
    }

    public function activate(Semester $semester, ActivateSemesterAction $activate): RedirectResponse
    {
        $this->authorize('activate', $semester->academicYear);

        $activate->handle($semester);

        return back()->with('success', "{$semester->label()} {$semester->academicYear->name} sekarang aktif.");
    }
}
