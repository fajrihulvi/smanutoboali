<?php

namespace App\Modules\Chat;

use App\Modules\Chat\Models\Conversation;
use App\Modules\Chat\Models\Message;
use App\Modules\Chat\Policies\ConversationPolicy;
use App\Modules\Chat\Policies\MessagePolicy;
use App\Providers\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ChatServiceProvider extends ModuleServiceProvider
{
    /** Batas kirim pesan per user per menit. */
    public const SEND_PER_MINUTE = 30;

    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Conversation::class => ConversationPolicy::class,
        Message::class => MessagePolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];

    public function boot(): void
    {
        parent::boot();

        RateLimiter::for('chat-send', fn (Request $request) => Limit::perMinute(self::SEND_PER_MINUTE)
            ->by('chat-send:'.$request->user()?->getAuthIdentifier())
            ->response(fn () => response()->json(['message' => 'Terlalu banyak pesan. Tunggu sebentar lalu coba lagi.'], 429)));
    }
}
