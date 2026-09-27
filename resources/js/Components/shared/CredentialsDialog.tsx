import { usePage } from '@inertiajs/react';
import { Check, Copy, KeyRound } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { useClipboard } from '@/hooks/use-clipboard';

/**
 * Menampilkan SEKALI kredensial akun baru dari flash `credentials`
 * (password sementara yang dibuat server).
 */
export function CredentialsDialog() {
    const { flash } = usePage().props;
    const credentials = flash?.credentials ?? null;
    const [dismissed, setDismissed] = useState<string | null>(null);
    const [copied, copy] = useClipboard();

    const key = credentials
        ? `${credentials.email}:${credentials.password}`
        : null;
    const open = key !== null && key !== dismissed;

    if (!credentials) {
        return null;
    }

    const text = `Email: ${credentials.email}\nPassword: ${credentials.password}`;

    return (
        <Dialog
            open={open}
            onOpenChange={(value) => !value && setDismissed(key)}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <span className="mb-2 flex size-11 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                        <KeyRound className="size-5" />
                    </span>
                    <DialogTitle>Akun login dibuat</DialogTitle>
                    <DialogDescription>
                        Berikan kredensial ini kepada pemilik akun. Password
                        sementara hanya ditampilkan sekali; minta pengguna
                        menggantinya setelah masuk.
                    </DialogDescription>
                </DialogHeader>

                <dl className="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                    <div>
                        <dt className="text-xs text-slate-500">Email</dt>
                        <dd className="font-medium break-all text-slate-900">
                            {credentials.email}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-xs text-slate-500">
                            Password sementara
                        </dt>
                        <dd className="font-mono text-base font-semibold tracking-wide text-slate-900">
                            {credentials.password}
                        </dd>
                    </div>
                </dl>

                <DialogFooter className="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => copy(text)}
                    >
                        {copied === text ? <Check /> : <Copy />}
                        {copied === text ? 'Disalin' : 'Salin'}
                    </Button>
                    <Button type="button" onClick={() => setDismissed(key)}>
                        Selesai
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
