<?php

namespace App\Modules\Cms\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Actions\UpdateTeacherWebsiteAction;
use App\Modules\Cms\Http\Resources\TeacherWebsiteResource;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\People\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pilih guru yang tampil di website. Nama & foto tetap dari data guru (modul People).
 */
class TeacherController extends Controller
{
    public const PER_PAGE = 20;

    public function index(Request $request): Response
    {
        $this->authorize('manage', WebsitePage::class);

        $search = $request->string('q')->value();
        $shown = $request->string('tampil')->value();

        $teachers = Teacher::query()
            ->where('is_active', true)
            ->with('photo')
            ->when($search !== '', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
            ->when($shown === 'ya', fn (Builder $q) => $q->where('show_on_website', true))
            ->when($shown === 'tidak', fn (Builder $q) => $q->where('show_on_website', false))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Cms::Operator/Teachers', [
            'teachers' => TeacherWebsiteResource::collection($teachers),
            'filters' => ['q' => $search, 'tampil' => $shown],
            'shownCount' => Teacher::query()->where('is_active', true)->where('show_on_website', true)->count(),
        ]);
    }

    public function update(Request $request, Teacher $teacher, UpdateTeacherWebsiteAction $update): RedirectResponse
    {
        $this->authorize('manage', WebsitePage::class);

        $data = $request->validate([
            'show_on_website' => ['required', 'boolean'],
            'website_title' => ['nullable', 'string', 'max:80'],
        ], [], ['website_title' => 'jabatan di website']);

        $title = trim((string) ($data['website_title'] ?? ''));
        $update->handle($teacher, (bool) $data['show_on_website'], $title === '' ? null : $title);

        return back()->with('success', 'Tampilan guru di website disimpan.');
    }
}
