<?php

namespace App\Modules\Access\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Access\Actions\SetUserActiveStatusAction;
use App\Modules\Access\Http\Requests\UpdateUserStatusRequest;
use Illuminate\Http\RedirectResponse;

class UserStatusController extends Controller
{
    public function update(UpdateUserStatusRequest $request, User $user, SetUserActiveStatusAction $setStatus): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $active = $request->boolean('is_active');

        $setStatus->handle($user, $active, $actor);

        return back()->with('success', $active
            ? "Akun {$user->name} diaktifkan."
            : "Akun {$user->name} dinonaktifkan.");
    }
}
