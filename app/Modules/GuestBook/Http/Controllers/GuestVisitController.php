<?php

namespace App\Modules\GuestBook\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\GuestBook\Actions\DeleteGuestVisitAction;
use App\Modules\GuestBook\Actions\SaveGuestVisitAction;
use App\Modules\GuestBook\Enums\GuestCategory;
use App\Modules\GuestBook\Enums\GuestHost;
use App\Modules\GuestBook\Enums\GuestVisitSource;
use App\Modules\GuestBook\Http\Requests\GuestVisitRequest;
use App\Modules\GuestBook\Http\Resources\GuestVisitResource;
use App\Modules\GuestBook\Models\GuestVisit;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Buku tamu di portal. Daftar dipakai operator (kelola) & kepala sekolah (lihat) — route default `base`.
 */
class GuestVisitController extends Controller
{
    public const PER_PAGE = 20;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', GuestVisit::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', Rule::enum(GuestCategory::class)],
            'host' => ['nullable', Rule::enum(GuestHost::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));

        $visits = GuestVisit::query()
            ->with('photo')
            ->when($search !== '', fn ($q) => $q->where(fn ($s) => $s
                ->where('full_name', 'like', "%{$search}%")
                ->orWhere('institution', 'like', "%{$search}%")
                ->orWhere('purpose', 'like', "%{$search}%")
                ->orWhere('whatsapp', 'like', "%{$search}%")))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when($filters['host'] ?? null, fn ($q, $host) => $q->where('host', $host))
            ->visitedBetween($filters['from'] ?? null, $filters['to'] ?? null)
            ->latest('visited_at')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        /** @var User $user */
        $user = $request->user();
        $today = CarbonImmutable::today();

        return Inertia::render('GuestBook::Index', [
            'base' => $this->base($request),
            'visits' => GuestVisitResource::collection($visits),
            'filters' => array_filter([...$filters, 'search' => $search]),
            'categories' => GuestCategory::options(),
            'hosts' => GuestHost::options(),
            'stats' => [
                'today' => GuestVisit::query()->visitedBetween($today->toDateString(), $today->toDateString())->count(),
                'month' => GuestVisit::query()->visitedBetween($today->startOfMonth()->toDateString(), $today->toDateString())->count(),
            ],
            'can' => [
                'create' => $user->can('create', GuestVisit::class),
                // Rekap memakai pusat laporan (butuh izin ekspor laporan).
                'recap' => $user->can('create', GuestVisit::class) && $user->can(Permission::ReportExport->value),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', GuestVisit::class);

        return $this->form(null);
    }

    public function store(GuestVisitRequest $request, SaveGuestVisitAction $save): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $visit = $save->handle($request->payload(), $request->file('photo'), actor: $user, source: GuestVisitSource::Operator);

        return redirect('/operator/buku-tamu')->with('success', "Tamu {$visit->full_name} dicatat.");
    }

    public function edit(GuestVisit $visit): Response
    {
        $this->authorize('update', $visit);

        return $this->form($visit->load('photo'));
    }

    public function update(GuestVisitRequest $request, GuestVisit $visit, SaveGuestVisitAction $save): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $save->handle($request->payload(), $request->file('photo'), $request->boolean('remove_photo'), $visit, $user);

        return redirect('/operator/buku-tamu')->with('success', 'Data tamu diperbarui.');
    }

    public function destroy(GuestVisit $visit, DeleteGuestVisitAction $delete): RedirectResponse
    {
        $this->authorize('delete', $visit);

        $delete->handle($visit);

        return back()->with('success', "Data tamu {$visit->full_name} dihapus.");
    }

    private function form(?GuestVisit $visit): Response
    {
        return Inertia::render('GuestBook::Operator/Form', [
            'visit' => $visit ? GuestVisitResource::make($visit)->resolve() : null,
            'categories' => GuestCategory::options(),
            'hosts' => GuestHost::options(),
        ]);
    }

    private function base(Request $request): string
    {
        /** @var string $base */
        $base = $request->route()?->defaults['base'] ?? '/operator/buku-tamu';

        return $base;
    }
}
