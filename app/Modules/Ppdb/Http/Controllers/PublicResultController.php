<?php

namespace App\Modules\Ppdb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cek hasil seleksi publik: nomor pendaftaran + tanggal lahir; hanya setelah hasil diumumkan.
 */
class PublicResultController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Ppdb::Public/Result', [
            'result' => $request->session()->get('ppdb_result'),
        ]);
    }

    public function check(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:20'],
            'birth_date' => ['required', 'date_format:Y-m-d'],
        ], [], ['number' => 'nomor pendaftaran', 'birth_date' => 'tanggal lahir']);

        $applicant = PpdbApplicant::query()
            ->where('registration_number', mb_strtoupper(trim($data['number'])))
            ->whereDate('birth_date', $data['birth_date'])
            ->with('period')
            ->first();

        // Pesan sama untuk "tidak ada" dan "belum diumumkan" agar data tidak bisa ditebak.
        if ($applicant === null || ! $applicant->resultPublished()) {
            return back()->withErrors(['number' => 'Hasil tidak ditemukan atau belum diumumkan. Periksa nomor pendaftaran & tanggal lahir.'])->withInput();
        }

        return back()->with('ppdb_result', [
            'number' => $applicant->registration_number,
            'name' => $applicant->name,
            'period' => $applicant->period->name,
            'accepted' => in_array($applicant->status, [ApplicantStatus::Accepted, ApplicantStatus::ReRegistered], true),
        ]);
    }
}
