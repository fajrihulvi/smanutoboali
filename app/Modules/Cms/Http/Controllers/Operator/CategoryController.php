<?php

namespace App\Modules\Cms\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Actions\SaveCategoryAction;
use App\Modules\Cms\Models\WebsiteCategory;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kategori berita/agenda. Menghapus kategori tidak menghapus beritanya (menjadi tanpa kategori).
 */
class CategoryController extends Controller
{
    public function index(): Response
    {
        $this->authorize('manage', WebsiteCategory::class);

        $categories = WebsiteCategory::query()->withCount('posts')->orderBy('name')->get()
            ->map(fn (WebsiteCategory $c) => [
                'public_id' => $c->public_id,
                'name' => $c->name,
                'slug' => $c->slug,
                'posts_count' => (int) $c->getAttribute('posts_count'),
            ]);

        return Inertia::render('Cms::Operator/Categories/Index', ['categories' => $categories]);
    }

    public function store(Request $request, SaveCategoryAction $save): RedirectResponse
    {
        $this->authorize('manage', WebsiteCategory::class);
        $save->handle($this->validated($request));

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(Request $request, WebsiteCategory $category, SaveCategoryAction $save): RedirectResponse
    {
        $this->authorize('update', $category);
        $save->handle($this->validated($request, $category), $category);

        return back()->with('success', 'Kategori disimpan.');
    }

    public function destroy(WebsiteCategory $category, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $category);
        $category->delete();
        $audit->record('cms', 'deleted', "Menghapus kategori \"{$category->name}\"", $category);

        return back()->with('success', 'Kategori dihapus.');
    }

    private function validated(Request $request, ?WebsiteCategory $category = null): string
    {
        $data = $request->validate(
            ['name' => ['required', 'string', 'max:60', Rule::unique('website_categories', 'name')->ignore($category?->id)]],
            [],
            ['name' => 'nama kategori'],
        );

        return trim($data['name']);
    }
}
