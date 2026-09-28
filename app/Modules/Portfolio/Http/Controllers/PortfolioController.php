<?php

namespace App\Modules\Portfolio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Core\Services\SettingsService;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Student;
use App\Modules\Portfolio\Actions\DeletePortfolioAction;
use App\Modules\Portfolio\Actions\SavePortfolioAction;
use App\Modules\Portfolio\Http\Requests\PortfolioRequest;
use App\Modules\Portfolio\Http\Resources\PortfolioResource;
use App\Modules\Portfolio\Models\Portfolio;
use App\Modules\Portfolio\Models\PortfolioCategory;
use App\Support\ActivePeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kelola portofolio (guru & operator). `$base` = prefix portal lewat route defaults.
 */
class PortfolioController extends Controller
{
    public const PER_PAGE = 12;

    public function index(Request $request, string $base): Response
    {
        $this->authorize('manageAny', Portfolio::class);
        $user = $this->user($request);
        $search = $request->string('search')->trim()->value();
        $category = $request->string('kategori')->value();
        $status = $request->string('status')->value();

        $portfolios = Portfolio::query()
            // Buatan sendiri atau milik siswa yang terlihat (operator: semua siswa → semua portofolio).
            ->where(fn (Builder $w) => $w
                ->where('created_by', $user->id)
                ->orWhereHas('students', fn (Builder $s) => $s->whereIn('students.id', Student::query()->visibleTo($user)->select('students.id'))))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('title', 'like', "%{$search}%")
                ->orWhereHas('students', fn (Builder $s) => $s->where('name', 'like', "%{$search}%"))))
            ->when($category !== '', fn (Builder $q) => $q->whereHas('category', fn (Builder $c) => $c->where('code', $category)))
            ->when(in_array($status, ['DRAFT', 'PUBLISHED'], true), fn (Builder $q) => $q->where('visibility', $status))
            ->with(['category', 'students:id,public_id,name', 'files', 'creator:id,name'])
            ->latest('date')->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Portfolio::Manage/Index', [
            'base' => $base,
            'portfolios' => PortfolioResource::collection($portfolios),
            'categories' => $this->categories(),
            'filters' => array_filter(['search' => $search, 'kategori' => $category, 'status' => $status]),
        ]);
    }

    public function create(Request $request, string $base, SettingsService $settings): Response
    {
        $this->authorize('manageAny', Portfolio::class);

        return Inertia::render('Portfolio::Manage/Form', [
            'base' => $base,
            'portfolio' => null,
            'categories' => $this->categories(),
            'maxUploadKb' => $settings->uploadMaxKb(),
            'maxFiles' => Portfolio::MAX_FILES,
        ]);
    }

    public function store(PortfolioRequest $request, string $base, SavePortfolioAction $save): RedirectResponse
    {
        $portfolio = $save->handle($request->payload(), $this->user($request), $request->uploads());

        return redirect("{$base}/{$portfolio->public_id}/ubah")->with('success', 'Portofolio disimpan.');
    }

    public function edit(Request $request, Portfolio $portfolio, string $base, SettingsService $settings): Response
    {
        $this->authorize('update', $portfolio);
        $portfolio->load(['category', 'students:id,public_id,name', 'files', 'creator:id,name']);

        return Inertia::render('Portfolio::Manage/Form', [
            'base' => $base,
            'portfolio' => (new PortfolioResource($portfolio))->resolve($request),
            'categories' => $this->categories(),
            'maxUploadKb' => $settings->uploadMaxKb(),
            'maxFiles' => Portfolio::MAX_FILES,
        ]);
    }

    public function update(PortfolioRequest $request, Portfolio $portfolio, SavePortfolioAction $save): RedirectResponse
    {
        $save->handle($request->payload(), $this->user($request), $request->uploads(), $request->removedFiles(), $portfolio);

        return back()->with('success', 'Portofolio diperbarui.');
    }

    public function destroy(Portfolio $portfolio, string $base, DeletePortfolioAction $delete): RedirectResponse
    {
        $this->authorize('delete', $portfolio);
        $delete->handle($portfolio);

        return redirect($base)->with('success', "Portofolio \"{$portfolio->title}\" dihapus.");
    }

    /**
     * Cari siswa yang boleh dipilih (JSON): siswa aktif yang terlihat oleh pengguna.
     */
    public function students(Request $request, ActivePeriod $period): JsonResponse
    {
        $this->authorize('manageAny', Portfolio::class);
        $term = $request->string('q')->trim()->value();

        $students = Student::query()
            ->visibleTo($this->user($request))
            ->where('students.status', StudentStatus::Active)
            ->search($term !== '' ? $term : null)
            ->with(['enrollments' => fn ($e) => $e->where('academic_year_id', $period->academicYearId() ?? 0)
                ->where('status', EnrollmentStatus::Active)->with('classroom:id,name')])
            ->orderBy('name')
            ->limit(30)
            ->get(['students.id', 'public_id', 'nis', 'name']);

        return response()->json(['data' => $students->map(fn (Student $s) => [
            'public_id' => $s->public_id,
            'name' => $s->name,
            'nis' => $s->nis,
            'classroom' => $s->enrollments->first()?->classroom->name,
        ])->values()]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function categories(): array
    {
        return PortfolioCategory::query()->orderBy('position')->get()
            ->map(fn (PortfolioCategory $c) => ['value' => $c->code, 'label' => $c->name])->values()->all();
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
