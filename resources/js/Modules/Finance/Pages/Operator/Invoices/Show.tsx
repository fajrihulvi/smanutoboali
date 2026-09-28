import { Head, useForm } from '@inertiajs/react';
import { Ban, CalendarClock } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import InputError from '@/Components/input-error';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate, formatDateTime } from '@/lib/format';
import { InvoiceItems } from '@/Modules/Finance/Components/InvoiceItems';
import { InvoiceStatusBadge } from '@/Modules/Finance/Components/InvoiceStatusBadge';
import { PaymentForm } from '@/Modules/Finance/Components/PaymentForm';
import { PaymentList } from '@/Modules/Finance/Components/PaymentList';
import { ReviewButtons } from '@/Modules/Finance/Components/ReviewButtons';
import type { InvoiceDetail, PaymentRow } from '@/Modules/Finance/types';

type Props = { invoice: InvoiceDetail; today: string };

const BASE = '/operator/keuangan';

function Card({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 className="text-sm font-semibold text-slate-900">{title}</h2>
            {children}
        </section>
    );
}

export default function InvoiceShow({ invoice, today }: Props) {
    const [dialog, setDialog] = useState<'edit' | 'cancel' | null>(null);
    const payable = ['UNPAID', 'PENDING', 'FAILED'].includes(
        invoice.status.value,
    );
    const cancellable =
        payable && !invoice.payments.some((p) => p.status.value !== 'REJECTED');

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Keuangan', href: `${BASE}/tagihan` },
                { title: invoice.number, href: '#' },
            ]}
        >
            <Head title={`Tagihan ${invoice.number}`} />
            <PageHeader
                title={invoice.title}
                description={`${invoice.student.name} (${invoice.student.nis})${invoice.classroom ? ` · ${invoice.classroom}` : ''} · ${invoice.number}`}
                actions={
                    invoice.status.value !== 'CANCELLED' && (
                        <>
                            <Button
                                variant="outline"
                                onClick={() => setDialog('edit')}
                            >
                                <CalendarClock />
                                Ubah jatuh tempo
                            </Button>
                            {cancellable && (
                                <Button
                                    variant="outline"
                                    className="text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                    onClick={() => setDialog('cancel')}
                                >
                                    <Ban />
                                    Batalkan
                                </Button>
                            )}
                        </>
                    )
                }
            />

            <div className="grid gap-6 lg:grid-cols-5">
                <div className="min-w-0 space-y-6 lg:col-span-3">
                    <Card title="Rincian">
                        <div className="flex flex-wrap items-center gap-2 text-sm text-slate-600">
                            <InvoiceStatusBadge invoice={invoice} />
                            <span>
                                Jatuh tempo {formatDate(invoice.due_date)}
                            </span>
                            {invoice.paid_at && (
                                <span>
                                    · lunas {formatDateTime(invoice.paid_at)}
                                </span>
                            )}
                        </div>
                        <InvoiceItems invoice={invoice} />
                        {invoice.notes && (
                            <p className="rounded-lg bg-slate-50 px-3 py-2 text-sm whitespace-pre-wrap text-slate-600">
                                {invoice.notes}
                            </p>
                        )}
                    </Card>
                    <Card title="Riwayat pembayaran">
                        <PaymentList
                            payments={invoice.payments}
                            receiptUrl={(p: PaymentRow) =>
                                `${BASE}/pembayaran/${p.public_id}/kwitansi`
                            }
                            actions={(p) =>
                                p.status.value === 'PENDING' && (
                                    <ReviewButtons payment={p} />
                                )
                            }
                        />
                    </Card>
                </div>
                <aside className="min-w-0 lg:col-span-2">
                    {payable && invoice.remaining > 0 ? (
                        <Card title="Catat pembayaran">
                            <PaymentForm
                                url={`${BASE}/tagihan/${invoice.public_id}/pembayaran`}
                                remaining={invoice.remaining}
                                today={today}
                                proofRequired={false}
                                submitLabel="Simpan pembayaran"
                            />
                        </Card>
                    ) : (
                        <Card title="Pembayaran">
                            <p className="text-sm text-slate-500">
                                {invoice.status.value === 'CANCELLED'
                                    ? 'Tagihan dibatalkan.'
                                    : 'Tagihan sudah lunas.'}
                            </p>
                        </Card>
                    )}
                </aside>
            </div>

            <EditDialog
                invoice={invoice}
                open={dialog === 'edit'}
                onClose={() => setDialog(null)}
            />
            <CancelDialog
                invoice={invoice}
                open={dialog === 'cancel'}
                onClose={() => setDialog(null)}
            />
        </AppLayout>
    );
}

function EditDialog({
    invoice,
    open,
    onClose,
}: {
    invoice: InvoiceDetail;
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm({
        due_date: invoice.due_date,
        notes: invoice.notes ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`${BASE}/tagihan/${invoice.public_id}`, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Dialog open={open} onOpenChange={(value) => !value && onClose()}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Ubah tagihan</DialogTitle>
                        <DialogDescription>
                            Nominal tidak bisa diubah. Bila salah, batalkan lalu
                            buat ulang.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-2">
                        <Label htmlFor="invoice-due">Jatuh tempo</Label>
                        <Input
                            id="invoice-due"
                            type="date"
                            value={form.data.due_date}
                            onChange={(e) =>
                                form.setData('due_date', e.target.value)
                            }
                        />
                        <InputError message={form.errors.due_date} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="invoice-notes">Catatan</Label>
                        <Textarea
                            id="invoice-notes"
                            rows={3}
                            value={form.data.notes}
                            onChange={(e) =>
                                form.setData('notes', e.target.value)
                            }
                        />
                        <InputError message={form.errors.notes} />
                    </div>
                    <DialogFooter className="gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function CancelDialog({
    invoice,
    open,
    onClose,
}: {
    invoice: InvoiceDetail;
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm({ reason: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`${BASE}/tagihan/${invoice.public_id}/batal`, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Dialog open={open} onOpenChange={(value) => !value && onClose()}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>
                            Batalkan tagihan {invoice.number}?
                        </DialogTitle>
                        <DialogDescription>
                            Tagihan yang dibatalkan bisa dibuat ulang untuk
                            periode yang sama.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-2">
                        <Label htmlFor="cancel-reason">Alasan</Label>
                        <Textarea
                            id="cancel-reason"
                            rows={3}
                            maxLength={255}
                            value={form.data.reason}
                            onChange={(e) =>
                                form.setData('reason', e.target.value)
                            }
                        />
                        <InputError
                            message={
                                form.errors.reason ??
                                (form.errors as Record<string, string>).invoice
                            }
                        />
                    </div>
                    <DialogFooter className="gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Kembali
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            Batalkan tagihan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
