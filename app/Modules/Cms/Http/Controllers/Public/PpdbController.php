<?php

namespace App\Modules\Cms\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Ppdb\Models\PpdbPeriod;
use Illuminate\View\View;

/**
 * Info PPDB: periode aktif/mendatang, jalur & kuota, persyaratan, tombol daftar & cek hasil.
 */
class PpdbController extends Controller
{
    public function __invoke(): View
    {
        $periods = PpdbPeriod::query()
            ->where('is_active', true)
            ->where('closes_at', '>=', now())
            ->with(['academicYear', 'tracks' => fn ($q) => $q->orderBy('position'), 'tracks.requirements' => fn ($q) => $q->orderBy('position')])
            ->orderBy('opens_at')
            ->get();

        return view('public.ppdb', [
            'periods' => $periods,
            'hasPublishedResults' => PpdbPeriod::query()->whereNotNull('results_published_at')->exists(),
            'seo' => ['title' => 'PPDB', 'description' => 'Informasi Penerimaan Peserta Didik Baru: jadwal, jalur, kuota, dan persyaratan pendaftaran online.'],
        ]);
    }
}
