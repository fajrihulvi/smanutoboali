<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;

/**
 * Filter tanggal yang bisa memakai indeks (sargable).
 *
 * `whereDate()` di MySQL menjadi `DATE(kolom) >= ?` sehingga indeks pada kolom tanggal
 * tidak terpakai. Macro ini memakai rentang setengah terbuka: `kolom >= awal AND kolom < akhir+1 hari`.
 * Cocok untuk kolom DATE maupun DATETIME, dan untuk SQLite (tanggal disimpan 'Y-m-d H:i:s').
 */
class QueryMacros
{
    public static function register(): void
    {
        Builder::macro('whereDateBetween', function (string $column, CarbonInterface|string $from, CarbonInterface|string $to) {
            /** @var Builder $this */
            return $this->where($column, '>=', CarbonImmutable::parse($from)->toDateString())
                ->where($column, '<', CarbonImmutable::parse($to)->addDay()->toDateString());
        });

        Builder::macro('whereOnDate', function (string $column, CarbonInterface|string $date) {
            /** @var Builder $this */
            return $this->whereDateBetween($column, $date, $date);
        });
    }
}
