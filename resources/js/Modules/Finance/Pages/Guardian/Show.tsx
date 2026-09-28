import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Clock } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/Components/ui/button';
import ParentLayout from '@/Layouts/ParentLayout';
import { formatDate } from '@/lib/format';
import { InvoiceItems } from '@/Modules/Finance/Components/InvoiceItems';
import { InvoiceStatusBadge } from '@/Modules/Finance/Components/InvoiceStatusBadge';
import { PaymentForm } from '@/Modules/Finance/Components/PaymentForm';
import { PaymentList } from '@/Modules/Finance/Components/PaymentList';
import type { InvoiceDetail } from '@/Modules/Finance/types';

type Props = {
    invoice: InvoiceDetail;
    today: string;
    instructions: string | null;
};

function Card({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h2 className="text-sm font-semibold text-slate-900">{title}</h2>
            {children}
        </section>
    );
}

export default function GuardianInvoiceShow({
    invoice,
    today,
    instructions,
}: Props) {
    const pending = invoice.payments.some((p) => p.status.value === 'PENDING');

    return (
        <ParentLayout>
            <Head title={invoice.title} />
            <Button
                variant="ghost"
                size="sm"
                className="-ml-2 self-start"
                asChild
            >
                <Link href="/orang-tua/keuangan">
                    <ArrowLeft />
                    Semua tagihan
                </Link>
            </Button>

            <div className="grid gap-5 lg:grid-cols-5">
                <div className="min-w-0 space-y-5 lg:col-span-3">
                    <Card title={invoice.title}>
                        <div className="flex flex-wrap items-center gap-2 text-sm text-slate-600">
                            <InvoiceStatusBadge invoice={invoice} />
                            <span>
                                {invoice.student.name} · jatuh tempo{' '}
                                {formatDate(invoice.due_date)}
                            </span>
                        </div>
                        <InvoiceItems invoice={invoice} />
                        <p className="font-mono text-xs text-slate-400">
                            {invoice.number}
                        </p>
                    </Card>
                    <Card title="Riwayat pembayaran">
                        <PaymentList
                            payments={invoice.payments}
                            receiptUrl={(p) =>
                                `/orang-tua/keuangan/pembayaran/${p.public_id}/kwitansi`
                            }
                        />
                    </Card>
                </div>
                <aside className="min-w-0 lg:col-span-2">
                    {pending ? (
                        <Card title="Menunggu verifikasi">
                            <p className="flex items-start gap-2 text-sm text-slate-600">
                                <Clock className="mt-0.5 size-4 shrink-0 text-sky-600" />
                                Bukti transfer Anda sedang diperiksa sekolah.
                                Anda akan mendapat notifikasi setelah
                                diverifikasi.
                            </p>
                        </Card>
                    ) : invoice.can.pay ? (
                        <Card title="Sudah transfer? Kirim bukti">
                            <p className="text-sm text-slate-500">
                                Transfer ke rekening sekolah, lalu unggah
                                foto/tangkapan layar bukti transfer. Boleh
                                dicicil.
                            </p>
                            {instructions && (
                                <p className="rounded-xl bg-brand-50 p-3 text-sm whitespace-pre-wrap text-brand-900">
                                    {instructions}
                                </p>
                            )}
                            <PaymentForm
                                url={`/orang-tua/keuangan/${invoice.public_id}/bukti`}
                                remaining={invoice.remaining}
                                today={today}
                                proofRequired
                                submitLabel="Kirim bukti transfer"
                            />
                        </Card>
                    ) : null}
                </aside>
            </div>
        </ParentLayout>
    );
}
