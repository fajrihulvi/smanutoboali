<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name : Nama modul dalam StudlyCase, contoh Attendance}';

    protected $description = 'Membuat kerangka modul baru di app/Modules dan resources/js/Modules';

    /**
     * Folder backend yang dibuat untuk setiap modul (lihat CLAUDE.md).
     *
     * @var list<string>
     */
    public const BACKEND_DIRECTORIES = [
        'routes',
        'Models',
        'Enums',
        'Actions',
        'Services',
        'Policies',
        'Http/Controllers/Principal',
        'Http/Controllers/Teacher',
        'Http/Controllers/Operator',
        'Http/Controllers/Guardian',
        'Http/Requests',
        'Http/Resources',
        'Events',
        'Listeners',
        'Notifications',
        'Jobs',
        'Database/Migrations',
        'Database/Factories',
        'Database/Seeders',
    ];

    /**
     * @var list<string>
     */
    public const FRONTEND_DIRECTORIES = ['Pages', 'Components'];

    public function handle(Filesystem $files): int
    {
        $name = Str::studly((string) $this->argument('name'));

        if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $name)) {
            $this->components->error('Nama modul hanya boleh huruf dan angka, diawali huruf.');

            return self::FAILURE;
        }

        $backend = app_path('Modules/'.$name);
        $frontend = resource_path('js/Modules/'.$name);

        if ($files->exists($backend) || $files->exists($frontend)) {
            $this->components->error("Modul [{$name}] sudah ada.");

            return self::FAILURE;
        }

        foreach (self::BACKEND_DIRECTORIES as $directory) {
            $files->ensureDirectoryExists($backend.'/'.$directory);

            if ($directory !== 'routes') {
                $files->put($backend.'/'.$directory.'/.gitkeep', '');
            }
        }

        foreach (self::FRONTEND_DIRECTORIES as $directory) {
            $files->ensureDirectoryExists($frontend.'/'.$directory);
            $files->put($frontend.'/'.$directory.'/.gitkeep', '');
        }

        $files->put($backend.'/'.$name.'ServiceProvider.php', $this->renderStub($files, 'provider', $name));
        $files->put($backend.'/routes/web.php', $this->renderStub($files, 'routes', $name));

        ServiceProvider::addProviderToBootstrapFile(
            'App\\Modules\\'.$name.'\\'.$name.'ServiceProvider',
            $this->laravel->getBootstrapProvidersPath(),
        );

        $this->components->info("Modul [{$name}] berhasil dibuat dan provider didaftarkan.");

        return self::SUCCESS;
    }

    private function renderStub(Filesystem $files, string $stub, string $module): string
    {
        return str_replace('{{ module }}', $module, $files->get(base_path("stubs/module/{$stub}.stub")));
    }
}
