import { router } from '@inertiajs/react';
import { CreditCard } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/Components/ui/button';
import { Spinner } from '@/Components/ui/spinner';
import { formatCurrency } from '@/lib/format';
import { HttpError, sendJson } from '@/lib/http';
import { loadSnap } from '@/Modules/Finance/snap';

type Session = {
    token: string;
    redirect_url: string;
    status_url: string;
    client_key: string;
    script_url: string;
};

/**
 * Bayar sisa tagihan lewat Midtrans Snap. Apa pun hasil popup, orang tua diarahkan ke halaman
 * status yang membaca status dari server (hasil webhook), bukan dari callback Snap.
 */
export function OnlinePayButton({
    invoiceId,
    amount,
}: {
    invoiceId: string;
    amount: number;
}) {
    const [busy, setBusy] = useState(false);

    const pay = async () => {
        setBusy(true);

        try {
            const session = await sendJson<Session>(
                `/orang-tua/keuangan/${invoiceId}/bayar`,
                'POST',
            );
            const toStatus = () => router.visit(session.status_url);

            try {
                const snap = await loadSnap(
                    session.script_url,
                    session.client_key,
                );
                snap.pay(session.token, {
                    onSuccess: toStatus,
                    onPending: toStatus,
                    onError: toStatus,
                    onClose: () => setBusy(false),
                });
            } catch {
                // Popup gagal dimuat → halaman pembayaran Midtrans penuh.
                window.location.href = session.redirect_url;
            }
        } catch (error) {
            toast.error(
                error instanceof HttpError
                    ? error.message
                    : 'Pembayaran online gagal dimulai.',
            );
            setBusy(false);
        }
    };

    return (
        <Button
            type="button"
            className="w-full"
            size="lg"
            onClick={pay}
            disabled={busy}
        >
            {busy ? <Spinner /> : <CreditCard />}
            Bayar online {formatCurrency(amount)}
        </Button>
    );
}
