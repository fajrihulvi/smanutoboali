<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Notifikasi milik user yang login. Halaman lengkap & realtime menyusul di S19.
 */
class NotificationController extends Controller
{
    public const RECENT_LIMIT = 10;

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
