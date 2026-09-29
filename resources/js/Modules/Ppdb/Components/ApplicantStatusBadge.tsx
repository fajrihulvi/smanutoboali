import { StatusBadge } from '@/Components/shared/StatusBadge';
import type { ApplicantRow } from '@/Modules/Ppdb/types';
import type { StatusVariant } from '@/types';

const VARIANT: Record<ApplicantRow['status']['value'], StatusVariant> = {
    DRAFT: 'neutral',
    SUBMITTED: 'info',
    NEEDS_REVISION: 'warning',
    VERIFIED: 'brand',
    IN_SELECTION: 'brand',
    ACCEPTED: 'success',
    REJECTED: 'danger',
    RE_REGISTERED: 'success',
};

export function ApplicantStatusBadge({
    status,
}: {
    status: ApplicantRow['status'];
}) {
    return (
        <StatusBadge variant={VARIANT[status.value]} dot>
            {status.label}
        </StatusBadge>
    );
}
