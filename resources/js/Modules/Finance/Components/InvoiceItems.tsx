import { formatCurrency } from '@/lib/format';
import type { InvoiceDetail } from '@/Modules/Finance/types';

/**
 * Rincian tagihan: baris biaya, potongan, total, terbayar, sisa.
 */
export function InvoiceItems({ invoice }: { invoice: InvoiceDetail }) {
    return (
        <dl className="space-y-2 text-sm">
            {invoice.items.map((item, index) => (
                <div key={index} className="flex justify-between gap-4">
                    <dt className="min-w-0 text-slate-600">
                        {item.description}
                    </dt>
                    <dd
                        className={
                            item.amount < 0
                                ? 'shrink-0 text-emerald-700 tabular-nums'
                                : 'shrink-0 text-slate-900 tabular-nums'
                        }
                    >
                        {formatCurrency(item.amount)}
                    </dd>
                </div>
            ))}
            <div className="flex justify-between gap-4 border-t border-slate-200 pt-2 font-semibold">
                <dt>Total</dt>
                <dd className="tabular-nums">
                    {formatCurrency(invoice.total)}
                </dd>
            </div>
            <div className="flex justify-between gap-4 text-slate-600">
                <dt>Terbayar</dt>
                <dd className="tabular-nums">
                    {formatCurrency(invoice.paid_amount)}
                </dd>
            </div>
            <div className="flex justify-between gap-4 text-base font-semibold text-brand-700">
                <dt>Sisa</dt>
                <dd className="tabular-nums">
                    {formatCurrency(invoice.remaining)}
                </dd>
            </div>
        </dl>
    );
}
