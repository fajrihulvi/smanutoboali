import { Link, usePage } from '@inertiajs/react';
import { CalendarClock, TriangleAlert } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Badge periode akademik aktif di topbar. Bila belum diatur, operator
 * mendapat tautan ke Master Data › Tahun Ajaran.
 */
export function ActivePeriodBadge({ className }: { className?: string }) {
    const { activePeriod, auth } = usePage().props;

    if (!activePeriod) {
        if (auth.activeRole !== 'operator') {
            return null;
        }

        return (
            <Link
                href="/operator/master-data/tahun-ajaran"
                className={cn(
                    'inline-flex h-8 items-center gap-1.5 rounded-full bg-amber-100 px-3 text-xs font-medium text-amber-700 transition-colors duration-150 hover:bg-amber-200',
                    className,
                )}
            >
                <TriangleAlert className="size-3.5" />
                Periode belum diatur
            </Link>
        );
    }

    return (
        <span
            className={cn(
                'inline-flex h-8 items-center gap-1.5 rounded-full bg-slate-100 px-3 text-xs font-medium text-slate-700 tabular-nums',
                className,
            )}
            title="Periode akademik aktif"
        >
            <CalendarClock className="size-3.5 text-brand-600" />
            <span>TA {activePeriod.academic_year.name}</span>
            {activePeriod.semester && (
                <span className="text-slate-500">
                    · {activePeriod.semester.label}
                </span>
            )}
        </span>
    );
}
