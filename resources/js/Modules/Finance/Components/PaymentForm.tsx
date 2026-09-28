import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { FileUploader } from '@/Components/shared/FileUploader';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { formatCurrency } from '@/lib/format';
import { RupiahInput } from '@/Modules/Finance/Components/RupiahInput';

type Props = {
    url: string;
    remaining: number;
    today: string;
    /** Orang tua wajib melampirkan bukti transfer. */
    proofRequired: boolean;
    submitLabel: string;
    onSuccess?: () => void;
};

/**
 * Form pembayaran: operator mencatat pembayaran, orang tua mengunggah bukti transfer.
 * Default jumlah = sisa tagihan; boleh lebih kecil (cicilan).
 */
export function PaymentForm({
    url,
    remaining,
    today,
    proofRequired,
    submitLabel,
    onSuccess,
}: Props) {
    const form = useForm({
        amount: remaining as number | '',
        paid_on: today,
        note: '',
        proof: null as File | null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset('note', 'proof');
                onSuccess?.();
            },
        });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="payment-amount">Jumlah</Label>
                    <RupiahInput
                        id="payment-amount"
                        value={form.data.amount}
                        onChange={(value) => form.setData('amount', value)}
                        invalid={!!form.errors.amount}
                    />
                    <p className="text-xs text-slate-500">
                        Sisa tagihan {formatCurrency(remaining)} · boleh
                        dicicil.
                    </p>
                    <InputError message={form.errors.amount} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="payment-date">Tanggal bayar</Label>
                    <Input
                        id="payment-date"
                        type="date"
                        max={today}
                        value={form.data.paid_on}
                        onChange={(e) =>
                            form.setData('paid_on', e.target.value)
                        }
                        aria-invalid={!!form.errors.paid_on}
                    />
                    <InputError message={form.errors.paid_on} />
                </div>
            </div>
            <div className="grid gap-2">
                <Label htmlFor="payment-note">Catatan (opsional)</Label>
                <Input
                    id="payment-note"
                    maxLength={255}
                    value={form.data.note}
                    onChange={(e) => form.setData('note', e.target.value)}
                    placeholder={
                        proofRequired
                            ? 'Mis. transfer dari rekening a.n. Budi'
                            : 'Mis. tunai di TU'
                    }
                />
            </div>
            <div className="grid gap-2">
                <Label>
                    Bukti transfer {proofRequired ? '' : '(opsional)'}
                </Label>
                <FileUploader
                    value={form.data.proof ? [form.data.proof] : []}
                    onChange={(files) =>
                        form.setData('proof', files[0] ?? null)
                    }
                    accept={[
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                        'application/pdf',
                    ]}
                    maxFiles={1}
                    maxSizeKb={5120}
                    hint="Foto/tangkapan layar atau PDF · maks 5 MB"
                    error={form.errors.proof}
                />
            </div>
            <Button
                type="submit"
                className="w-full sm:w-auto"
                disabled={form.processing || form.data.amount === ''}
            >
                {form.processing && <Spinner />}
                {submitLabel}
            </Button>
        </form>
    );
}
