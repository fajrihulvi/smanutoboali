<?php

namespace App\Modules\People\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\People\Actions\AttachGuardianAction;
use App\Modules\People\Actions\CreateGuardianAction;
use App\Modules\People\Actions\DetachGuardianAction;
use App\Modules\People\Actions\SetPrimaryGuardianAction;
use App\Modules\People\Enums\GuardianRelationship;
use App\Modules\People\Http\Requests\AttachGuardianRequest;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tab "Orang Tua" di detail siswa: hubungkan, jadikan utama, lepaskan.
 */
class StudentGuardianController extends Controller
{
    public function store(
        AttachGuardianRequest $request,
        Student $student,
        CreateGuardianAction $createGuardian,
        AttachGuardianAction $attach,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $student, $createGuardian, $attach) {
            $guardian = $request->string('mode')->value() === 'new'
                ? $createGuardian->handle([
                    'name' => $request->string('new_name')->trim()->value(),
                    'phone' => $request->filled('new_phone') ? $request->string('new_phone')->trim()->value() : null,
                    'email' => $request->filled('new_email') ? $request->string('new_email')->trim()->lower()->value() : null,
                ])['guardian']
                : Guardian::query()->where('public_id', $request->string('guardian')->value())->firstOrFail();

            $attach->handle(
                $student,
                $guardian,
                GuardianRelationship::from($request->string('relationship')->value()),
                $request->boolean('is_primary'),
            );
        });

        return to_route('operator.students.edit', ['student' => $student, 'tab' => 'orang-tua'])
            ->with('success', 'Orang tua berhasil dihubungkan.');
    }

    public function makePrimary(Student $student, Guardian $guardian, SetPrimaryGuardianAction $setPrimary): RedirectResponse
    {
        $this->authorize('update', $student);
        abort_unless($student->guardians()->whereKey($guardian->getKey())->exists(), 404);

        $setPrimary->handle($student, $guardian);

        return back()->with('success', "{$guardian->name} menjadi orang tua utama.");
    }

    public function destroy(Student $student, Guardian $guardian, DetachGuardianAction $detach): RedirectResponse
    {
        $this->authorize('update', $student);
        abort_unless($student->guardians()->whereKey($guardian->getKey())->exists(), 404);

        $detach->handle($student, $guardian);

        return back()->with('success', "{$guardian->name} dilepas dari data siswa.");
    }

    /**
     * Pencarian orang tua untuk dialog "hubungkan" (maks 10 hasil).
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Guardian::class);

        $term = $request->string('q')->trim()->value();

        $guardians = Guardian::query()
            ->search($term !== '' ? $term : null)
            ->withCount('students')
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'public_id', 'name', 'phone', 'email']);

        return response()->json([
            'data' => $guardians->map(fn (Guardian $guardian) => [
                'public_id' => $guardian->public_id,
                'name' => $guardian->name,
                'phone' => $guardian->phone,
                'email' => $guardian->email,
                'students_count' => (int) $guardian->getAttribute('students_count'),
            ]),
        ]);
    }
}
