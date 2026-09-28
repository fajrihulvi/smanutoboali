<?php

namespace App\Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Chat\Actions\ArchiveConversationAction;
use App\Modules\Chat\Actions\MarkConversationReadAction;
use App\Modules\Chat\Actions\MuteConversationAction;
use App\Modules\Chat\Actions\SendMessageAction;
use App\Modules\Chat\Actions\StartConversationAction;
use App\Modules\Chat\Http\Requests\StartConversationRequest;
use App\Modules\Chat\Http\Requests\UpdateConversationRequest;
use App\Modules\Chat\Http\Resources\ConversationResource;
use App\Modules\Chat\Models\Conversation;
use App\Modules\Chat\Services\ChatDirectory;
use App\Modules\Chat\Services\ChatInbox;
use App\Modules\People\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API JSON percakapan (dipakai UI chat semua role).
 */
class ConversationController extends Controller
{
    public function __construct(private ChatInbox $inbox, private ChatDirectory $directory) {}

    /**
     * GET /chat/percakapan?arsip=1&q=
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $conversations = $this->inbox->conversations($user, $request->boolean('arsip'), $request->string('q')->value());
        $map = $this->directory->teachersOfStudents($conversations->pluck('student')->filter(fn ($s) => $s instanceof Student));

        return response()->json([
            'data' => $conversations->map(fn (Conversation $c) => (new ConversationResource($c))->withTeacherMap($map)->resolve($request))->values(),
            'unread_total' => $this->inbox->unreadTotal($user),
        ]);
    }

    /**
     * POST /chat/percakapan {user, student?} — memakai ulang percakapan yang sudah ada.
     */
    public function store(StartConversationRequest $request, StartConversationAction $start): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $conversation = $start->handle($user, $request->target(), $request->student());

        return response()->json(['data' => $this->present($request, $conversation, $user)], $conversation->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * GET /chat/percakapan/{conversation}
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        /** @var User $user */
        $user = $request->user();

        return response()->json(['data' => $this->present($request, $conversation, $user)]);
    }

    /**
     * PATCH /chat/percakapan/{conversation} {archived?, muted?}
     */
    public function update(UpdateConversationRequest $request, Conversation $conversation, ArchiveConversationAction $archive, MuteConversationAction $mute): JsonResponse
    {
        $this->authorize('view', $conversation);

        /** @var User $user */
        $user = $request->user();

        if ($request->has('archived')) {
            $archive->handle($conversation, $user, $request->boolean('archived'));
        }

        if ($request->has('muted')) {
            $mute->handle($conversation, $user, $request->boolean('muted'));
        }

        return response()->json(['data' => $this->present($request, $conversation, $user)]);
    }

    /**
     * POST /chat/percakapan/{conversation}/baca
     */
    public function read(Request $request, Conversation $conversation, MarkConversationReadAction $markRead): JsonResponse
    {
        $this->authorize('view', $conversation);

        /** @var User $user */
        $user = $request->user();
        $markRead->handle($conversation, $user);

        return response()->json(['unread_total' => $this->inbox->unreadTotal($user)]);
    }

    /**
     * GET /chat/belum-dibaca — total untuk badge menu.
     */
    public function unread(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['unread_total' => $this->inbox->unreadTotal($user)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Request $request, Conversation $conversation, User $user): array
    {
        $row = $this->inbox->find($user, $conversation);
        abort_if($row === null, 403);

        return (new ConversationResource($row))
            ->withTeacherMap($this->directory->teachersOfStudents($row->student ? [$row->student] : []))
            ->withCanSend(app(SendMessageAction::class)->canSend($row, $user))
            ->resolve($request);
    }
}
