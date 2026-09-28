<?php

namespace App\Modules\Portfolio\Actions;

use App\Models\User;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use App\Modules\Portfolio\Enums\PortfolioVisibility;
use App\Modules\Portfolio\Events\PortfolioPublished;
use App\Modules\Portfolio\Models\Portfolio;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Buat/ubah portofolio: data, siswa terkait (sinkron), file baru, file dihapus, publikasi.
 * DRAF → TERBIT memicu PortfolioPublished (notifikasi orang tua).
 */
class SavePortfolioAction
{
    public function __construct(
        private FileService $files,
        private AuditLogger $audit,
        private ActivePeriod $period,
    ) {}

    /**
     * @param  array{category_id: int, title: string, description: string|null, date: CarbonImmutable, visibility: PortfolioVisibility, student_ids: list<int>}  $data
     * @param  list<UploadedFile>  $uploads
     * @param  list<string>  $removeFiles  public_id file yang dihapus
     *
     * @throws ValidationException
     */
    public function handle(array $data, User $actor, array $uploads = [], array $removeFiles = [], ?Portfolio $portfolio = null): Portfolio
    {
        $isNew = $portfolio === null;
        $wasPublished = $portfolio?->visibility === PortfolioVisibility::Published;

        $portfolio = DB::transaction(function () use ($data, $actor, $uploads, $removeFiles, $portfolio) {
            $portfolio ??= new Portfolio(['created_by' => $actor->id, 'academic_year_id' => $this->period->academicYearId()]);

            $publishing = $data['visibility'] === PortfolioVisibility::Published;
            $portfolio->fill([
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'date' => $data['date']->toDateString(),
                'visibility' => $data['visibility'],
                'published_at' => $publishing ? ($portfolio->published_at ?? now()) : null,
            ])->save();

            $portfolio->students()->sync($data['student_ids']);

            if ($removeFiles !== []) {
                $portfolio->files()->whereIn('public_id', $removeFiles)->get()->each(fn ($file) => $this->files->delete($file));
            }

            if ($portfolio->files()->count() + count($uploads) > Portfolio::MAX_FILES) {
                throw ValidationException::withMessages(['files' => 'Maksimal '.Portfolio::MAX_FILES.' file per portofolio.']);
            }

            foreach ($uploads as $upload) {
                $this->files->store($upload, FileDirectory::Portfolios, FileVisibility::Private, $portfolio, uploader: $actor, field: 'files');
            }

            if ($data['visibility'] === PortfolioVisibility::Published && $portfolio->files()->doesntExist() && blank($data['description'])) {
                throw ValidationException::withMessages(['files' => 'Portofolio terbit perlu minimal satu file atau deskripsi.']);
            }

            return $portfolio;
        });

        $this->audit->record('portfolio', $isNew ? 'created' : 'updated', ($isNew ? 'Menambah' : 'Mengubah')." portofolio \"{$portfolio->title}\"", $portfolio);

        if (! $wasPublished && $portfolio->visibility === PortfolioVisibility::Published) {
            PortfolioPublished::dispatch($portfolio);
        }

        return $portfolio;
    }
}
