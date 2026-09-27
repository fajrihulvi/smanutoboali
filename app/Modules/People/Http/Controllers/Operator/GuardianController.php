<?php

namespace App\Modules\People\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\People\Actions\CreateGuardianAction;
use App\Modules\People\Actions\DeletePersonAction;
use App\Modules\People\Actions\LinkPersonAccountAction;
use App\Modules\People\Actions\UpdateGuardianAction;
use App\Modules\People\Http\Requests\GuardianRequest;
use App\Modules\People\Http\Requests\LinkAccountRequest;
use App\Modules\People\Http\Resources\GuardianResource;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Services\PeopleDirectoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuardianController extends Controller
{
    public function index(Request $request, PeopleDirectoryService $directory): Response
    {
        $this->authorize('viewAny', Guardian::class);
        $filters = $directory->guardianFilters($request);

        return Inertia::render('People::Operator/Guardians/Index', [
            'guardians' => GuardianResource::collection($directory->guardians($filters)),
            'filters' => array_filter($filters),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Guardian::class);

        return Inertia::render('People::Operator/Guardians/Form', ['guardian' => null]);
    }

    public function store(GuardianRequest $request, CreateGuardianAction $create): RedirectResponse
    {
        $result = $create->handle($request->payload(), $request->boolean('create_account'));
        $guardian = $result['guardian'];

        return to_route('operator.guardians.edit', $guardian)
            ->with('success', "Orang tua {$guardian->name} berhasil ditambahkan.")
            ->with('credentials', $this->credentials($guardian, $result['temporary_password']));
    }

    public function edit(Guardian $guardian): Response
    {
        $this->authorize('update', $guardian);

        $guardian->load('user:id,email,is_active');

        return Inertia::render('People::Operator/Guardians/Form', [
            'guardian' => (new GuardianResource($guardian))->resolve(request()),
        ]);
    }

    public function update(GuardianRequest $request, Guardian $guardian, UpdateGuardianAction $update): RedirectResponse
    {
        $update->handle($guardian, $request->payload());

        return to_route('operator.guardians.edit', $guardian)->with('success', 'Data orang tua berhasil diperbarui.');
    }

    public function destroy(Guardian $guardian, DeletePersonAction $delete): RedirectResponse
    {
        $this->authorize('delete', $guardian);

        $delete->handle($guardian);

        return to_route('operator.guardians.index')->with('success', "Data orang tua {$guardian->name} dihapus.");
    }

    public function linkAccount(LinkAccountRequest $request, Guardian $guardian, LinkPersonAccountAction $link): RedirectResponse
    {
        $password = $link->handle($guardian, $request->string('account_email')->value());

        return back()
            ->with('success', 'Akun login berhasil ditautkan.')
            ->with('credentials', $this->credentials($guardian->refresh(), $password));
    }

    /**
     * @return array{email: string, password: string}|null
     */
    private function credentials(Guardian $guardian, ?string $password): ?array
    {
        $guardian->loadMissing('user');

        return $password && $guardian->user ? ['email' => $guardian->user->email, 'password' => $password] : null;
    }
}
