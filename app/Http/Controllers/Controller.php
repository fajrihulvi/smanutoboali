<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Setelah menghapus: kembali ke halaman daftar sebelumnya (paginasi & filter tetap)
     * bila penghapusan dilakukan dari daftar; selain itu (mis. dari halaman ubah) ke $fallback.
     */
    protected function backToIndex(string $indexRoute, RedirectResponse $fallback): RedirectResponse
    {
        $index = route($indexRoute);
        $previous = url()->previous();
        $rest = str_starts_with($previous, $index) ? substr($previous, strlen($index)) : null;

        return $rest !== null && ($rest === '' || str_starts_with($rest, '?'))
            ? redirect()->to($previous)
            : $fallback;
    }
}
