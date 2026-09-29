<?php

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

/**
 * Test arsitektur (S32): route baru yang lupa diberi middleware langsung ketahuan.
 */

/**
 * Route yang memang boleh diakses tanpa login (URI tanpa slash awal).
 *
 * @var list<string>
 */
const PUBLIC_ROUTES = [
    // Autentikasi & pemulihan password
    'login', 'login/{portal}', 'forgot-password', 'reset-password/{token}', 'reset-password',
    // Website publik (S29)
    '/', '{slug}', 'berita', 'berita/{slug}', 'agenda', 'agenda/{slug}', 'galeri', 'galeri/{slug}',
    'guru', 'ppdb', 'sitemap.xml', 'robots.txt',
    // PPDB tamu (S26–S27)
    'ppdb/daftar', 'ppdb/masuk', 'ppdb/hasil', 'ppdb/verifikasi/{user}/{hash}',
    // Mesin ke mesin & infrastruktur
    'webhooks/midtrans', 'health', 'up', 'broadcasting/auth',
    // File publik (hanya visibility public, dicek di controller)
    'media/{file}',
];

/**
 * @return list<RoutingRoute>
 */
function appRoutes(): array
{
    return array_values(array_filter(
        Route::getRoutes()->getRoutes(),
        fn (RoutingRoute $r) => ! str_starts_with($r->uri(), 'style-guide') && ! str_starts_with($r->uri(), '_'),
    ));
}

it('setiap route tanpa login tercantum di daftar route publik', function () {
    $unprotected = collect(appRoutes())
        ->reject(fn (RoutingRoute $r) => in_array('auth', $r->gatherMiddleware(), true))
        ->map(fn (RoutingRoute $r) => $r->uri())
        ->unique()
        ->values();

    expect($unprotected->diff(PUBLIC_ROUTES)->values()->all())->toBe([]);
});

it('setiap route portal wajib login dan role aktif yang sesuai', function (string $prefix, string $role) {
    $routes = collect(appRoutes())->filter(fn (RoutingRoute $r) => str_starts_with($r->uri(), $prefix.'/'));

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        $middleware = $route->gatherMiddleware();

        expect(in_array('auth', $middleware, true))->toBeTrue("{$route->uri()} tanpa auth")
            ->and(in_array("active.role:{$role}", $middleware, true))->toBeTrue("{$route->uri()} tanpa active.role:{$role}");
    }
})->with([
    ['operator', 'operator'],
    ['guru', 'teacher'],
    ['kepala-sekolah', 'principal'],
    ['orang-tua', 'guardian'],
    ['ppdb/akun', 'applicant'],
]);

it('route tulis publik dibatasi laju (throttle) atau signature', function () {
    $writes = collect(appRoutes())
        ->filter(fn (RoutingRoute $r) => array_intersect($r->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) !== [])
        ->reject(fn (RoutingRoute $r) => in_array('auth', $r->gatherMiddleware(), true))
        ->reject(fn (RoutingRoute $r) => in_array($r->uri(), ['login/{portal}', 'ppdb/masuk', 'broadcasting/auth'], true)); // throttle di AttemptPortalLoginAction / controller framework

    foreach ($writes as $route) {
        $middleware = collect($route->gatherMiddleware())->filter(fn ($m) => is_string($m));

        expect($middleware->contains(fn (string $m) => str_starts_with($m, 'throttle') || $m === 'signed'))
            ->toBeTrue("{$route->uri()} tanpa throttle");
    }
});
