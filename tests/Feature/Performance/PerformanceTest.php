<?php

use App\Models\User;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\FileService;
use App\Modules\Core\Services\FileUrlGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
});

function storeImage(int $width, int $height, FileVisibility $visibility = FileVisibility::Public, ?User $uploader = null): File
{
    return app(FileService::class)->store(UploadedFile::fake()->image('foto.jpg', $width, $height), FileDirectory::Website, $visibility, uploader: $uploader);
}

describe('thumbnail', function () {
    it('membuat thumbnail untuk gambar besar dan tidak untuk gambar kecil', function () {
        $large = storeImage(1200, 800);
        $small = storeImage(300, 200);

        expect($large->thumbnail_path)->toContain('/thumbs/')
            ->and($small->thumbnail_path)->toBeNull();

        Storage::disk('public')->assertExists($large->thumbnail_path);
        $thumb = (new ImageManager(new Driver))->read(Storage::disk('public')->get($large->thumbnail_path));
        expect(max($thumb->width(), $thumb->height()))->toBe(480);

        $urls = app(FileUrlGenerator::class);
        expect($urls->thumbnailUrl($small))->toBe($urls->url($small))
            ->and($urls->thumbnailUrl($large))->toContain('ukuran=kecil');
    });

    it('menyajikan thumbnail lewat /media dan /files dengan otorisasi yang sama', function () {
        $public = storeImage(1200, 800);
        $this->get("/media/{$public->public_id}?ukuran=kecil")->assertOk();
        expect(strlen((string) $this->get("/media/{$public->public_id}?ukuran=kecil")->streamedContent()))
            ->toBeLessThan(strlen((string) $this->get("/media/{$public->public_id}")->streamedContent()));

        $owner = User::factory()->withRole(Role::Operator)->create();
        $private = storeImage(1200, 800, FileVisibility::Private, $owner);
        $this->actingAsRole(Role::Operator, $owner);
        $this->get("/files/{$private->public_id}?ukuran=kecil")->assertOk();

        $this->actingAsRole(Role::Guardian);
        $this->get("/files/{$private->public_id}?ukuran=kecil")->assertForbidden();
    });

    it('prune menghapus file asli beserta thumbnail', function () {
        $file = storeImage(1200, 800);
        $file->delete();
        $this->travel(31)->days();

        $this->artisan('model:prune', ['--model' => [File::class]])->assertSuccessful();

        Storage::disk('public')->assertMissing($file->path);
        Storage::disk('public')->assertMissing($file->thumbnail_path);
    });

    it('perintah skoolia:thumbnails membuat thumbnail untuk gambar lama', function () {
        $file = storeImage(1200, 800);
        Storage::disk('public')->delete($file->thumbnail_path);
        $file->forceFill(['thumbnail_path' => null])->saveQuietly();

        $this->artisan('skoolia:thumbnails')->expectsOutputToContain('1 thumbnail dibuat')->assertSuccessful();

        expect($file->fresh()->thumbnail_path)->not->toBeNull();
    });
});

describe('filter tanggal sargable', function () {
    it('whereDateBetween inklusif di kedua ujung dan mengabaikan jam', function () {
        DB::table('users')->insert([
            ['name' => 'a', 'email' => 'a@x.test', 'password' => 'x', 'created_at' => '2026-09-01 00:00:00'],
            ['name' => 'b', 'email' => 'b@x.test', 'password' => 'x', 'created_at' => '2026-09-30 23:59:59'],
            ['name' => 'c', 'email' => 'c@x.test', 'password' => 'x', 'created_at' => '2026-10-01 00:00:00'],
            ['name' => 'd', 'email' => 'd@x.test', 'password' => 'x', 'created_at' => '2026-08-31 23:59:59'],
        ]);

        expect(DB::table('users')->whereDateBetween('created_at', '2026-09-01', '2026-09-30')->orderBy('name')->pluck('name')->all())->toBe(['a', 'b'])
            ->and(DB::table('users')->whereOnDate('created_at', '2026-09-30')->pluck('name')->all())->toBe(['b'])
            ->and(DB::table('users')->whereDateBetween('created_at', '2026-09-01', '2026-09-30')->toSql())->not->toContain('date(');
    });
});
