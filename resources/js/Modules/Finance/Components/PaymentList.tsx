import { Download, FileText } from 'lucide-react';
import type { ReactNode } from 'react';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { formatCurrency, formatDate } from '@/lib/format';
import type { PaymentRow } from '@/Modules/Finance/types';

type Props = {
    payments: PaymentRow[];
    /** URL kwitansi per pembayaran terkonfirmasi. */
    receiptUrl: (payment: PaymentRow) => string;
    /** Aksi tambahan per pembayaran (mis. verifikasi). */
    actions?: (payment: PaymentRow) => ReactNode;
};

const VARIANT = {
    PENDING: 'info',
    CONFIRMED: 'success',
    REJECTED: 'danger',
} as const;

export function PaymentList({ payments, receiptUrl, actions }: Props) {
    if (payments.length === 0) {
        return <p className="text-sm text-slate-500">Belum ada pembayaran.</p>;
    }

    return (
        <ul className="divide-y divide-slate-100">
            {payments.map((payment) => (
                <li
                    key={payment.public_id}
                    className="space-y-2 py-3 first:pt-0 last:pb-0"
                >
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <p className="font-semibold text-slate-900 tabular-nums">
                            {formatCurrency(payment.amount)}
                        </p>
                        <StatusBadge
                            variant={VARIANT[payment.status.value]}
                            dot
                        >
                            {payment.status.label}
                        </StatusBadge>
                    </div>
                    <p className="text-xs text-slate-500">
                        {formatDate(payment.paid_on)} · {payment.method.label}
                        {payment.receipt_number &&
                            ` · ${payment.receipt_number}`}
                        {payment.submitted_by &&
                            ` · oleh ${payment.submitted_by}`}
                    </p>
                    {payment.note && (
                        <p className="text-sm text-slate-600">{payment.note}</p>
                    )}
                    {payment.rejection_reason && (
                        <p className="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">
                            Ditolak: {payment.rejection_reason}
                        </p>
                    )}
                    <div className="flex flex-wrap items-center gap-3 text-sm">
                        {payment.proof && (
                            <a
                                href={payment.proof.url}
                                target="_blank"
                                rel="noreferrer"
                                className="flex items-center gap-1 font-medium text-brand-600 hover:text-brand-700"
                            >
                                <FileText className="size-4" />
                                Bukti transfer
                            </a>
                        )}
                        {payment.status.value === 'CONFIRMED' && (
                            <a
                                href={receiptUrl(payment)}
                                className="flex items-center gap-1 font-medium text-brand-600 hover:text-brand-700"
                            >
                                <Download className="size-4" />
                                Kwitansi PDF
                            </a>
                        )}
                        {actions?.(payment)}
                    </div>
                </li>
            ))}
        </ul>
    );
}
