import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { formatRelative } from '@/lib/format';
import { cn } from '@/lib/utils';
import {
    ConditionStatusBadge,
    TypeIcon,
    validRange,
} from '@/Modules/ChildCondition/Components/ConditionBits';
import type { ConditionRow } from '@/Modules/ChildCondition/types';

type Props = {
    href: string;
    condition: ConditionRow;
    /** Staf: tampilkan pelapor & sorot laporan baru. */
    staff?: boolean;
};

export function ConditionListItem({ href, condition, staff = false }: Props) {
    const highlight = staff && condition.is_new;

    return (
        <Link
            href={href}
            className={cn(
                'flex items-start gap-3 px-4 py-3.5 transition-colors duration-150 hover:bg-slate-50',
                highlight && 'bg-amber-50/60 hover:bg-amber-50',
            )}
        >
            <TypeIcon type={condition.type.value} />
            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <p
                        className={cn(
                            'text-sm text-slate-900',
                            highlight ? 'font-semibold' : 'font-medium',
                        )}
                    >
                        {condition.student.name}
                    </p>
                    <span className="text-xs text-slate-400">·</span>
                    <p className="text-sm text-slate-600">
                        {condition.type.label}
                    </p>
                    <ConditionStatusBadge status={condition.status} />
                </div>
                <p className="mt-0.5 line-clamp-2 text-sm text-slate-500">
                    {condition.description}
                </p>
                <p className="mt-1 text-xs text-slate-400">
                    Berlaku {validRange(condition)} ·{' '}
                    {staff
                        ? `dari ${condition.reporter ?? 'orang tua'}`
                        : `untuk ${condition.target_teacher ?? 'guru'}`}{' '}
                    · {formatRelative(condition.created_at)}
                </p>
            </div>
            <ChevronRight className="mt-2 size-4 shrink-0 text-slate-300" />
        </Link>
    );
}
