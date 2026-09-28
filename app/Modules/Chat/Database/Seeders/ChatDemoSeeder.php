<?php

namespace App\Modules\Chat\Database\Seeders;

use App\Models\User;
use App\Modules\Chat\Actions\StartConversationAction;
use App\Modules\Chat\Enums\MessageType;
use App\Modules\Chat\Models\Conversation;
use App\Modules\Chat\Models\ConversationParticipant;
use App\Modules\Chat\Models\Message;
use App\Modules\Chat\Services\ChatEligibility;
use App\Modules\People\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Percakapan demo: orang tua ↔ wali kelas (konteks anak) dan kepala sekolah ↔ guru.
 * Pesan terakhir dari orang tua dibiarkan belum dibaca guru agar badge unread terlihat.
 */
class ChatDemoSeeder extends Seeder
{
    public function run(StartConversationAction $start, ChatEligibility $eligibility): void
    {
        if (app()->isProduction() || Conversation::query()->exists()) {
            return;
        }

        $guru = User::query()->where('email', 'guru@skoolia.test')->first();
        $ortu = User::query()->where('email', 'ortu@skoolia.test')->first();
        $kepsek = User::query()->where('email', 'kepsek@skoolia.test')->first();
        $aisyah = Student::query()->where('name', 'Aisyah Putri Hidayat')->first();

        if ($guru && $ortu && $aisyah && $eligibility->canChat($ortu, $guru, $aisyah)) {
            $this->addMessages($start->handle($ortu, $guru, $aisyah), [
                [$ortu, 3, '08:10', 'Selamat pagi Bu, Aisyah hari ini izin terlambat karena ke dokter gigi.'],
                [$guru, 3, '08:25', 'Baik Pak, terima kasih infonya. Semoga lekas sembuh ya.'],
                [$ortu, 1, '19:02', 'Bu, tugas proyek IPA dikumpulkan kapan ya?'],
                [$guru, 1, '19:30', 'Hari Jumat Pak, dalam bentuk maket.'],
                [$ortu, 0, '06:45', 'Siap Bu, terima kasih.'],
                [$ortu, 0, '06:46', 'Oh ya, bahan maketnya boleh dari kardus bekas?'],
            ], unreadFor: $guru, unreadCount: 2);
        }

        if ($guru && $kepsek && $eligibility->canChat($kepsek, $guru)) {
            $this->addMessages($start->handle($kepsek, $guru), [
                [$kepsek, 2, '13:00', 'Bu, mohon nilai tengah semester VII-A diselesaikan minggu ini.'],
                [$guru, 2, '13:20', 'Baik Pak, sedang saya input. Kamis selesai.'],
            ]);
        }
    }

    /**
     * @param  list<array{User, int, string, string}>  $lines  [pengirim, hari lalu, jam, isi]
     */
    private function addMessages(Conversation $conversation, array $lines, ?User $unreadFor = null, int $unreadCount = 0): void
    {
        $messages = [];

        foreach ($lines as [$sender, $daysAgo, $time, $body]) {
            $at = CarbonImmutable::today()->subDays($daysAgo)->setTimeFromTimeString($time);
            $message = new Message(['conversation_id' => $conversation->id, 'sender_id' => $sender->id, 'body' => $body, 'type' => MessageType::Text]);
            $message->created_at = $at;
            $message->updated_at = $at;
            $message->save();
            $messages[] = $message;
        }

        $last = end($messages);
        $conversation->update(['last_message_at' => $last->created_at]);

        $conversation->participants()->with('user')->get()->each(function (ConversationParticipant $participant) use ($messages, $last, $unreadFor, $unreadCount) {
            $readUpTo = $unreadFor?->is($participant->user) ? $messages[count($messages) - 1 - $unreadCount] : $last;
            $participant->update(['last_read_message_id' => $readUpTo->id, 'last_read_at' => $readUpTo->created_at]);
        });
    }
}
