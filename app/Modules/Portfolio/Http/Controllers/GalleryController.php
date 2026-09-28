<?php

namespace App\Modules\Portfolio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\Classroom;
use App\Modules\People\Services\GuardianChildren;
use App\Modules\Portfolio\Enums\PortfolioVisibility;
use App\Modules\Portfolio\Http\Resources\PortfolioResource;
use App\Modules\Portfolio\Models\Portfolio;
use App\Modules\Portfolio\Models\PortfolioCategory;
use App\Support\ActivePeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Galeri portofolio TERBIT: orang tua (anak terpilih) dan kepala sekolah (semua, filter rombel).
 */
class GalleryController extends Controller
{
    public const PER_PAGE = 12;

    public function guardian(Request $request, GuardianChildren $guardianChildren): Response
    {
        /** @var User $user */
        $user = $request->user();
        $children = $guardianChildren->of($user);
        $child = $guardianChildren->selected($children, $request->string(GuardianChildren::QUERY_KEY)->value());
        $category = $request->string('kategori')->value();

        $portfolios = $child
            ? $this->published($category)
                ->whereHas('students', fn (Builder $s) => $s->whereKey($child->id))
                ->paginate(self::PER_PAGE)
                ->withQueryString()
            : null;

        return Inertia::render('Portfolio::Guardian/Index', [
            ...$guardianChildren->switcherProps($children, $child),
            'child' => $child ? ['public_id' => $child->public_id, 'name' => $child->name] : null,
            'portfolios' => $portfolios
                ? PortfolioResource::collection($portfolios->through(fn (Portfolio $p) => (new PortfolioResource($p))->onlyStudents($children->modelKeys())))
                : null,
            'categories' => $this->categories(),
            'category' => $category !== '' ? $category : null,
        ]);
    }

    public function principal(Request $request, ActivePeriod $period): Response
    {
        $category = $request->string('kategori')->value();
        $classrooms = Classroom::query()->where('academic_year_id', $period->academicYearId() ?? 0)->orderBy('grade')->orderBy('name')->get(['id', 'public_id', 'name']);
        $classroom = $classrooms->firstWhere('public_id', $request->string('rombel')->value());

        $portfolios = $this->published($category)
            ->when($classroom, fn (Builder $q) => $q->whereHas('students.enrollments', fn (Builder $e) => $e
                ->where('classroom_id', $classroom?->id)->where('status', EnrollmentStatus::Active)))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Portfolio::Principal/Index', [
            'portfolios' => PortfolioResource::collection($portfolios),
            'categories' => $this->categories(),
            'classrooms' => $classrooms->map(fn (Classroom $c) => ['value' => $c->public_id, 'label' => $c->name])->values(),
            'filters' => array_filter(['kategori' => $category, 'rombel' => $classroom?->public_id]),
        ]);
    }

    /**
     * @return Builder<Portfolio>
     */
    private function published(string $category): Builder
    {
        return Portfolio::query()
            ->where('visibility', PortfolioVisibility::Published)
            ->when($category !== '', fn (Builder $q) => $q->whereHas('category', fn (Builder $c) => $c->where('code', $category)))
            ->with(['category', 'students:id,public_id,name', 'files', 'creator:id,name'])
            ->latest('date')->latest('id');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function categories(): array
    {
        return PortfolioCategory::query()->orderBy('position')->get()
            ->map(fn (PortfolioCategory $c) => ['value' => $c->code, 'label' => $c->name])->values()->all();
    }
}
