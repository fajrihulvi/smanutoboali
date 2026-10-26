<?php

namespace App\Modules\Report\Reports;

use Illuminate\Contracts\Container\Container;

/**
 * Daftar semua jenis laporan (urutan = urutan tampil di pusat laporan).
 */
class ReportRegistry
{
    /** @var list<class-string<Report>> */
    private const REPORTS = [
        StudentAttendanceReport::class,
        TeacherAttendanceReport::class,
        GradesReport::class,
        ReportCardsReport::class,
        ReceiptsReport::class,
        OutstandingReport::class,
        PpdbReport::class,
        GuestBookReport::class,
    ];

    public function __construct(private Container $container) {}

    /**
     * @return list<Report>
     */
    public function available(ReportContext $context): array
    {
        return array_values(array_filter(
            array_map(fn (string $class) => $this->container->make($class), self::REPORTS),
            fn (Report $report) => $report->allows($context),
        ));
    }

    public function find(ReportContext $context, string $key): ?Report
    {
        foreach ($this->available($context) as $report) {
            if ($report->key() === $key) {
                return $report;
            }
        }

        return null;
    }
}
