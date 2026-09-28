import { StatusBadge } from '@/Components/shared/StatusBadge';
import type { InvoiceRow } from '@/Modules/Finance/types';
import type { StatusVariant } from '@/types';

const VARIANTS: Record<InvoiceRow['status']['value'], StatusVariant> = {
    UNPAID: 'warning',
    PENDING: 'info',
    PAID: 'success',
    FAILED: 'danger',
    CANCELLED: 'neutral',
};

/**
 * Status tagihan; tagihan belum lunas yang sudah dibayar sebagian ditandai "sebagian",
 * yang lewat jatuh tempo ditandai merah.
 */
export function InvoiceStatusBadge({
    invoice,
}: {
    invoice: Pick<InvoiceRow, 'status' | 'paid_amount' | 'overdue'>;
}) {
    const partial =
        invoice.status.value === 'UNPAID' && invoice.paid_amount > 0;
    const variant =
        invoice.overdue && invoice.status.value === 'UNPAID'
            ? 'danger'
            : VARIANTS[invoice.status.value];

    return (
        <StatusBadge variant={variant} dot>
            {partial ? 'Terbayar sebagian' : invoice.status.label}
            {invoice.overdue && invoice.status.value !== 'PAID'
                ? ' · terlambat'
                : ''}
        </StatusBadge>
    );
}
