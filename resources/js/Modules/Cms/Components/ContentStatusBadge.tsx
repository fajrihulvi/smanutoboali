import { StatusBadge } from '@/Components/shared/StatusBadge';
import { STATUS_VARIANTS } from '@/Modules/Cms/types';
import type { ContentStatus } from '@/Modules/Cms/types';

export function ContentStatusBadge({ status }: { status: ContentStatus }) {
    return (
        <StatusBadge
            variant={status.scheduled ? 'info' : STATUS_VARIANTS[status.value]}
            dot
        >
            {status.label}
        </StatusBadge>
    );
}
