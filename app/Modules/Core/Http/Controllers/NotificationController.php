<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Notifikasi milik user yang login. Halaman lengkap & realtime menyusul di S19.
 */
class NotificationController extends Controller
{
    public const RECENT_LIMIT = 10;

    public const PER_PAGE = 20;

    /**
     * GET /notifikasi — halaman notification center (?status=belum untuk yang belum dibaca).
     */
    public function index(Request $request): Response
    {
        $user = $this->user($request);
        $unreadOnly = $request->string('status')->value() === 'belum';

        $notifications = ($unreadOnly ? $user->unreadNotifications() : $user->notifications())
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Core::Notifications/Index', [
            'notifications' => NotificationResource::collection($notifications),
            'filters' => array_filter(['status' => $unreadOnly ? 'belum' : null]),
            'unread' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * GET /notifikasi/terbaru — isi dropdown lonceng (JSON).
     */
    public function recent(Request $request): JsonResponse
    {
        $user = $this->user($request);

        return response()->json([
            'data' => NotificationResource::collection(
                $user->notifications()->latest()->limit(self::RECENT_LIMIT)->get(),
            ),
            'unread' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * POST /notifikasi/{id}/dibaca — hanya notifikasi milik sendiri (404 selain itu).
     */
    public function markAsRead(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $notification = $this->user($request)->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        return $request->expectsJson()
            ? response()->json(['unread' => $this->user($request)->unreadNotifications()->count()])
            : back();
    }

    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        $this->user($request)->unreadNotifications()->update(['read_at' => now()]);

        return $request->expectsJson() ? response()->json(['unread' => 0]) : back();
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
