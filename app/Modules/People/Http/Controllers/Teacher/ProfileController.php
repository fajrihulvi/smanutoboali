<?php

namespace App\Modules\People\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Actions\ReplaceSignatureAction;
use App\Modules\Core\Services\FileUrlGenerator;
use App\Modules\People\Actions\UpdateTeacherAction;
use App\Modules\People\Http\Requests\UpdateOwnProfileRequest;
use App\Modules\People\Http\Resources\TeacherResource;
use App\Modules\People\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
        $teacher?->load(['photo', 'signature']);

        return Inertia::render('People::Teacher/Profile', [
            'teacher' => $teacher ? (new TeacherResource($teacher))->resolve($request) : null,
            'signatureUrl' => $teacher?->signature ? app(FileUrlGenerator::class)->url($teacher->signature) : null,
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

    public function uploadSignature(Request $request, ReplaceSignatureAction $replace): RedirectResponse
    {
        $teacher = $this->ownTeacher($request);
        abort_if($teacher === null, 404);
        $this->authorize('updateOwnProfile', $teacher);

        $request->validate(['signature' => ['required', 'file', 'max:'.ReplaceSignatureAction::MAX_KB]], [], ['signature' => 'tanda tangan']);

        /** @var UploadedFile $file */
        $file = $request->file('signature');
        $replace->handle($teacher, 'signature_file_id', $file, 'people', "guru {$teacher->name}");

        return back()->with('success', 'Tanda tangan disimpan. Dipakai di PDF raport bila Anda wali kelas.');
    }

    public function removeSignature(Request $request, ReplaceSignatureAction $replace): RedirectResponse
    {
        $teacher = $this->ownTeacher($request);
        abort_if($teacher === null, 404);
        $this->authorize('updateOwnProfile', $teacher);

        $replace->handle($teacher, 'signature_file_id', null, 'people', "guru {$teacher->name}");

        return back()->with('success', 'Tanda tangan dihapus.');
    }

    private function ownTeacher(Request $request): ?Teacher
    {
        /** @var User $user */
        $user = $request->user();

        return Teacher::query()->where('user_id', $user->getKey())->first();
    }
}
