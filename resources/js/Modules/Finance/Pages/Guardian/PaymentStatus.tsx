import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle2, Clock, Download, XCircle } from 'lucide-react';
import { useEffect } from 'react';
import { Button } from '@/Components/ui/button';
import ParentLayout from '@/Layouts/ParentLayout';
import { formatCurrency } from '@/lib/format';
import type { InvoiceRow } from '@/Modules/Finance/types';

type Props = {
    invoice: InvoiceRow;
    transaction: {
        public_id: string;
        order_id: string;
        amount: number;
        status: {
            value: 'CREATED' | 'PENDING' | 'SETTLED' | 'FAILED';
            label: string;
        };
        final: boolean;
        payment_type: string | null;
        receipt_url: string | null;
    };
};

/** Cek ulang status dari server tiap 4 detik selama belum final (maks ~3 menit). */
const POLL_MS = 4000;
const MAX_POLLS = 45;

export default function PaymentStatus({ invoice, transaction }: Props) {
    useEffect(() => {
        if (transaction.final) {
            return;
        }

        let polls = 0;
        const timer = window.setInterval(() => {
            polls++;

            if (polls > MAX_POLLS) {
                window.clearInterval(timer);

                return;
            }

            router.reload({ only: ['transaction', 'invoice'] });
        }, POLL_MS);

        return () => window.clearInterval(timer);
    }, [transaction.final]);

    const settled = transaction.status.value === 'SETTLED';
    const failed = transaction.status.value === 'FAILED';
    const Icon = settled ? CheckCircle2 : failed ? XCircle : Clock;

    return (
        <ParentLayout>
            <Head title="Status pembayaran" />
            <section className="mx-auto max-w-md space-y-4 rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
                <Icon
                    className={
                        settled
                            ? 'mx-auto size-14 text-emerald-600'
                            : failed
                              ? 'mx-auto size-14 text-rose-600'
                              : 'mx-auto size-14 text-sky-600'
                    }
                />
                <div>
                    <h1 className="text-lg font-semibold text-slate-900">
                        {settled
                            ? 'Pembayaran berhasil'
                            : failed
                              ? 'Pembayaran gagal atau kedaluwarsa'
                              : 'Menunggu konfirmasi pembayaran'}
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        {invoice.title} · {invoice.student.name}
                    </p>
                </div>
                <p className="text-2xl font-semibold text-slate-900 tabular-nums">
                    {formatCurrency(transaction.amount)}
                </p>
                {!transaction.final && (
                    <p className="text-sm text-slate-500">
                        Selesaikan pembayaran sesuai petunjuk Midtrans (mis.
                        transfer ke virtual account). Status diperbarui otomatis
                        setelah bank mengonfirmasi.
                    </p>
                )}
                <p className="font-mono text-xs text-slate-400">
                    {transaction.order_id}
                    {transaction.payment_type &&
                        ` · ${transaction.payment_type}`}
                </p>
                <div className="flex flex-col gap-2">
                    {transaction.receipt_url && (
                        <Button asChild>
                            <a href={transaction.receipt_url}>
                                <Download />
                                Unduh kwitansi
                            </a>
                        </Button>
                    )}
                    <Button variant="outline" asChild>
                        <Link href={`/orang-tua/keuangan/${invoice.public_id}`}>
                            Kembali ke tagihan
                        </Link>
                    </Button>
                </div>
            </section>
        </ParentLayout>
    );
}
