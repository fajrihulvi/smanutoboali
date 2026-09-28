import {
    Apple,
    Brain,
    CalendarX,
    HeartPulse,
    MessageCircleMore,
    Thermometer,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { formatDate } from '@/lib/format';
import type {
    ConditionRow,
    ConditionStatusValue,
    ConditionTypeValue,
} from '@/Modules/ChildCondition/types';
import type { StatusVariant } from '@/types';

export const TYPE_ICONS: Record<ConditionTypeValue, LucideIcon> = {
    SAKIT: Thermometer,
    KELUHAN_KESEHATAN: HeartPulse,
    EMOSIONAL: Brain,
    IZIN_KHUSUS: CalendarX,
    ALERGI_MAKANAN: Apple,
    LAINNYA: MessageCircleMore,
};

const STATUS_VARIANTS: Record<ConditionStatusValue, StatusVariant> = {
    SUBMITTED: 'warning',
    RECEIVED: 'info',
    REVIEWED: 'brand',
    RESPONDED: 'success',
    CLOSED: 'neutral',
};

export function ConditionStatusBadge({
    status,
}: {
    status: ConditionRow['status'];
}) {
    return (
        <StatusBadge variant={STATUS_VARIANTS[status.value]} dot>
            {status.label}
        </StatusBadge>
    );
}

export function TypeIcon({
    type,
    className,
}: {
    type: ConditionTypeValue;
    className?: string;
}) {
    const Icon = TYPE_ICONS[type];

    return (
        <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
            <Icon className={className ?? 'size-5'} />
        </span>
    );
}

/** "28 Sep 2026" atau "28 Sep – 30 Sep 2026". */
export function validRange(
    row: Pick<ConditionRow, 'valid_from' | 'valid_until'>,
): string {
    return row.valid_until && row.valid_until !== row.valid_from
        ? `${formatDate(row.valid_from, 'd MMM')} – ${formatDate(row.valid_until)}`
        : formatDate(row.valid_from);
}
