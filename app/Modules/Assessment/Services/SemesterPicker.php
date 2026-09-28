<?php

namespace App\Modules\Assessment\Services;

use App\Modules\School\Models\Semester;
use App\Support\ActivePeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Semester tahun ajaran aktif untuk halaman nilai: ?semester=<public_id>, default semester aktif.
 */
class SemesterPicker
{
    public function __construct(private ActivePeriod $period) {}

    /**
     * @return Collection<int, Semester>
     */
    public function options(): Collection
    {
        return Semester::query()->where('academic_year_id', $this->period->academicYearId() ?? 0)->orderBy('start_date')->get();
    }

    public function selected(Request $request): ?Semester
    {
        $options = $this->options();
        $publicId = $request->string('semester')->value();

        return $options->firstWhere('public_id', $publicId)
            ?? $options->firstWhere('id', $this->period->semesterId())
            ?? $options->first();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function toOptions(): array
    {
        return $this->options()->map(fn (Semester $s) => ['value' => $s->public_id, 'label' => $s->label()])->values()->all();
    }
}
