import { FileText } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
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
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { HttpError, postForDownload } from '@/lib/http';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

/** Ekspor lewat pusat laporan (laporan `buku-tamu`): kecil langsung diunduh, besar lewat antrean. */
const EXPORT_URL = '/operator/laporan/ekspor';

function isoDate(date: Date): string {
    return date.toLocaleDateString('en-CA');
}

/**
 * Rekap buku tamu ke PDF berdasarkan rentang tanggal kunjungan.
 */
export function RecapDialog({ open, onOpenChange }: Props) {
    const today = new Date();
    const [from, setFrom] = useState(
        isoDate(new Date(today.getFullYear(), today.getMonth(), 1)),
    );
    const [to, setTo] = useState(isoDate(today));
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setErrors({});
        setProcessing(true);

        try {
            const result = await postForDownload(
                EXPORT_URL,
                { laporan: 'buku-tamu', format: 'pdf', from, to },
                'rekap-buku-tamu.pdf',
            );

            toast.success(
                result.queued
                    ? `${result.message} Unduh dari menu Laporan.`
                    : 'Rekap buku tamu diunduh.',
            );
            onOpenChange(false);
        } catch (error) {
            if (error instanceof HttpError) {
                setErrors(error.errors);
            }

            toast.error(
                error instanceof Error ? error.message : 'Rekap gagal dibuat.',
            );
        } finally {
            setProcessing(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Rekap buku tamu (PDF)</DialogTitle>
                        <DialogDescription>
                            Pilih rentang tanggal kunjungan yang ingin direkap.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="recap-from">Tanggal mulai</Label>
                            <Input
                                id="recap-from"
                                type="date"
                                value={from}
                                max={to}
                                onChange={(e) => setFrom(e.target.value)}
                                aria-invalid={!!errors.from}
                                required
                            />
                            <InputError message={errors.from} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="recap-to">Tanggal selesai</Label>
                            <Input
                                id="recap-to"
                                type="date"
                                value={to}
                                min={from}
                                onChange={(e) => setTo(e.target.value)}
                                aria-invalid={!!errors.to}
                                required
                            />
                            <InputError message={errors.to} />
                        </div>
                    </div>
                    <DialogFooter className="gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            disabled={!from || !to || processing}
                        >
                            {processing ? <Spinner /> : <FileText />}
                            Unduh PDF
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
