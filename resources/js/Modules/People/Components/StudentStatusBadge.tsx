import { StatusBadge } from '@/Components/shared/StatusBadge';
import type { Option } from '@/Modules/People/types';
import type { StatusVariant } from '@/types';

const variants: Record<string, StatusVariant> = {
    AKTIF: 'success',
    LULUS: 'brand',
    PINDAH: 'info',
    KELUAR: 'neutral',
};

export function StudentStatusBadge({ status }: { status: Option }) {
    return (
        <StatusBadge variant={variants[status.value] ?? 'neutral'} dot>
            {status.label}
        </StatusBadge>
    );
}
