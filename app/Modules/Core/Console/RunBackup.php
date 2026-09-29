<?php

namespace App\Modules\Core\Console;

use Exception;
use Illuminate\Console\Command;
use Spatie\Backup\BackupDestination\BackupDestinationFactory;
use Spatie\Backup\Config\Config;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\CleanupHasFailed;
use Spatie\Backup\Exceptions\BackupFailed;
use Spatie\Backup\Helpers\ConsoleOutput;
use Spatie\Backup\Tasks\Backup\BackupJobFactory;
use Spatie\Backup\Tasks\Cleanup\CleanupJob;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;

/**
 * Pembungkus spatie/laravel-backup (S34): database dan file disimpan dengan nama backup
 * berbeda ("<nama>-database" / "<nama>-files") agar retensi & monitor masing-masing
 * tidak saling memangkas (strategi mingguan hanya menyimpan backup terbaru per minggu).
 *
 *   php artisan skoolia:backup database   → dump DB (harian)
 *   php artisan skoolia:backup files      → file unggahan (mingguan)
 *   php artisan skoolia:backup clean      → bersihkan keduanya sesuai retensi 7/4/3
 *
 * Perintah bawaan `backup:run` menerima Config saat boot, jadi nama backup tidak bisa
 * diganti lewat config() di runtime — job dibuat langsung di sini.
 */
class RunBackup extends Command
{
    protected $signature = 'skoolia:backup {type : database|files|clean}';

    protected $description = 'Jalankan backup database/file atau bersihkan backup lama';

    public const TYPES = ['database', 'files'];

    public function handle(): int
    {
        app(ConsoleOutput::class)->setCommand($this);
        $type = (string) $this->argument('type');

        if ($type === 'clean') {
            return collect(self::TYPES)->map(fn (string $kind) => $this->clean($kind))->max() ?? self::SUCCESS;
        }

        if (! in_array($type, self::TYPES, true)) {
            $this->error('Jenis backup harus database, files, atau clean.');

            return self::INVALID;
        }

        return $this->backup($type);
    }

    public static function nameFor(string $type): string
    {
        return config('backup.backup.name').'-'.$type;
    }

    public static function configFor(string $type): Config
    {
        $data = config('backup');
        $data['backup']['name'] = self::nameFor($type);

        return Config::fromArray($data);
    }

    private function backup(string $type): int
    {
        $this->comment("Backup {$type} dimulai ({$this->nameFor($type)})...");

        try {
            $job = BackupJobFactory::createFromConfig(self::configFor($type));
            $type === 'database' ? $job->dontBackupFilesystem() : $job->dontBackupDatabases();
            $job->disableSignals();
            $job->run();

            $this->info('Backup selesai.');

            return self::SUCCESS;
        } catch (Exception $exception) {
            $this->error("Backup gagal: {$exception->getMessage()}");
            report($exception);

            event($exception instanceof BackupFailed
                ? new BackupHasFailed($exception->getPrevious(), $exception->backupDestination)
                : new BackupHasFailed($exception));

            return self::FAILURE;
        }
    }

    private function clean(string $type): int
    {
        try {
            $destinations = BackupDestinationFactory::createFromArray(self::configFor($type));
            (new CleanupJob($destinations, app(DefaultStrategy::class)))->run();
            $this->info("Pembersihan {$type} selesai.");

            return self::SUCCESS;
        } catch (Exception $exception) {
            $this->error("Pembersihan {$type} gagal: {$exception->getMessage()}");
            event(new CleanupHasFailed($exception));

            return self::FAILURE;
        }
    }
}
