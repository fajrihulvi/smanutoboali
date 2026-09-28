import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import { toastFirstError } from '@/lib/toast-errors';
import type { PaymentRow } from '@/Modules/Finance/types';

const BASE = '/operator/keuangan';

/**
 * Terima / tolak (dengan alasan) bukti transfer yang menunggu verifikasi.
 */
export function ReviewButtons({ payment }: { payment: PaymentRow }) {
    const [rejecting, setRejecting] = useState(false);
    const [busy, setBusy] = useState(false);
    const form = useForm({ decision: 'reject', reason: '' });
    const url = `${BASE}/pembayaran/${payment.public_id}/verifikasi`;

    const approve = () => {
        setBusy(true);
        router.post(
            url,
            { decision: 'approve' },
            {
                preserveScroll: true,
                onError: toastFirstError,
                onFinish: () => setBusy(false),
            },
        );
    };

    const reject = (event: FormEvent) => {
        event.preventDefault();
        form.post(url, {
            preserveScroll: true,
            onSuccess: () => setRejecting(false),
            onError: toastFirstError,
        });
    };

    return (
        <>
            <Button size="sm" onClick={approve} disabled={busy}>
                {busy && <Spinner />}
                Terima
            </Button>
            <Button
                size="sm"
                variant="outline"
                onClick={() => setRejecting(true)}
            >
                Tolak
            </Button>
            <Dialog open={rejecting} onOpenChange={setRejecting}>
                <DialogContent className="sm:max-w-md">
                    <form onSubmit={reject} className="space-y-4">
                        <DialogHeader>
                            <DialogTitle>Tolak bukti transfer?</DialogTitle>
                            <DialogDescription>
                                Orang tua akan menerima alasan penolakan dan
                                bisa mengunggah ulang.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-2">
                            <Label htmlFor={`reject-${payment.public_id}`}>
                                Alasan
                            </Label>
                            <Textarea
                                id={`reject-${payment.public_id}`}
                                rows={3}
                                maxLength={255}
                                value={form.data.reason}
                                onChange={(e) =>
                                    form.setData('reason', e.target.value)
                                }
                                placeholder="Mis. nominal di bukti tidak sesuai"
                            />
                            <InputError message={form.errors.reason} />
                        </div>
                        <DialogFooter className="gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setRejecting(false)}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={form.processing}
                            >
                                {form.processing && <Spinner />}
                                Tolak
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
