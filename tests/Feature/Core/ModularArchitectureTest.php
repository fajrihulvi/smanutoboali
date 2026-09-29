<?php

use App\Console\Commands\MakeModuleCommand;
use App\Support\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

describe('make:module', function () {
    $module = 'PestTmpModule';

    beforeEach(function () {
        $this->providersBackup = file_get_contents(base_path('bootstrap/providers.php'));
    });

    afterEach(function () use ($module) {
        $files = new Filesystem;
        $files->deleteDirectory(app_path('Modules/'.$module));
        $files->deleteDirectory(resource_path('js/Modules/'.$module));
        file_put_contents(base_path('bootstrap/providers.php'), $this->providersBackup);
    });

    it('membuat struktur folder modul lengkap dan mendaftarkan provider', function () use ($module) {
        $this->artisan('make:module', ['name' => $module])->assertSuccessful();

        foreach (MakeModuleCommand::BACKEND_DIRECTORIES as $directory) {
            expect(is_dir(app_path("Modules/{$module}/{$directory}")))->toBeTrue("{$directory} tidak dibuat");
        }

        foreach (MakeModuleCommand::FRONTEND_DIRECTORIES as $directory) {
            expect(is_dir(resource_path("js/Modules/{$module}/{$directory}")))->toBeTrue();
        }

        expect(app_path("Modules/{$module}/{$module}ServiceProvider.php"))->toBeFile()
            ->and(app_path("Modules/{$module}/routes/web.php"))->toBeFile()
            ->and(file_get_contents(base_path('bootstrap/providers.php')))
            ->toContain("App\\Modules\\{$module}\\{$module}ServiceProvider::class");
    });

    it('menolak modul yang sudah ada', function () {
        $this->artisan('make:module', ['name' => 'Core'])->assertFailed();
    });

    it('menolak nama modul tidak valid', function () {
        $this->artisan('make:module', ['name' => '123-abc'])->assertFailed();
    });
});

it('me-render halaman modul dengan format Module::Path', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth::PortalSelect'));
});

it('HasPublicId mengisi ULID otomatis dan menjadikannya route key', function () {
    Schema::create('public_id_dummies', function ($table) {
        $table->id();
        $table->ulid('public_id')->unique();
    });

    $model = new class extends Model
    {
        use HasPublicId;

        protected $table = 'public_id_dummies';

        public $timestamps = false;
    };

    $model->save();

    expect($model->public_id)->toBeString()->toHaveLength(26)
        ->and($model->getRouteKeyName())->toBe('public_id')
        ->and($model->getRouteKey())->toBe($model->public_id);
});
