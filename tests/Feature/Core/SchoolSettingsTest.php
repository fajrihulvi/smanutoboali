<?php

use App\Modules\Core\Enums\Role;
use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

function validSettings(array $overrides = []): array
{
    return array_merge([
        'school_name' => 'SMP Negeri 1 Pangkalpinang',
        'school_address' => 'Jl. Merdeka No. 1',
        'school_phone' => '(0717) 123456',
        'school_email' => 'info@smpn1.sch.id',
        'timezone' => 'Asia/Jakarta',
        'upload_max_size_kb' => 4096,
    ], $overrides);
}

it('memakai nilai default sebelum diatur', function () {
    expect(app(SettingsService::class)->get(SettingKey::SchoolName))->toBe('Sekolah Skoolia')
        ->and(app(SettingsService::class)->uploadMaxKb())->toBe(5120);
});

it('operator membuka halaman pengaturan', function () {
    $this->actingAsRole(Role::Operator);

    $this->get('/operator/settings')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Core::Operator/Settings')
            ->where('settings.school_name', 'Sekolah Skoolia')
            ->has('timezones', 3));
});

it('menyimpan pengaturan, membersihkan cache, dan mencatat audit', function () {
    $this->actingAsRole(Role::Operator);
    $settings = app(SettingsService::class);
    $settings->all(); // isi cache

    $this->post('/operator/settings', validSettings())
        ->assertSessionHasNoErrors()
        ->assertRedirect('/operator/settings');

    $fresh = new SettingsService;
    expect($fresh->get(SettingKey::SchoolName))->toBe('SMP Negeri 1 Pangkalpinang')
        ->and($fresh->uploadMaxKb())->toBe(4096);

    $log = Activity::query()->where('log_name', 'settings')->firstOrFail();
    expect($log->properties['attributes'])->toHaveKey('school_name');

    // Nama sekolah ikut dibagikan ke Inertia.
    $this->get('/operator/dashboard')
        ->assertInertia(fn (Assert $page) => $page->where('school.name', 'SMP Negeri 1 Pangkalpinang'));
});

it('mengunggah logo sebagai file publik dan mengganti logo lama', function () {
    Storage::fake('public');
    $this->actingAsRole(Role::Operator);

    $this->post('/operator/settings', validSettings(['logo' => UploadedFile::fake()->image('logo.png', 300, 300)]))
        ->assertSessionHasNoErrors();

    $first = File::query()->firstOrFail();
    expect(app(SettingsService::class)->get(SettingKey::SchoolLogo))->toBe($first->public_id);

    $this->post('/operator/settings', validSettings(['logo' => UploadedFile::fake()->image('baru.png', 300, 300)]));

    expect($first->fresh()->trashed())->toBeTrue()
        ->and(File::query()->count())->toBe(1);

    $this->get('/operator/dashboard')
        ->assertInertia(fn (Assert $page) => $page->whereNot('school.logo_url', null));
});

it('memvalidasi input pengaturan', function () {
    $this->actingAsRole(Role::Operator);

    $this->post('/operator/settings', validSettings([
        'school_name' => '',
        'timezone' => 'Europe/London',
        'upload_max_size_kb' => 999999,
        'school_phone' => 'telp<script>',
    ]))->assertSessionHasErrors(['school_name', 'timezone', 'upload_max_size_kb', 'school_phone']);
});

it('menolak logo dengan MIME palsu walau berekstensi .png', function () {
    Storage::fake('public');
    $this->actingAsRole(Role::Operator);

    $this->post('/operator/settings', validSettings([
        'logo' => UploadedFile::fake()->createWithContent('logo.png', '<?php echo 1;'),
    ]))->assertSessionHasErrors(['logo' => 'Tipe file tidak diizinkan.']);

    expect(File::query()->count())->toBe(0);
});

it('role lain tidak dapat membuka atau mengubah pengaturan', function (Role $role) {
    $this->actingAsRole($role);

    $this->get('/operator/settings')->assertForbidden();
    $this->post('/operator/settings', validSettings())->assertForbidden();
})->with([Role::Principal, Role::Teacher, Role::Guardian]);

it('menerapkan zona waktu dari pengaturan', function () {
    app(SettingsService::class)->set([SettingKey::Timezone->value => 'Asia/Makassar']);

    app(SettingsService::class)->applyRuntimeConfig();

    expect(config('app.timezone'))->toBe('Asia/Makassar');

    app(SettingsService::class)->set([SettingKey::Timezone->value => 'Asia/Jakarta']);
    app(SettingsService::class)->applyRuntimeConfig();
});
