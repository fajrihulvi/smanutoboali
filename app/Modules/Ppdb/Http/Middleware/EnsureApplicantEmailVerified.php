<?php

namespace App\Modules\Ppdb\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Portal PPDB hanya untuk akun yang emailnya sudah diverifikasi.
 */
class EnsureApplicantEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->email_verified_at === null) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Verifikasi email Anda terlebih dahulu.'], 403)
                : redirect()->route('applicant.verification.notice');
        }

        return $next($request);
    }
}
