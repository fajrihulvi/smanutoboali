import { Head, Link } from '@inertiajs/react';
import { Baby, ChevronRight, Wallet } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { Pagination } from '@/Components/shared/Pagination';
import ParentLayout from '@/Layouts/ParentLayout';
import { formatCurrency, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { InvoiceStatusBadge } from '@/Modules/Finance/Components/InvoiceStatusBadge';
import type { InvoiceRow } from '@/Modules/Finance/types';
import type { Paginated } from '@/types';

type Props = {
    child: { public_id: string; name: string } | null;
    tab: 'belum' | 'lunas';
    invoices: Paginated<InvoiceRow> | null;
    outstanding: { amount: number; count: number };
};

const TABS = [
    { value: 'belum', label: 'Belum lunas' },
    { value: 'lunas', label: 'Lunas' },
] as const;

export default function GuardianInvoices({
    child,
    tab,
    invoices,
    outstanding,
}: Props) {
    return (
        <ParentLayout>
            <Head title="Tagihan" />
            <div>
                <h1 className="text-xl font-semibold text-slate-900">
                    Tagihan
                </h1>
                <p className="text-sm text-slate-500">
                    {child
                        ? `Tagihan sekolah ${child.name}.`
                        : 'Tagihan sekolah anak.'}
                </p>
            </div>

            {!child ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState icon={Baby} title="Belum ada anak terhubung" />
                </div>
            ) : (
                <>
                    <section className="rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 p-5 text-white shadow-sm">
                        <p className="text-sm text-white/80">
                            Total belum dibayar
                        </p>
                        <p className="mt-1 text-2xl font-semibold tabular-nums">
                            {formatCurrency(outstanding.amount)}
                        </p>
                        <p className="text-sm text-white/80">
                            {outstanding.count} tagihan
                        </p>
                    </section>

                    <div
                        role="tablist"
                        className="grid grid-cols-2 gap-1 rounded-lg bg-slate-100 p-1"
                    >
                        {TABS.map((item) => (
                            <Link
                                key={item.value}
                                href={
                                    item.value === 'belum'
                                        ? '/orang-tua/keuangan'
                                        : '/orang-tua/keuangan?tab=lunas'
                                }
                                role="tab"
                                aria-selected={tab === item.value}
                                preserveScroll
                                className={cn(
                                    'rounded-md px-3 py-1.5 text-center text-sm font-medium transition-colors duration-150',
                                    tab === item.value
                                        ? 'bg-white text-slate-900 shadow-sm'
                                        : 'text-slate-500 hover:text-slate-700',
                                )}
                            >
                                {item.label}
                            </Link>
                        ))}
                    </div>

                    {!invoices || invoices.data.length === 0 ? (
                        <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <EmptyState
                                icon={Wallet}
                                title={
                                    tab === 'lunas'
                                        ? 'Belum ada tagihan lunas'
                                        : 'Tidak ada tagihan'
                                }
                                description={
                                    tab === 'lunas'
                                        ? 'Tagihan yang sudah lunas tampil di sini.'
                                        : 'Semua tagihan sudah lunas. Terima kasih!'
                                }
                            />
                        </div>
                    ) : (
                        <ul className="space-y-3">
                            {invoices.data.map((invoice) => (
                                <li key={invoice.public_id}>
                                    <Link
                                        href={`/orang-tua/keuangan/${invoice.public_id}`}
                                        className="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-colors duration-150 hover:border-brand-300"
                                    >
                                        <div className="min-w-0 flex-1 space-y-1">
                                            <p className="font-medium text-slate-900">
                                                {invoice.title}
                                            </p>
                                            <p className="text-xs text-slate-500">
                                                {invoice.status.value === 'PAID'
                                                    ? 'Lunas'
                                                    : `Jatuh tempo ${formatDate(invoice.due_date)}`}{' '}
                                                · {invoice.number}
                                            </p>
                                            <InvoiceStatusBadge
                                                invoice={invoice}
                                            />
                                        </div>
                                        <div className="shrink-0 text-right">
                                            <p className="font-semibold text-slate-900 tabular-nums">
                                                {formatCurrency(
                                                    invoice.status.value ===
                                                        'PAID'
                                                        ? invoice.total
                                                        : invoice.remaining,
                                                )}
                                            </p>
                                            {invoice.status.value !== 'PAID' &&
                                                invoice.remaining !==
                                                    invoice.total && (
                                                    <p className="text-xs text-slate-500">
                                                        dari{' '}
                                                        {formatCurrency(
                                                            invoice.total,
                                                        )}
                                                    </p>
                                                )}
                                        </div>
                                        <ChevronRight className="size-4 shrink-0 text-slate-300" />
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                    {invoices && <Pagination paginated={invoices} />}
                </>
            )}
        </ParentLayout>
    );
}
