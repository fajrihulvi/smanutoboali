<?php

namespace App\Modules\Access\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Access\Actions\CreateUserAction;
use App\Modules\Access\Actions\DeleteUserAction;
use App\Modules\Access\Actions\UpdateUserAction;
use App\Modules\Access\Http\Requests\StoreUserRequest;
use App\Modules\Access\Http\Requests\UpdateUserRequest;
use App\Modules\Access\Http\Resources\UserResource;
use App\Modules\Access\Services\UserDirectoryService;
use App\Modules\Core\Enums\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request, UserDirectoryService $directory): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = [
            'search' => $request->string('search')->trim()->value() ?: null,
            'role' => Role::tryFrom($request->string('role')->value())?->value,
            'status' => in_array($request->query('status'), ['active', 'inactive'], true) ? (string) $request->query('status') : null,
        ];

        return Inertia::render('Access::Operator/Users/Index', [
            'users' => UserResource::collection($directory->paginate($filters)),
            'filters' => array_filter($filters),
            'roles' => $this->roleOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Access::Operator/Users/Create', [
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $createUser): RedirectResponse
    {
        $user = $createUser->handle($request->payload());

        return to_route('operator.users.index')->with('success', "User {$user->name} berhasil dibuat.");
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $user->load('roles');

        return Inertia::render('Access::Operator/Users/Edit', [
            'user' => (new UserResource($user))->resolve(request()),
            'roles' => $this->roleOptions(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $updateUser): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $updateUser->handle($user, $request->payload(), $actor);

        return to_route('operator.users.edit', $user)->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user, DeleteUserAction $deleteUser): RedirectResponse
    {
        $this->authorize('delete', $user);

        /** @var User $actor */
        $actor = $request->user();

        $deleteUser->handle($user, $actor);

        return $this->backToIndex('operator.users.index', to_route('operator.users.index'))
            ->with('success', "User {$user->email} dihapus.");
    }

    /**
     * @return list<array{code: string, label: string, prefix: string}>
     */
    private function roleOptions(): array
    {
        return array_map(fn (Role $role) => $role->toArray(), Role::cases());
    }
}
