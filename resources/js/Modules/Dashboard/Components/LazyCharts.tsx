import { lazy, Suspense } from 'react';
import type { ComponentProps } from 'react';
import { Skeleton } from '@/Components/ui/skeleton';

/*
 * Recharts (±100 kB gzip) dimuat terpisah: kartu KPI tampil lebih dulu,
 * grafik menyusul tanpa mengubah tinggi layout.
 */

const TrendChart = lazy(() =>
    import('@/Modules/Dashboard/Components/AttendanceTrendChart').then((m) => ({
        default: m.AttendanceTrendChart,
    })),
);

const GradeChart = lazy(() =>
    import('@/Modules/Dashboard/Components/GradeDistributionChart').then(
        (m) => ({ default: m.GradeDistributionChart }),
    ),
);

function ChartFallback({ height }: { height: string }) {
    return (
        <div aria-busy="true" aria-label="Memuat grafik">
            <Skeleton className="mb-3 h-4 w-48" />
            <Skeleton className={height} />
        </div>
    );
}

export function LazyAttendanceTrendChart(
    props: ComponentProps<typeof TrendChart>,
) {
    return (
        <Suspense fallback={<ChartFallback height="h-60" />}>
            <TrendChart {...props} />
        </Suspense>
    );
}

export function LazyGradeDistributionChart(
    props: ComponentProps<typeof GradeChart>,
) {
    return (
        <Suspense fallback={<ChartFallback height="h-48" />}>
            <GradeChart {...props} />
        </Suspense>
    );
}
