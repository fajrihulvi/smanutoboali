<?php

namespace App\Modules\People\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\People\Actions\UpdateGuardianAction;
use App\Modules\People\Http\Requests\UpdateOwnProfileRequest;
use App\Modules\People\Http\Resources\GuardianResource;
use App\Modules\People\Models\Guardian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Profil orang tua sendiri (/orang-tua/profil): telepon, pekerjaan, alamat.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $guardian = $this->ownGuardian($request);

        return Inertia::render('People::Guardian/Profile', [
            'guardian' => $guardian ? (new GuardianResource($guardian))->resolve($request) : null,
        ]);
    }

    public function update(UpdateOwnProfileRequest $request, UpdateGuardianAction $update): RedirectResponse
    {
        $guardian = $this->ownGuardian($request);

        abort_if($guardian === null, 404);
        $this->authorize('updateOwnProfile', $guardian);

        $update->handle($guardian, [
            'phone' => $request->optional('phone'),
            'occupation' => $request->optional('occupation'),
            'address' => $request->optional('address'),
        ]);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    private function ownGuardian(Request $request): ?Guardian
    {
        /** @var User $user */
        $user = $request->user();

        return Guardian::query()->where('user_id', $user->getKey())->first();
    }
}
