<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Chat\Events\ChatMessageReceived;
use App\Modules\Chat\Events\ConversationRead;
use App\Modules\Chat\Events\MessageSent;
use App\Modules\Chat\Events\MessageUpdated;
use App\Modules\Chat\Models\Conversation;
use App\Modules\Chat\Models\Message;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\SettingsService;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
});

/**
 * Wali kelas VII-A (guru) + orang tua Ani (siswa VII-A), guru lain yang tidak mengajar Ani,
 * orang tua lain, dan operator.
 *
 * @return array{teacher: User, parent: User, child: Student, otherTeacher: User, otherParent: User, operator: User}
 */
function chatScenario(): array
{
    $year = activeAcademicYear();
    $teacher = User::factory()->withRole(Role::Teacher)->create(['name' => 'Bu Rina']);
    $classroom = Classroom::factory()->for($year)->create([
        'name' => 'VII-A',
        'homeroom_teacher_id' => Teacher::factory()->withAccount($teacher)->create()->id,
    ]);

    $parent = User::factory()->withRole(Role::Guardian)->create(['name' => 'Pak Budi']);
    $child = Student::factory()->withGuardian(Guardian::factory()->withAccount($parent)->create())->create(['name' => 'Ani']);
    enrollIn($classroom, $child);

    $otherTeacher = User::factory()->withRole(Role::Teacher)->create(['name' => 'Pak Joko']);
    Teacher::factory()->withAccount($otherTeacher)->create();
    assignTeaching(Classroom::factory()->for($year)->create(['name' => 'VIII-B']), Teacher::query()->where('user_id', $otherTeacher->id)->sole());

    $otherParent = User::factory()->withRole(Role::Guardian)->create(['name' => 'Bu Sari']);
    enrollIn($classroom, Student::factory()->withGuardian(Guardian::factory()->withAccount($otherParent)->create())->create());

    $operator = User::factory()->withRole(Role::Operator)->create(['name' => 'Admin TU']);

    return compact('teacher', 'parent', 'child', 'otherTeacher', 'otherParent', 'operator');
}

function startChat(User $with, ?Student $student = null): string
{
    return test()->postJson('/chat/percakapan', ['user' => $with->public_id, 'student' => $student?->public_id])
        ->assertSuccessful()
        ->json('data.public_id');
}

function usePusherForChat(): void
{
    config([
        'broadcasting.default' => 'pusher',
        'broadcasting.connections.pusher.key' => 'test-key',
        'broadcasting.connections.pusher.secret' => 'test-secret',
        'broadcasting.connections.pusher.app_id' => '1',
        'broadcasting.connections.pusher.options.cluster' => 'ap1',
    ]);

    require base_path('routes/channels.php');
}

describe('eligibility', function () {
    it('orang tua bisa memulai chat dengan wali kelas anaknya (konteks anak)', function () {
        ['teacher' => $teacher, 'parent' => $parent, 'child' => $child] = chatScenario();
        $this->actingAsRole(Role::Guardian, $parent);

        $this->postJson('/chat/percakapan', ['user' => $teacher->public_id, 'student' => $child->public_id])
            ->assertCreated()
            ->assertJsonPath('data.other.name', 'Bu Rina')
            ->assertJsonPath('data.other.role', 'Wali Kelas VII-A')
            ->assertJsonPath('data.student.name', 'Ani')
            ->assertJsonPath('data.can_send', true)
            ->assertJsonMissingPath('data.id');
    });

    it('orang tua tidak bisa chat dengan guru yang tidak mengajar anaknya', function () {
        ['parent' => $parent, 'otherTeacher' => $otherTeacher, 'child' => $child] = chatScenario();
        $this->actingAsRole(Role::Guardian, $parent);

        $this->postJson('/chat/percakapan', ['user' => $otherTeacher->public_id])->assertForbidden();
        $this->postJson('/chat/percakapan', ['user' => $otherTeacher->public_id, 'student' => $child->public_id])->assertForbidden();
        expect(Conversation::query()->count())->toBe(0);
    });

    it('orang tua tidak bisa chat dengan orang tua lain', function () {
        ['parent' => $parent, 'otherParent' => $otherParent] = chatScenario();
        $this->actingAsRole(Role::Guardian, $parent);

        $this->postJson('/chat/percakapan', ['user' => $otherParent->public_id])->assertForbidden();
    });

    it('konteks anak harus anak dari pihak orang tua', function () {
        ['teacher' => $teacher, 'parent' => $parent] = chatScenario();
        $stranger = Student::factory()->create();
        $this->actingAsRole(Role::Guardian, $parent);

        $this->postJson('/chat/percakapan', ['user' => $teacher->public_id, 'student' => $stranger->public_id])->assertForbidden();
    });

    it('guru bisa memulai chat dengan orang tua siswanya dan dengan sesama guru', function () {
        ['teacher' => $teacher, 'parent' => $parent, 'child' => $child, 'otherTeacher' => $otherTeacher] = chatScenario();
        $this->actingAsRole(Role::Teacher, $teacher);

        $this->postJson('/chat/percakapan', ['user' => $parent->public_id, 'student' => $child->public_id])
            ->assertCreated()
            ->assertJsonPath('data.other.role', 'Orang tua Ani');
        $this->postJson('/chat/percakapan', ['user' => $otherTeacher->public_id])->assertCreated();
    });

    it('operator bisa chat dengan semua user aktif, orang tua juga bisa menghubungi operator', function () {
        ['operator' => $operator, 'otherParent' => $otherParent, 'parent' => $parent] = chatScenario();
        $this->actingAsRole(Role::Operator, $operator);
        $this->postJson('/chat/percakapan', ['user' => $otherParent->public_id])->assertCreated();

        $this->actingAsRole(Role::Guardian, $parent);
        $this->postJson('/chat/percakapan', ['user' => $operator->public_id])->assertCreated();
    });

    it('akun nonaktif dan calon orang tua PPDB tidak bisa dihubungi', function () {
        ['operator' => $operator] = chatScenario();
        $inactive = User::factory()->withRole(Role::Teacher)->inactive()->create();
        $applicant = User::factory()->withRole(Role::Applicant)->create();
        $this->actingAsRole(Role::Operator, $operator);

        $this->postJson('/chat/percakapan', ['user' => $inactive->public_id])->assertForbidden();
        $this->postJson('/chat/percakapan', ['user' => $applicant->public_id])->assertForbidden();
    });

    it('calon orang tua PPDB tidak punya akses API chat', function () {
        $this->actingAsRole(Role::Applicant);

        $this->getJson('/chat/percakapan')->assertForbidden();
    });
});

describe('percakapan', function () {
    it('memakai ulang percakapan yang sudah ada dari kedua arah', function () {
        ['teacher' => $teacher, 'parent' => $parent, 'child' => $child] = chatScenario();

        $this->actingAsRole(Role::Guardian, $parent);
        $first = startChat($teacher, $child);
        $this->postJson('/chat/percakapan', ['user' => $teacher->public_id, 'student' => $child->public_id])->assertOk();

        $this->actingAsRole(Role::Teacher, $teacher);
        expect(startChat($parent, $child))->toBe($first)
            ->and(Conversation::query()->count())->toBe(1);
    });

    it('daftar kontak orang tua dikelompokkan per anak, guru per rombel', function () {
        ['teacher' => $teacher, 'parent' => $parent] = chatScenario();

        $this->actingAsRole(Role::Guardian, $parent);
        $groups = $this->getJson('/chat/kontak')->assertOk()->json('groups');
        expect($groups[0]['title'])->toBe('Ani · VII-A')
            ->and($groups[0]['contacts'][0]['name'])->toBe('Bu Rina')
            ->and($groups[0]['contacts'][0]['context'])->toBe('Wali Kelas VII-A')
            ->and(collect($groups)->firstWhere('key', 'school')['contacts'][0]['name'])->toBe('Admin TU')
            ->and(collect($groups)->flatMap(fn ($g) => collect($g['contacts'])->pluck('name')))->not->toContain('Pak Joko', 'Bu Sari');

        $this->actingAsRole(Role::Teacher, $teacher);
        $groups = $this->getJson('/chat/kontak?q=budi')->assertOk()->json('groups');
        expect($groups)->toHaveCount(1)
            ->and($groups[0]['title'])->toBe('Rombel VII-A')
            ->and($groups[0]['contacts'][0]['context'])->toBe('Ibu dari Ani');
    });

    it('operator mencari orang tua lewat pencarian', function () {
        ['operator' => $operator] = chatScenario();
        $this->actingAsRole(Role::Operator, $operator);

        $groups = collect($this->getJson('/chat/kontak')->json('groups'))->keyBy('key');
        expect($groups['guardians']['contacts'])->toBe([])
            ->and($groups['guardians']['hint'])->not->toBeEmpty();

        $groups = collect($this->getJson('/chat/kontak?q=Budi')->json('groups'))->keyBy('key');
        expect($groups['guardians']['contacts'][0]['context'])->toBe('Orang tua Ani');
    });

    it('arsip hanya untuk user sendiri dan terbuka lagi saat ada pesan baru', function () {
        ['teacher' => $teacher, 'parent' => $parent, 'child' => $child] = chatScenario();

        $this->actingAsRole(Role::Guardian, $parent);
        $id = startChat($teacher, $child);
        $this->postJson("/chat/percakapan/{$id}/pesan", ['body' => 'Halo Bu'])->assertCreated();

        $this->actingAsRole(Role::Teacher, $teacher);
        $this->patchJson("/chat/percakapan/{$id}", ['archived' => true, 'muted' => true])
            ->assertOk()->assertJsonPath('data.archived', true)->assertJsonPath('data.muted', true);
        expect($this->getJson('/chat/percakapan')->json('data'))->toHaveCount(0)
            ->and($this->getJson('/chat/percakapan?arsip=1')->json('data'))->toHaveCount(1);

        $this->actingAsRole(Role::Guardian, $parent);
        expect($this->getJson('/chat/percakapan')->json('data'))->toHaveCount(1);
        $this->postJson("/chat/percakapan/{$id}/pesan", ['body' => 'Bu?'])->assertCreated();

        $this->actingAsRole(Role::Teacher, $teacher);
        $this->getJson('/chat/percakapan')->assertJsonPath('data.0.archived', false)->assertJsonPath('data.0.muted', true);
    });

    it('menjadi hanya-baca bila hubungan guru–orang tua sudah tidak berlaku', function () {
        ['teacher' => $teacher, 'parent' => $parent, 'child' => $child] = chatScenario();
        $this->actingAsRole(Role::Guardian, $parent);
        $id = startChat($teacher, $child);
        $this->postJson("/chat/percakapan/{$id}/pesan", ['body' => 'Halo'])->assertCreated();

        Classroom::query()->where('name', 'VII-A')->update(['homeroom_teacher_id' => null]);

        $this->getJson("/chat/percakapan/{$id}")->assertJsonPath('data.can_send', false);
        $this->getJson("/chat/percakapan/{$id}/pesan")->assertOk()->assertJsonCount(1, 'data');
        $this->postJson("/chat/percakapan/{$id}/pesan", ['body' => 'Lagi'])->assertJsonValidationErrors('body');
    });
});

describe('pesan', function () {
    it('peserta bisa membaca dan mengirim; non-peserta ditolak', function () {
        ['teacher' => $teacher, 'parent' => $parent, 'child' => $child, 'otherParent' => $otherParent, 'operator' => $operator] = chatScenario();

        $this->actingAsRole(Role::Guardian, $parent);
        $id = startChat($teacher, $child);
        $this->postJson("/chat/percakapan/{$id}/pesan", ['body' => 'Ani sakit hari ini'])
            ->assertCreated()
            ->assertJsonPath('data.is_mine', true)
            ->assertJsonPath('data.read', false)
            ->assertJsonPath('data.type', 'TEXT');

        foreach ([[Role::Guardian, $otherParent], [Role::Operator, $operator]] as [$role, $outsider]) {
            $this->actingAsRole($role, $outsider);
            $this->getJson("/chat/percakapan/{$id}")->assertForbidden();
            $this->getJson("/chat/percakapan/{$id}/pesan")->assertForbidden();
            $this->postJson("/chat/percakapan/{$id}/pesan", ['body' => 'Menyusup'])->assertForbidden();
            $this->postJson("/chat/percakapan/{$id}/baca")->assertForbidden();
        }

        expect(Message::query()->count())->toBe(1);
    });

    it('menghitung unread per percakapan & total, lalu status dibaca setelah dibuka', function () {
        ['teacher' => $teacher, 'parent' => $parent, 'child' => $child] = chatScenario();

        $this->actingAsRole(Role::Guardian, $parent);
        $id = startChat($teacher, $child);
        $this->postJson("/chat/percakapan/{$id}/pesan", ['body' => 'Satu'])->assertCreated();
        $this->postJson("/chat/percakapan/{$id}/pesan", ['body' => 'Dua'])->assertCreated();
        $this->getJson('/chat/belum-dibaca')->assertJsonPath('unread_total', 0);

        $this->actingAsRole(Role::Teacher, $teacher);
        $this->getJson('/chat/percakapan')
            ->assertJsonPath('data.0.unread', 2)
            ->assertJsonPath('data.0.last_message.preview', 'Dua')
            ->assertJsonPath('unread_total', 2);
        $this->get('/guru/dashboard')->assertInertia(fn (Assert $page) => $page->where('chat.unread', 2));

        Event::fake([ConversationRead::class]);
        $this->postJson("/chat/percakapan/{$id}/baca")->assertOk()->assertJsonPath('unread_total', 0);
        $this->postJson("/chat/percakapan/{$id}/baca")->assertOk();
        Event::assertDispatchedTimes(ConversationRead::class, 1);

        $this->actingAsRole(Role::Guardian, $parent);
        $messages = $this->getJson("/chat/percakapan/{$id}/pesan")->json('data');
        expect(collect($messages)->pluck('read')->all())->toBe([true, true]);
    });

    it('menyimpan dulu lalu broadcast ke channel percakapan dan channel penerima', function () {
        ['teacher' => $teacher, 'parent' => $parent, 'child' => $child] = chatScenario();
        $this->actingAsRole(Role::Guardian, $parent);
        $id = startChat($teacher, $child);

        Event::fake([MessageSent::class, ChatMessageReceived::class]);
        $this->postJson("/chat/percakapan/{$id}/pesan", ['body' => 'Halo Bu'])->assertCreated();

        Event::assertDispatched(MessageSent::class, fn (MessageSent $e) => $e->broadcastOn()->name === "private-conversation.{$id}"
            && $e->broadcastWith()['message']['body'] === 'Halo Bu'
            && ! array_key_exists('is_mine', $e->broadcastWith()['message']));
        Event::assertDispatched(ChatMessageReceived::class, fn (ChatMessageReceived $e) => $e->recipient->is($teacher)
            && $e->broadcastOn()->name === "private-App.Models.User.{$teacher->public_id}"
            && $e->broadcastWith()['unread_total'] === 1
            && $e->broadcastWith()['preview'] === 'Halo Bu');
    });

    it('broadcast yang gagal tidak menggagalkan pengiriman pesan', function () {
        ['teacher' => $teacher, 'parent' => $parent, 'child' => $child] = chatScenario();
        $this->actingAsRole(Role::Guardian, $parent);
        $id = startChat($teacher, $child);

        Broadcast::extend('rusak', fn () => new class extends Broadcaster
        {
            public function auth($request): mixed
            {
                return null;
            }

            public function validAuthenticationResponse($request, $result): mixed
            {
                return null;
            }

            public function broadcast(array $channels, $event, array $payload = []): void
            {
                throw new RuntimeException('Pusher tidak terjangkau');
            }
        });
        config(['broadcasting.connections.rusak' => ['driver' => 'rusak'], 'broadcasting.default' => 'rusak']);

        $this->postJson("/chat/percakapan/{$id}/pesan", ['body' => 'Tetap tersimpan'])->assertCreated();
        expect(Message::query()->where('body', 'Tetap tersimpan')->exists())->toBeTrue();
    });

    it('lampiran gambar/PDF tersimpan privat dan hanya peserta yang bisa membukanya', function () {
        ['teacher' => $teacher, 'parent' => $parent, 'child' => $child, 'otherParent' => $otherParent] = chatScenario();
        $this->actingAsRole(Role::Guardian, $parent);
        $id = startChat($teacher, $child);

        $response = $this->post("/chat/percakapan/{$id}/pesan", ['file' => UploadedFile::fake()->image('surat.jpg', 800, 600)], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'IMAGE')
            ->assertJsonPath('data.attachment.is_image', true);
        $file = File::query()->where('public_id', $response->json('data.attachment.public_id'))->sole();
        expect($file->visibility->value)->toBe('private');

        $this->actingAsRole(Role::Teacher, $teacher);
        $this->get(route('files.show', $file))->assertOk();
        $this->actingAsRole(Role::Guardian, $otherParent);
        $this->get(route('files.show', $file))->assertForbidden();

        $this->actingAsRole(Role::Guardian, $parent);
        $this->post("/chat/percakapan/{$id}/pesan", ['file' => UploadedFile::fake()->create('virus.exe', 10)], ['Accept' => 'application/json'])
            ->assertJsonValidationErrors('file');
        $this->post("/chat/percakapan/{$id}/pesan", ['file' => UploadedFile::fake()->image('besar.jpg')->size(6000)], ['Accept' => 'application/json'])
            ->assertJsonValidationErrors('file');
        $this->postJson("/chat/percakapan/{$id}/pesan", ['body' => '   '])->assertJsonValidationErrors('body');
    });

    it('memuat pesan lama dengan cursor pagination', function () {
        ['teacher' => $teacher, 'parent' => $parent, 'child' => $child] = chatScenario();
        $this->actingAsRole(Role::Guardian, $parent);
        $id = startChat($teacher, $child);
        $conversation = Conversation::query()->where('public_id', $id)->sole();
        foreach (range(1, Message::PER_PAGE + 5) as $i) {
            Message::query()->create(['conversation_id' => $conversation->id, 'sender_id' => $parent->id, 'body' => "Pesan {$i}", 'type' => 'TEXT']);
        }

        $first = $this->getJson("/chat/percakapan/{$id}/pesan")->assertOk();
        expect($first->json('data'))->toHaveCount(Message::PER_PAGE)
            ->and($first->json('data.0.body'))->toBe('Pesan 6')
            ->and(last($first->json('data'))['body'])->toBe('Pesan 35');

        $older = $this->getJson("/chat/percakapan/{$id}/pesan?cursor=".$first->json('next_cursor'))->assertOk();
        expect(collect($older->json('data'))->pluck('body')->all())->toBe(['Pesan 1', 'Pesan 2', 'Pesan 3', 'Pesan 4', 'Pesan 5'])
            ->and($older->json('next_cursor'))->toBeNull();
    });

    it('membatasi 30 pesan per menit', function () {
        ['teacher' => $teacher, 'parent' => $parent, 'child' => $child] = chatScenario();
        $this->actingAsRole(Role::Guardian, $parent);
        $id = startChat($teacher, $child);

        foreach (range(1, 30) as $i) {
            $this->postJson("/chat/percakapan/{$id}/pesan", ['body' => "Pesan {$i}"])->assertCreated();
        }

        $this->postJson("/chat/percakapan/{$id}/pesan", ['body' => 'Satu lagi'])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Terlalu banyak pesan. Tunggu sebentar lalu coba lagi.');
    });

    it('pengirim bisa mengubah (15 menit) dan menghapus (24 jam) pesannya sendiri', function () {
        ['teacher' => $teacher, 'parent' => $parent, 'child' => $child] = chatScenario();
        $this->actingAsRole(Role::Guardian, $parent);
        $id = startChat($teacher, $child);
        $messageId = $this->post("/chat/percakapan/{$id}/pesan", ['body' => 'Salah tulis', 'file' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])
            ->json('data.public_id');
        $textId = $this->postJson("/chat/percakapan/{$id}/pesan", ['body' => 'Halo Bu'])->json('data.public_id');

        Event::fake([MessageUpdated::class]);
        $this->patchJson("/chat/pesan/{$textId}", ['body' => 'Halo Bu Rina'])
            ->assertOk()->assertJsonPath('data.body', 'Halo Bu Rina')->assertJsonPath('data.edited_at', fn ($v) => $v !== null);

        $this->actingAsRole(Role::Teacher, $teacher);
        $this->patchJson("/chat/pesan/{$textId}", ['body' => 'Dibajak'])->assertForbidden();
        $this->deleteJson("/chat/pesan/{$textId}")->assertForbidden();

        $this->actingAsRole(Role::Guardian, $parent);
        $this->deleteJson("/chat/pesan/{$messageId}")->assertOk()->assertJsonPath('data.deleted', true)->assertJsonPath('data.body', null);
        expect(File::query()->count())->toBe(0);
        Event::assertDispatchedTimes(MessageUpdated::class, 2);

        $this->travel(16)->minutes();
        $this->patchJson("/chat/pesan/{$textId}", ['body' => 'Terlambat'])->assertForbidden();
        $this->travel(1)->days();
        $this->deleteJson("/chat/pesan/{$textId}")->assertForbidden();

        $messages = $this->getJson("/chat/percakapan/{$id}/pesan")->json('data');
        expect($messages[0]['deleted'])->toBeTrue()
            ->and($messages[0]['attachment'])->toBeNull();
    });
});

describe('channel percakapan', function () {
    it('hanya peserta yang bisa subscribe', function () {
        ['teacher' => $teacher, 'parent' => $parent, 'child' => $child, 'otherParent' => $otherParent] = chatScenario();
        $this->actingAsRole(Role::Guardian, $parent);
        $id = startChat($teacher, $child);
        usePusherForChat();

        $auth = fn () => $this->postJson('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-conversation.{$id}"]);

        $auth()->assertOk();
        $this->actingAsRole(Role::Teacher, $teacher);
        $auth()->assertOk();
        $this->actingAsRole(Role::Guardian, $otherParent);
        $auth()->assertForbidden();
    });

    it('presence status online hanya bila diaktifkan', function () {
        ['parent' => $parent] = chatScenario();
        $this->actingAsRole(Role::Guardian, $parent);
        usePusherForChat();

        $this->postJson('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'presence-chat.online'])->assertForbidden();

        app(SettingsService::class)->set(['chat_presence_enabled' => true]);
        $this->postJson('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'presence-chat.online'])
            ->assertOk()->assertJsonStructure(['auth', 'channel_data']);
    });
});
