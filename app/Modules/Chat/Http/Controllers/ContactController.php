<?php

namespace App\Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Services\ActiveRoleService;
use App\Modules\Chat\Services\ChatDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /chat/kontak?q= — kontak yang boleh dihubungi, dikelompokkan sesuai role aktif
 * (per anak untuk orang tua, per rombel untuk guru).
 */
class ContactController extends Controller
{
    public function __invoke(Request $request, ChatDirectory $directory, ActiveRoleService $roles): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $role = $roles->resolve($request->session(), $user);

        return response()->json([
            'groups' => $role ? $directory->contacts($user, $role, $request->string('q')->value()) : [],
        ]);
    }
}
