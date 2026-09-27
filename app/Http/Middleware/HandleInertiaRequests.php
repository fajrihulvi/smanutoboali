<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Modules\Auth\Http\Resources\AuthUserResource;
use App\Modules\Auth\Services\ActiveRoleService;
use App\Modules\Core\Enums\Role;
use App\Modules\School\Services\SchoolProfile;
use App\Support\ActivePeriod;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => fn () => $this->authProps($request, $user instanceof User ? $user : null),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'notifications' => fn () => [
                'unread' => $user instanceof User ? $user->unreadNotifications()->count() : 0,
            ],
            'school' => fn () => app(SchoolProfile::class)->toArray(),
            'activePeriod' => fn () => $user ? app(ActivePeriod::class)->toArray() : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array{user: array<string, mixed>|null, roles: list<array{code: string, label: string, prefix: string}>, activeRole: string|null, permissions: list<string>}
     */
    private function authProps(Request $request, ?User $user): array
    {
        if (! $user) {
            return ['user' => null, 'roles' => [], 'activeRole' => null, 'permissions' => []];
        }

        $service = app(ActiveRoleService::class);
        $active = $service->resolve($request->session(), $user);

        return [
            'user' => (new AuthUserResource($user))->resolve($request),
            'roles' => array_map(fn (Role $role) => $role->toArray(), $user->coreRoles()),
            'activeRole' => $active?->value,
            'permissions' => $service->permissionsFor($user, $active),
        ];
    }
}
