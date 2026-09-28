<?php

namespace App\Modules\Core\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class AuditLogQueryService
{
    public const PER_PAGE = 20;

    /**
     * Label modul (log_name). Modul baru cukup menambah baris di sini.
     *
     * @var array<string, string>
     */
    public const MODULE_LABELS = [
        'auth' => 'Autentikasi',
        'access' => 'Akses & Pengguna',
        'settings' => 'Pengaturan',
        'school' => 'Sekolah & Periode',
        'people' => 'Guru, Orang Tua & Siswa',
        'academic' => 'Akademik',
        'attendance' => 'Presensi',
        'assessment' => 'Penilaian',
        'report_card' => 'Raport',
        'portfolio' => 'Portofolio',
        'activity' => 'Kegiatan',
        'child_condition' => 'Kondisi Anak',
        'announcement' => 'Pengumuman',
    ];

    /**
     * @param  array{module?: string|null, user?: string|null, from?: string|null, to?: string|null}  $filters
     * @return LengthAwarePaginator<int, Activity>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Activity::query()
            ->with(['causer' => fn ($query) => $query->select('id', 'public_id', 'name', 'email')])
            ->when($filters['module'] ?? null, fn (Builder $query, string $module) => $query->where('log_name', $module))
            ->when($filters['user'] ?? null, function (Builder $query, string $search) {
                $query->whereHasMorph('causer', [User::class], fn (Builder $q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($this->date($filters['from'] ?? null), fn (Builder $query, CarbonImmutable $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($this->date($filters['to'] ?? null), fn (Builder $query, CarbonImmutable $to) => $query->where('created_at', '<=', $to->endOfDay()))
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Pilihan filter modul: label bawaan + log_name yang pernah tercatat.
     *
     * @return list<array{value: string, label: string}>
     */
    public function moduleOptions(): array
    {
        $names = Activity::query()->distinct()->pluck('log_name')->filter()->merge(array_keys(self::MODULE_LABELS))->unique()->sort();

        return $names->map(fn (string $name) => ['value' => $name, 'label' => self::label($name)])->values()->all();
    }

    public static function label(?string $module): string
    {
        return self::MODULE_LABELS[$module] ?? ucfirst((string) $module);
    }

    private function date(?string $value): ?CarbonImmutable
    {
        if (! $value || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d', $value) ?: null;
    }
}
