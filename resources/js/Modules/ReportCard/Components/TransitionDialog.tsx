import { useForm } from '@inertiajs/react';
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
import type { ReportStatus } from '@/Modules/ReportCard/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** Tujuan transisi; REVISION mewajibkan catatan. */
    target: ReportStatus | null;
    url: string;
    /** Untuk aksi massal: public_id raport terpilih. */
    cards?: string[];
    description?: string;
};

/**
 * Konfirmasi perpindahan tahap raport (tunggal/massal) dengan catatan opsional/wajib.
 */
export function TransitionDialog({ open, onOpenChange, ...props }: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                {props.target && (
                    <TransitionForm
                        key={props.target.value}
                        {...props}
                        target={props.target}
                        onClose={() => onOpenChange(false)}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}

function TransitionForm({
    target,
    url,
    cards,
    description,
    onClose,
}: Omit<Props, 'open' | 'onOpenChange' | 'target'> & {
    target: ReportStatus;
    onClose: () => void;
}) {
    const isRevision = target.value === 'REVISION';
    const form = useForm({
        target: target.value,
        note: '',
        ...(cards ? { cards } : {}),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <DialogHeader>
                <DialogTitle>
                    {isRevision
                        ? 'Kembalikan untuk revisi'
                        : `Ubah ke "${target.label}"`}
                </DialogTitle>
                <DialogDescription>
                    {description ??
                        (cards
                            ? `${cards.length} raport terpilih.`
                            : 'Tindakan ini tercatat di riwayat raport.')}
                </DialogDescription>
            </DialogHeader>

            <div className="grid gap-2">
                <Label htmlFor="transition-note">
                    {isRevision
                        ? 'Alasan revisi (wajib)'
                        : 'Catatan (opsional)'}
                </Label>
                <Textarea
                    id="transition-note"
                    rows={3}
                    maxLength={1000}
                    value={form.data.note}
                    onChange={(event) =>
                        form.setData('note', event.target.value)
                    }
                    aria-invalid={!!form.errors.note}
                    required={isRevision}
                />
                <InputError
                    message={
                        form.errors.note ??
                        form.errors.target ??
                        (form.errors as Record<string, string>).status
                    }
                />
            </div>

            <DialogFooter className="gap-2">
                <Button type="button" variant="outline" onClick={onClose}>
                    Batal
                </Button>
                <Button
                    type="submit"
                    variant={isRevision ? 'destructive' : 'default'}
                    disabled={form.processing}
                >
                    {form.processing && <Spinner />}
                    {isRevision ? 'Kembalikan' : 'Lanjutkan'}
                </Button>
            </DialogFooter>
        </form>
    );
}
