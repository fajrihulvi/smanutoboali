<?php

namespace App\Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Chat\Actions\SendMessageAction;
use App\Modules\Chat\Http\Resources\ConversationResource;
use App\Modules\Chat\Models\Conversation;
use App\Modules\Chat\Services\ChatDirectory;
use App\Modules\Chat\Services\ChatInbox;
use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Services\SettingsService;
use App\Modules\People\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman chat semua portal (`/<portal>/chat[/{conversation}]`). Pesan dimuat lewat API JSON.
 */
class ChatPageController extends Controller
{
    public function __construct(
        private ChatInbox $inbox,
        private ChatDirectory $directory,
        private SettingsService $settings,
    ) {}

    public function index(Request $request, ?Conversation $conversation = null): Response
    {
        /** @var User $user */
        $user = $request->user();
        /** @var string $base */
        $base = $request->route()?->defaults['base'] ?? '/chat';

        if ($conversation !== null) {
            $this->authorize('view', $conversation);
        }

        return Inertia::render('Chat::Index', [
            'base' => $base,
            'conversations' => fn () => $this->conversations($request, $user),
            'active' => fn () => $conversation ? $this->active($request, $user, $conversation) : null,
            'presenceEnabled' => filter_var($this->settings->get(SettingKey::ChatPresenceEnabled), FILTER_VALIDATE_BOOL),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function conversations(Request $request, User $user): array
    {
        $rows = $this->inbox->conversations($user);
        $map = $this->directory->teachersOfStudents($rows->pluck('student')->filter(fn ($s) => $s instanceof Student));

        return $rows->map(fn (Conversation $c) => (new ConversationResource($c))->withTeacherMap($map)->resolve($request))->values()->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function active(Request $request, User $user, Conversation $conversation): ?array
    {
        $row = $this->inbox->find($user, $conversation);

        if ($row === null) {
            return null;
        }

        return (new ConversationResource($row))
            ->withTeacherMap($this->directory->teachersOfStudents($row->student ? [$row->student] : []))
            ->withCanSend(app(SendMessageAction::class)->canSend($row, $user))
            ->resolve($request);
    }
}
