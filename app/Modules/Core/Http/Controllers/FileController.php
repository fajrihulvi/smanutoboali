<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    /**
     * GET /files/{file} — stream file setelah FilePolicy. ?download=1 untuk unduh.
     */
    public function show(Request $request, File $file): StreamedResponse
    {
        $this->authorize('view', $file);

        return $this->stream($file, $request->boolean('download'), private: true);
    }

    /**
     * GET /media/{file} — fallback file publik bila storage:link tidak tersedia.
     */
    public function media(File $file): StreamedResponse
    {
        abort_unless($file->visibility === FileVisibility::Public, 404);

        return $this->stream($file, false, private: false);
    }

    private function stream(File $file, bool $download, bool $private): StreamedResponse
    {
        $disk = Storage::disk($file->disk);

        abort_unless($disk->exists($file->path), 404);

        $headers = [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $private ? 'private, max-age=0, no-store' : 'public, max-age=604800',
        ];

        return $disk->response(
            $file->path,
            $file->original_name,
            $headers,
            $download ? 'attachment' : 'inline',
        );
    }
}
