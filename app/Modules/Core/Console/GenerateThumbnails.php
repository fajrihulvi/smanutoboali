<?php

namespace App\Modules\Core\Console;

use App\Modules\Core\Models\File;
use App\Modules\Core\Services\FileService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Buat thumbnail untuk gambar lama yang belum punya (sekali jalan setelah rilis S33).
 * Diproses per 100 file agar aman di shared hosting.
 */
class GenerateThumbnails extends Command
{
    protected $signature = 'skoolia:thumbnails {--limit=0 : Batasi jumlah file (0 = semua)}';

    protected $description = 'Buat thumbnail untuk gambar yang belum memilikinya';

    public function handle(FileService $files): int
    {
        $made = 0;
        $checked = 0;
        $limit = (int) $this->option('limit');

        File::query()
            ->whereIn('mime_type', ['image/jpeg', 'image/png', 'image/webp'])
            ->whereNull('thumbnail_path')
            ->where(fn ($q) => $q->where('width', '>', config('skoolia.upload.thumbnail_dimension', 480))
                ->orWhere('height', '>', config('skoolia.upload.thumbnail_dimension', 480))
                ->orWhereNull('width'))
            ->orderBy('id')
            ->chunkById(100, function ($chunk) use ($files, &$made, &$checked, $limit) {
                foreach ($chunk as $file) {
                    if ($limit > 0 && $checked >= $limit) {
                        return false;
                    }

                    $checked++;

                    try {
                        $made += $files->makeThumbnail($file) !== null ? 1 : 0;
                    } catch (Throwable $e) {
                        $this->warn("Lewati {$file->public_id}: {$e->getMessage()}");
                    }
                }

                return true;
            });

        $this->info("Diperiksa {$checked} gambar, {$made} thumbnail dibuat.");

        return self::SUCCESS;
    }
}
