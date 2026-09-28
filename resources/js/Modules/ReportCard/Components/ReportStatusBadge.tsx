import { StatusBadge } from '@/Components/shared/StatusBadge';
import type {
    ReportStatus,
    ReportStatusValue,
} from '@/Modules/ReportCard/types';
import type { StatusVariant } from '@/types';

export const REPORT_STATUS_VARIANT: Record<ReportStatusValue, StatusVariant> = {
    DRAFT: 'neutral',
    SUBMITTED: 'info',
    HOMEROOM_REVIEWED: 'brand',
    VALIDATED: 'brand',
    APPROVED: 'success',
    PUBLISHED: 'success',
    REVISION: 'warning',
};

/** Urutan alur untuk tampilan ringkasan. */
export const REPORT_FLOW: ReportStatusValue[] = [
    'DRAFT',
    'REVISION',
    'SUBMITTED',
    'HOMEROOM_REVIEWED',
    'VALIDATED',
    'APPROVED',
    'PUBLISHED',
];

export const REPORT_STATUS_SHORT: Record<ReportStatusValue, string> = {
    DRAFT: 'Draf',
    SUBMITTED: 'Diajukan',
    HOMEROOM_REVIEWED: 'Direview',
    VALIDATED: 'Divalidasi',
    APPROVED: 'Disetujui',
    PUBLISHED: 'Terbit',
    REVISION: 'Revisi',
};

export function ReportStatusBadge({ status }: { status: ReportStatus }) {
    return (
        <StatusBadge variant={REPORT_STATUS_VARIANT[status.value]} dot>
            {status.label}
        </StatusBadge>
    );
}
