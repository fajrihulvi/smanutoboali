<?php

namespace App\Modules\People\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\People\Actions\UpdateTeacherAction;
use App\Modules\People\Http\Requests\UpdateOwnProfileRequest;
use App\Modules\People\Http\Resources\TeacherResource;
use App\Modules\People\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Profil guru sendiri (/guru/profil): data induk read-only, kontak & foto bisa diubah.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $teacher = $this->ownTeacher($request);
        $teacher?->load('photo');

        return Inertia::render('People::Teacher/Profile', [
            'teacher' => $teacher ? (new TeacherResource($teacher))->resolve($request) : null,
        ]);
    }

    public function update(UpdateOwnProfileRequest $request, UpdateTeacherAction $update): RedirectResponse
    {
        $teacher = $this->ownTeacher($request);

        abort_if($teacher === null, 404);
        $this->authorize('updateOwnProfile', $teacher);

        $update->handle(
            $teacher,
            ['phone' => $request->optional('phone')],
            $request->file('photo'),
            $request->boolean('remove_photo'),
        );

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    private function ownTeacher(Request $request): ?Teacher
    {
        /** @var User $user */
        $user = $request->user();

        return Teacher::query()->where('user_id', $user->getKey())->first();
    }
}
