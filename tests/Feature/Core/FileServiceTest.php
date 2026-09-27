<?php

use App\Models\User;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\FileService;
use App\Modules\Core\Services\FileUrlGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
});

function storeFile(UploadedFile $upload, ?User $uploader = null, FileVisibility $visibility = FileVisibility::Private, mixed $fileable = null): File
{
    return app(FileService::class)->store($upload, FileDirectory::Misc, $visibility, $fileable, uploader: $uploader);
}

describe('validasi upload', function () {
    it('menolak file dengan MIME palsu (isi PHP berekstensi .jpg)', function () {
        $fake = UploadedFile::fake()->createWithContent('foto.jpg', '<?php echo "hack"; ?>');

        expect(fn () => storeFile($fake))->toThrow(ValidationException::class, 'Tipe file tidak diizinkan.');
        expect(File::count())->toBe(0);
    });

    it('menolak file yang melebihi ukuran maksimum', function () {
        $big = UploadedFile::fake()->image('besar.jpg')->size(6000);

        expect(fn () => storeFile($big))->toThrow(ValidationException::class);
    });

    it('menerima PDF berdasarkan isi file', function () {
        $pdf = UploadedFile::fake()->createWithContent('rapor.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");

        $file = storeFile($pdf);

        expect($file->mime_type)->toBe('application/pdf')
            ->and($file->original_name)->toBe('rapor.pdf');
    });
});

it('menyimpan dengan nama acak di disk privat', function () {
    $file = storeFile(UploadedFile::fake()->image('Foto Siswa.png', 200, 100));

    expect($file->stored_name)->not->toContain('Foto')
        ->and($file->stored_name)->toEndWith('.png')
        ->and($file->path)->toStartWith('misc/')
        ->and($file->visibility)->toBe(FileVisibility::Private)
        ->and(strlen($file->public_id))->toBe(26);

    Storage::disk('private')->assertExists($file->path);
    Storage::disk('public')->assertMissing($file->path);
});

it('memperkecil gambar lebih dari 1600px', function () {
    $file = storeFile(UploadedFile::fake()->image('besar.jpg', 3200, 2000));

    expect($file->width)->toBe(1600)
        ->and($file->height)->toBe(1000);

    [$width] = getimagesizefromstring(Storage::disk('private')->get($file->path));
    expect($width)->toBe(1600);
});

it('tidak mengubah gambar yang sudah kecil', function () {
    $file = storeFile(UploadedFile::fake()->image('kecil.jpg', 800, 600));

    expect($file->width)->toBe(800)->and($file->height)->toBe(600);
});

describe('akses file', function () {
    it('pengunggah dapat membuka file privatnya', function () {
        $owner = $this->actingAsRole(Role::Teacher);
        $file = storeFile(UploadedFile::fake()->image('a.jpg'), $owner);

        $this->get("/files/{$file->public_id}")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    });

    it('user yang tidak berhak mendapat 403 untuk file privat', function () {
        $owner = User::factory()->withRole(Role::Teacher)->create();
        $file = storeFile(UploadedFile::fake()->image('a.jpg'), $owner);

        $this->actingAsRole(Role::Teacher);
        $this->get("/files/{$file->public_id}")->assertForbidden();

        $this->actingAsRole(Role::Guardian);
        $this->get("/files/{$file->public_id}")->assertForbidden();
    });

    it('otorisasi didelegasikan ke policy pemilik file (fileable)', function () {
        $owner = User::factory()->withRole(Role::Teacher)->create();
        $uploader = User::factory()->withRole(Role::Operator)->create();
        $file = storeFile(UploadedFile::fake()->image('foto.jpg'), $uploader, fileable: $owner);

        // Pemilik (UserPolicy::view → diri sendiri) boleh.
        $this->actingAsRole(Role::Teacher, $owner);
        $this->get("/files/{$file->public_id}")->assertOk();

        // Guru lain ditolak.
        $this->actingAsRole(Role::Teacher);
        $this->get("/files/{$file->public_id}")->assertForbidden();

        // Operator lain dengan user.manage boleh.
        $this->actingAsRole(Role::Operator);
        $this->get("/files/{$file->public_id}")->assertOk();
    });

    it('tamu diarahkan ke login dan id numerik tidak dikenali', function () {
        $file = storeFile(UploadedFile::fake()->image('a.jpg'));

        $this->get("/files/{$file->public_id}")->assertRedirect('/login');

        $this->actingAsRole(Role::Operator);
        $this->get("/files/{$file->id}")->assertNotFound();
    });

    it('file publik tersedia lewat fallback /media tanpa login', function () {
        $file = storeFile(UploadedFile::fake()->image('logo.png'), visibility: FileVisibility::Public);

        Storage::disk('public')->assertExists($file->path);
        $this->get("/media/{$file->public_id}")->assertOk()->assertHeader('Content-Type', 'image/png');
    });

    it('file privat tidak bisa dibuka lewat /media', function () {
        $file = storeFile(UploadedFile::fake()->image('a.jpg'));

        $this->get("/media/{$file->public_id}")->assertNotFound();
    });

    it('URL file privat selalu lewat route berotorisasi', function () {
        $file = storeFile(UploadedFile::fake()->image('a.jpg'));

        expect(app(FileUrlGenerator::class)->url($file))->toBe(route('files.show', $file));
    });
});

it('file terhapus dibersihkan permanen setelah 30 hari', function () {
    $file = storeFile(UploadedFile::fake()->image('a.jpg'));
    app(FileService::class)->delete($file);

    $this->travel(31)->days();
    $this->artisan('model:prune', ['--model' => [File::class]])->assertSuccessful();

    expect(File::withTrashed()->count())->toBe(0);
    Storage::disk('private')->assertMissing($file->path);
});
