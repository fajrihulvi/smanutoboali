import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { formatDate } from '@/lib/format';
import { shiftMonth } from '@/Modules/Attendance/lib/status';

type Props = {
    month: string;
    /** Query lain yang dipertahankan saat berganti bulan (mis. anak). */
    query?: Record<string, string | undefined>;
};

/**
 * Navigasi bulan (‹ September 2026 ›) lewat query `?bulan=Y-m`.
 */
export function MonthPicker({ month, query = {} }: Props) {
    const go = (delta: number) =>
        router.get(
            window.location.pathname,
            { ...query, bulan: shiftMonth(month, delta) },
            { preserveScroll: true },
        );

    return (
        <div className="flex items-center gap-1">
            <Button
                variant="outline"
                size="icon"
                className="size-9"
                onClick={() => go(-1)}
                aria-label="Bulan sebelumnya"
            >
                <ChevronLeft className="size-4" />
            </Button>
            <p className="min-w-36 text-center text-sm font-semibold text-slate-900 capitalize">
                {formatDate(`${month}-01`, 'MMMM yyyy')}
            </p>
            <Button
                variant="outline"
                size="icon"
                className="size-9"
                onClick={() => go(1)}
                aria-label="Bulan berikutnya"
            >
                <ChevronRight className="size-4" />
            </Button>
        </div>
    );
}
