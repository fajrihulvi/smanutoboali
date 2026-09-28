import { Head, Link } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Pagination } from '@/Components/shared/Pagination';
import AppLayout from '@/Layouts/AppLayout';
import { formatCurrency, formatRelative } from '@/lib/format';
import { PaymentList } from '@/Modules/Finance/Components/PaymentList';
import { ReviewButtons } from '@/Modules/Finance/Components/ReviewButtons';
import type { PaymentRow } from '@/Modules/Finance/types';
import type { Paginated } from '@/types';

const BASE = '/operator/keuangan';

export default function Verification({
    payments,
}: {
    payments: Paginated<PaymentRow>;
}) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Keuangan', href: `${BASE}/tagihan` },
                { title: 'Verifikasi', href: `${BASE}/verifikasi` },
            ]}
        >
            <Head title="Verifikasi pembayaran" />
            <PageHeader
                title="Verifikasi bukti transfer"
                description="Bukti transfer dari orang tua, terlama di atas. Terima untuk mengonfirmasi pembayaran dan menerbitkan kwitansi."
            />

            {payments.data.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={ShieldCheck}
                        title="Tidak ada yang perlu diverifikasi"
                        description="Bukti transfer baru dari orang tua akan tampil di sini."
                    />
                </div>
            ) : (
                <div className="space-y-3">
                    {payments.data.map((payment) => (
                        <section
                            key={payment.public_id}
                            className="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            {payment.invoice && (
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div className="min-w-0">
                                        <Link
                                            href={`${BASE}/tagihan/${payment.invoice.public_id}`}
                                            className="font-semibold text-slate-900 hover:text-brand-700"
                                        >
                                            {payment.invoice.student.name}
                                        </Link>
                                        <p className="text-sm text-slate-500">
                                            {payment.invoice.title} ·{' '}
                                            {payment.invoice.number} · sisa{' '}
                                            {formatCurrency(
                                                payment.invoice.remaining,
                                            )}
                                        </p>
                                    </div>
                                    <span className="text-xs text-slate-400">
                                        dikirim{' '}
                                        {formatRelative(payment.created_at)}
                                    </span>
                                </div>
                            )}
                            <PaymentList
                                payments={[payment]}
                                receiptUrl={(p) =>
                                    `${BASE}/pembayaran/${p.public_id}/kwitansi`
                                }
                                actions={(p) => <ReviewButtons payment={p} />}
                            />
                        </section>
                    ))}
                    <Pagination paginated={payments} />
                </div>
            )}
        </AppLayout>
    );
}
