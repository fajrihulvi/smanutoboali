<?php

namespace App\Modules\Access\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Access\Actions\ResetUserPasswordAction;
use App\Modules\Access\Http\Requests\ResetUserPasswordRequest;
use Illuminate\Http\RedirectResponse;

class UserPasswordController extends Controller
{
    public function update(ResetUserPasswordRequest $request, User $user, ResetUserPasswordAction $resetPassword): RedirectResponse
    {
        $resetPassword->handle($user, $request->string('password')->value());

        return back()->with('success', "Password {$user->name} berhasil direset. User perlu masuk ulang.");
    }
}
