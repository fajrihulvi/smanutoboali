<?php

namespace App\Modules\Core\Services;

use App\Models\User;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Models\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\Mime\MimeTypes;

/**
 * Penyimpanan file terpusat (SKOOLIA_SYSTEM_PLANNING §15).
 *
 * - MIME diperiksa dari ISI file (finfo), bukan ekstensi/header klien.
 * - Nama file acak (ULID); nama asli hanya disimpan di database.
 * - Gambar JPEG/PNG/WebP lebih besar dari 1600px diperkecil & dikompres.
 */
class FileService
{
    /** @var list<string> */
    public const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /** @var list<string> */
    public const DOCUMENT_MIMES = ['application/pdf'];

    /** Dokumen Office (OOXML + format lama) untuk materi pembelajaran. @var list<string> */
    public const OFFICE_MIMES = [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/msword',
        'application/vnd.ms-powerpoint',
        'application/vnd.ms-excel',
    ];

    /** Gambar yang bisa diproses ulang (GIF/SVG tidak). */
    private const RESIZABLE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(private SettingsService $settings) {}

    /**
     * @param  list<string>  $allowedMimes
     *
     * @throws ValidationException
     */
    public function store(
        UploadedFile $upload,
        FileDirectory $directory,
        FileVisibility $visibility = FileVisibility::Private,
        ?Model $fileable = null,
        array $allowedMimes = [...self::IMAGE_MIMES, ...self::DOCUMENT_MIMES],
        ?int $maxKb = null,
        ?User $uploader = null,
        string $field = 'file',
    ): File {
        $maxKb ??= $this->settings->uploadMaxKb();

        if (! $upload->isValid()) {
            throw ValidationException::withMessages([$field => 'File gagal diunggah. Silakan coba lagi.']);
        }

        $mime = $this->detectMime($upload);

        if (! in_array($mime, $allowedMimes, true)) {
            throw ValidationException::withMessages([$field => 'Tipe file tidak diizinkan.']);
        }

        if ((int) $upload->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                $field => 'Ukuran file maksimal '.number_format($maxKb / 1024, 1, ',', '.').' MB.',
            ]);
        }

        $extension = MimeTypes::getDefault()->getExtensions($mime)[0] ?? 'bin';
        $storedName = Str::lower((string) Str::ulid()).'.'.$extension;
        $path = $directory->value.'/'.now()->format('Y/m').'/'.$storedName;
        $disk = Storage::disk($visibility->disk());

        [$contents, $width, $height] = $this->prepareContents($upload, $mime);

        $disk->put($path, $contents, ['visibility' => $visibility->value]);

        $file = new File([
            'disk' => $visibility->disk(),
            'visibility' => $visibility,
            'directory' => $directory,
            'original_name' => Str::limit($upload->getClientOriginalName(), 250, ''),
            'stored_name' => $storedName,
            'path' => $path,
            'mime_type' => $mime,
            'size' => strlen($contents),
            'width' => $width,
            'height' => $height,
            'uploaded_by' => ($uploader ?? auth()->user())?->getKey(),
        ]);

        if ($fileable) {
            $file->fileable()->associate($fileable);
        }

        $file->save();

        return $file;
    }

    /**
     * Hapus file (soft delete). File fisik dibersihkan oleh model:prune.
     */
    public function delete(File $file): void
    {
        $file->delete();
    }

    /**
     * MIME dari ISI file (magic bytes via finfo), tidak pernah dari nama/ekstensi
     * atau header Content-Type yang dikirim klien.
     */
    private function detectMime(UploadedFile $upload): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return (string) $finfo->file($upload->getRealPath());
    }

    /**
     * @return array{0: string, 1: int|null, 2: int|null}
     */
    private function prepareContents(UploadedFile $upload, string $mime): array
    {
        $raw = (string) file_get_contents($upload->getRealPath());

        if (! in_array($mime, self::RESIZABLE_MIMES, true)) {
            return [$raw, null, null];
        }

        $manager = new ImageManager(new Driver);
        $image = $manager->read($raw);
        $max = (int) config('skoolia.upload.image_max_dimension', 1600);

        if ($image->width() <= $max && $image->height() <= $max) {
            return [$raw, $image->width(), $image->height()];
        }

        $image->scaleDown($max, $max);
        $encoded = (string) $image->encodeByMediaType($mime, quality: (int) config('skoolia.upload.image_quality', 80));

        return [$encoded, $image->width(), $image->height()];
    }
}
