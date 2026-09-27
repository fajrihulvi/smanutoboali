<?php

namespace App\Support;

/**
 * Padanan PHP dari resources/js/lib/resolve-page.ts: memetakan nama halaman
 * Inertia ke file sumbernya (dipakai @vite di app.blade.php untuk preload).
 */
class InertiaPage
{
    public static function sourcePath(string $component): string
    {
        if (! str_contains($component, '::')) {
            return "resources/js/pages/{$component}.tsx";
        }

        [$module, $path] = explode('::', $component, 2);

        return "resources/js/Modules/{$module}/Pages/{$path}.tsx";
    }
}
