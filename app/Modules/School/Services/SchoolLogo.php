<?php

namespace App\Modules\School\Services;

use App\Modules\Core\Models\File;
use Illuminate\Support\Facades\Storage;

/**
 * Logo sekolah untuk tampilan & kop PDF. Logo unggahan (Pengaturan Sekolah) diutamakan;
 * bila belum ada dipakai logo bawaan `public/images/logo.png`.
 */
class SchoolLogo
{
    public const DEFAULT_PATH = 'images/logo.png';

    public static function defaultUrl(): string
    {
        return asset(self::DEFAULT_PATH);
    }

    /**
     * Data URI untuk dompdf (tanpa request HTTP, aman untuk file privat).
     */
    public static function dataUri(?File $logo): ?string
    {
        if ($logo !== null && $logo->isImage()) {
            $contents = Storage::disk($logo->disk)->get($logo->path);

            if ($contents !== null) {
                return 'data:'.$logo->mime_type.';base64,'.base64_encode($contents);
            }
        }

        $default = public_path(self::DEFAULT_PATH);

        return is_file($default) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($default)) : null;
    }
}
