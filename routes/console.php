<?php

use App\Modules\Core\Models\File;
use App\Modules\Core\Services\HealthCheckService;
use App\Modules\ReportCard\Models\ReportCardExport;
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

// Detak scheduler untuk GET /health (cron benar-benar berjalan).
Schedule::call(fn () => HealthCheckService::markSchedulerRan())
    ->everyMinute()
    ->name('scheduler-heartbeat');

Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

// Pengumuman terjadwal yang sudah tiba waktunya → notifikasi audiens (S23).
Schedule::command('announcements:publish-due')->everyMinute()->withoutOverlapping();

// Pengingat tagihan H-3 & H+1 jatuh tempo (S24).
Schedule::command('finance:remind')->dailyAt('07:00')->withoutOverlapping();

// Hapus permanen file > 30 hari di tempat sampah (File::prunable()) dan ZIP cetak raport > 7 hari.
Schedule::command('model:prune', ['--model' => [File::class, ReportCardExport::class]])->dailyAt('01:30');

// Hapus audit log lebih lama dari config('activitylog.delete_records_older_than_days').
Schedule::command('activitylog:clean --force')->dailyAt('02:00');

// Bersihkan token reset password kedaluwarsa.
Schedule::command('auth:clear-resets')->dailyAt('02:15');
