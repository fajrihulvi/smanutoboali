<?php

namespace App\Modules\GuestBook\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\GuestBook\Actions\SaveGuestVisitAction;
use App\Modules\GuestBook\Enums\GuestCategory;
use App\Modules\GuestBook\Enums\GuestHost;
use App\Modules\GuestBook\Enums\GuestVisitSource;
use App\Modules\GuestBook\Http\Requests\PublicGuestVisitRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Form buku tamu di website (/buku-tamu). Tidak di-cache karena berisi token CSRF.
 */
class GuestBookController extends Controller
{
    public function create(): View
    {
        return view('public.guest-book', [
            'categories' => GuestCategory::options(),
            'hosts' => GuestHost::options(),
        ]);
    }

    public function store(PublicGuestVisitRequest $request, SaveGuestVisitAction $save): RedirectResponse
    {
        $visit = $save->handle($request->payload(), $request->file('photo'), source: GuestVisitSource::Website);

        return redirect()->route('public.guest-book')->with('guest_book_success', $visit->full_name);
    }
}
