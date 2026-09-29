<?php

namespace App\Modules\Core\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\SystemStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * /operator/sistem (S34): versi, detak scheduler/antrean, job gagal, backup, storage, log.
 * Route dibatasi `permission:setting.manage`.
 */
class SystemController extends Controller
{
    public function __construct(private SystemStatus $status, private AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Core::Operator/System', [
            'app' => $this->status->app(),
            'health' => $this->status->health(),
            'queue' => $this->status->queue(),
            'backups' => $this->status->backups(),
            'storage' => $this->status->storage($request->boolean('segarkan')),
            'logs' => $this->status->logs(),
        ]);
    }

    public function retry(string $uuid): RedirectResponse
    {
        abort_unless(DB::table(config('queue.failed.table', 'failed_jobs'))->where('uuid', $uuid)->exists(), 404);

        Artisan::call('queue:retry', ['id' => [$uuid]]);
        $this->audit->record('system', 'job_retried', "Mengulang job gagal {$uuid}");

        return back()->with('success', 'Job dimasukkan kembali ke antrean dan akan diproses dalam ±1 menit.');
    }

    public function retryAll(): RedirectResponse
    {
        $count = DB::table(config('queue.failed.table', 'failed_jobs'))->count();

        Artisan::call('queue:retry', ['id' => ['all']]);
        $this->audit->record('system', 'job_retried', "Mengulang semua job gagal ({$count})");

        return back()->with('success', "{$count} job dimasukkan kembali ke antrean.");
    }

    public function forget(string $uuid): RedirectResponse
    {
        abort_unless(DB::table(config('queue.failed.table', 'failed_jobs'))->where('uuid', $uuid)->exists(), 404);

        Artisan::call('queue:forget', ['id' => $uuid]);
        $this->audit->record('system', 'job_deleted', "Menghapus job gagal {$uuid}");

        return back()->with('success', 'Job gagal dihapus.');
    }

    /**
     * Backup database lewat antrean (tidak di request web: hindari batas waktu PHP).
     */
    public function backup(): RedirectResponse
    {
        Artisan::queue('skoolia:backup', ['type' => 'database']);
        $this->audit->record('system', 'backup_requested', 'Meminta backup database manual');

        return back()->with('success', 'Backup database dijadwalkan dan akan selesai dalam ±1–2 menit. Muat ulang halaman untuk melihat hasilnya.');
    }
}
