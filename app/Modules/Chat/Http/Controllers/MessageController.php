<?php

namespace App\Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Chat\Actions\DeleteMessageAction;
use App\Modules\Chat\Actions\EditMessageAction;
use App\Modules\Chat\Actions\SendMessageAction;
use App\Modules\Chat\Http\Requests\SendMessageRequest;
use App\Modules\Chat\Http\Requests\UpdateMessageRequest;
use App\Modules\Chat\Http\Resources\MessageResource;
use App\Modules\Chat\Models\Conversation;
use App\Modules\Chat\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * API JSON pesan dalam percakapan.
 */
class MessageController extends Controller
{
    /**
     * GET /chat/percakapan/{conversation}/pesan?cursor= — halaman terbaru dulu; `next_cursor`
     * memuat pesan yang lebih lama. `data` diurutkan kronologis (lama → baru).
     */
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        /** @var User $user */
        $user = $request->user();
        $conversation->loadMissing('participants');
        $counterpart = $conversation->otherParticipant($user);

        $page = Message::withTrashed()
            ->where('conversation_id', $conversation->id)
            ->with(['sender', 'attachment'])
            ->orderByDesc('id')
            ->cursorPaginate(Message::PER_PAGE);

        return response()->json([
            'data' => collect($page->items())
                ->reverse()
                ->map(fn (Message $m) => (new MessageResource($m))->viewedBy($user, $counterpart)->resolve($request))
                ->values(),
            'next_cursor' => $page->nextCursor()?->encode(),
        ]);
    }

    /**
     * POST /chat/percakapan/{conversation}/pesan {body?, file?} — dibatasi 30/menit.
     */
    public function store(SendMessageRequest $request, Conversation $conversation, SendMessageAction $send): JsonResponse
    {
        $this->authorize('view', $conversation);

        /** @var User $user */
        $user = $request->user();
        $file = $request->file('file');
        $message = $send->handle($conversation, $user, $request->string('body')->value(), $file instanceof UploadedFile ? $file : null);

        return response()->json(['data' => $this->present($request, $message, $user)], 201);
    }

    /**
     * PATCH /chat/pesan/{message} {body}
     */
    public function update(UpdateMessageRequest $request, Message $message, EditMessageAction $edit): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $edit->handle($message, $request->string('body')->value());

        return response()->json(['data' => $this->present($request, $message, $user)]);
    }

    /**
     * DELETE /chat/pesan/{message}
     */
    public function destroy(Request $request, Message $message, DeleteMessageAction $delete): JsonResponse
    {
        $this->authorize('delete', $message);

        /** @var User $user */
        $user = $request->user();
        $delete->handle($message);

        return response()->json(['data' => $this->present($request, $message, $user)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Request $request, Message $message, User $user): array
    {
        $message->loadMissing(['conversation.participants', 'sender', 'attachment']);

        return (new MessageResource($message))
            ->viewedBy($user, $message->conversation->otherParticipant($user))
            ->resolve($request);
    }
}
