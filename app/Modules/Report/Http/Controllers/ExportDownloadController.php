<?php

namespace App\Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Report\Enums\ExportStatus;
use App\Modules\Report\Models\ReportExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * GET /laporan/ekspor/{export} — unduh hasil ekspor antrean (hanya pemilik, 24 jam).
 */
class ExportDownloadController extends Controller
{
    public function __invoke(Request $request, ReportExport $export): StreamedResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($export->user_id === $user->id, 404);

        if ($export->isExpired()) {
            return redirect('/dashboard')->with('error', 'Tautan unduhan sudah kedaluwarsa (berlaku 24 jam). Silakan ekspor ulang laporan.');
        }

        if ($export->status !== ExportStatus::Done) {
            return redirect('/dashboard')->with('error', $export->status === ExportStatus::Failed
                ? 'Ekspor laporan ini gagal. Silakan coba lagi.'
                : 'Laporan masih disiapkan. Coba lagi sebentar lagi.');
        }

        $file = $export->file;
        abort_if($file === null || ! Storage::disk($file->disk)->exists($file->path), 404);

        $filename = Str::slug($export->title.' '.$export->created_at->format('Y-m-d Hi')).'.'.$export->format->value;

        return Storage::disk($file->disk)->download($file->path, $filename, [
            'Content-Type' => $export->format->mime(),
            'Cache-Control' => 'no-store',
        ]);
    }
}
