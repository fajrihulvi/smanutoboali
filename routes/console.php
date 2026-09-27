<?php

use App\Modules\Core\Models\File;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduler (shared hosting)
|--------------------------------------------------------------------------
|
| Cukup SATU cron di cPanel:
|   * * * * * cd /home/<user>/skoolia && php artisan schedule:run >> /dev/null 2>&1
|
| Tidak ada worker permanen: queue diproses tiap menit lalu berhenti saat kosong.
|
*/

Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

// Hapus permanen file yang > 30 hari di tempat sampah (File::prunable()).
Schedule::command('model:prune', ['--model' => [File::class]])->dailyAt('01:30');

// Hapus audit log lebih lama dari config('activitylog.delete_records_older_than_days').
Schedule::command('activitylog:clean --force')->dailyAt('02:00');

// Bersihkan token reset password kedaluwarsa.
Schedule::command('auth:clear-resets')->dailyAt('02:15');
