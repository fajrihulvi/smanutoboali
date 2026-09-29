<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Modules\Auth\Http\Middleware\EnsureActiveRole;
use App\Modules\Auth\Services\ActiveRoleService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['sidebar_state']);

        // Header keamanan (CSP bernonce, anti-framing, HSTS produksi) untuk semua respons.
        $middleware->append(SecurityHeaders::class);

        // Callback server-ke-server payment gateway (diverifikasi dengan signature, bukan CSRF).
        $middleware->validateCsrfTokens(except: ['webhooks/*']);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'active.role' => EnsureActiveRole::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));

        // User yang sudah login dan membuka halaman tamu (login, lupa password)
        // diarahkan ke dashboard role aktifnya.
        $middleware->redirectUsersTo(function (Request $request) {
            $service = app(ActiveRoleService::class);
            $user = $request->user();
            $role = $user ? $service->resolve($request->session(), $user) : null;

            return $role ? $service->homeUrl($role) : '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Token CSRF kedaluwarsa pada kunjungan Inertia: kembali ke halaman
        // sebelumnya dengan toast, bukan modal halaman error.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if ($response->getStatusCode() === 419 && $request->header('X-Inertia')) {
                return back()->with('error', 'Sesi Anda telah berakhir. Silakan coba lagi.');
            }

            return $response;
        });
    })->create();
