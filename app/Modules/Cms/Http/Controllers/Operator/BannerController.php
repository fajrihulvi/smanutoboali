<?php

namespace App\Modules\Cms\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Cms\Actions\DeleteContentAction;
use App\Modules\Cms\Actions\SaveBannerAction;
use App\Modules\Cms\Http\Requests\BannerRequest;
use App\Modules\Cms\Http\Resources\BannerResource;
use App\Modules\Cms\Models\WebsiteBanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Banner beranda. Jumlahnya sedikit, jadi tanpa paginasi.
 */
class BannerController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('manage', WebsiteBanner::class);

        $banners = WebsiteBanner::query()->with('image')->orderBy('position')->orderBy('id')->get();

        return Inertia::render('Cms::Operator/Banners/Index', [
            // resolve(): array biasa (tanpa pembungkus "data") — halaman memakai banners.map().
            'banners' => BannerResource::collection($banners)->resolve($request),
        ]);
    }

    public function store(BannerRequest $request, SaveBannerAction $save): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $save->handle($request->payload(), $user);

        return back()->with('success', 'Banner ditambahkan.');
    }

    public function update(BannerRequest $request, WebsiteBanner $banner, SaveBannerAction $save): RedirectResponse
    {
        $this->authorize('update', $banner);

        /** @var User $user */
        $user = $request->user();
        $save->handle($request->payload(), $user, $banner);

        return back()->with('success', 'Banner disimpan.');
    }

    public function destroy(WebsiteBanner $banner, DeleteContentAction $delete): RedirectResponse
    {
        $this->authorize('delete', $banner);
        $delete->handle($banner);

        return back()->with('success', 'Banner dihapus.');
    }
}
