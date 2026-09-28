<?php

namespace App\Modules\People\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\People\Services\GuardianChildren;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * POST /orang-tua/anak-aktif {anak, kembali} — ganti anak aktif (StudentSwitcher global),
 * lalu kembali ke halaman asal tanpa `?anak=` agar session yang menentukan.
 */
class ActiveChildController extends Controller
{
    public function __invoke(Request $request, GuardianChildren $children): RedirectResponse
    {
        $data = $request->validate([
            'anak' => ['required', 'string'],
            'kembali' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $child = $children->of($user)->firstWhere('public_id', $data['anak']);
        abort_if($child === null, 403);

        $children->remember($child);

        return redirect($this->safeReturn($data['kembali'] ?? null));
    }

    /**
     * Hanya path internal portal orang tua (mencegah open redirect); `anak` dibuang dari query.
     */
    private function safeReturn(?string $url): string
    {
        $parts = parse_url((string) $url) ?: [];
        $path = (string) ($parts['path'] ?? '');

        if (isset($parts['scheme']) || isset($parts['host']) || ! str_starts_with($path, '/orang-tua/')
            || str_contains($path, '//') || str_contains($path, '\\')) {
            return '/orang-tua/dashboard';
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        unset($query[GuardianChildren::QUERY_KEY]);

        return $path.($query === [] ? '' : '?'.http_build_query($query));
    }
}
