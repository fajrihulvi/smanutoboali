import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { ConfirmDialog } from '@/Components/shared/ConfirmDialog';
import { toastFirstError } from '@/lib/toast-errors';

type DeleteRequest = {
    url: string;
    title: string;
    description?: ReactNode;
    confirmLabel?: string;
    onSuccess?: () => void;
};

/**
 * Konfirmasi + DELETE Inertia. Penolakan dari server (mis. data masih dipakai)
 * dikirim sebagai error validasi, lalu ditampilkan sebagai toast agar tidak gagal diam-diam.
 *
 * Pakai: `const { confirmDelete, dialog } = useConfirmDelete();` lalu render `{dialog}`.
 */
export function useConfirmDelete() {
    const [request, setRequest] = useState<DeleteRequest | null>(null);
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const confirmDelete = (next: DeleteRequest) => {
        setRequest(next);
        setOpen(true);
    };

    const run = () => {
        if (!request) {
            return;
        }

        router.delete(request.url, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onSuccess: () => request.onSuccess?.(),
            onError: (errors) =>
                toastFirstError(errors, 'Data tidak dapat dihapus.'),
            onFinish: () => {
                setProcessing(false);
                setOpen(false);
            },
        });
    };

    const dialog = (
        <ConfirmDialog
            open={open}
            onOpenChange={setOpen}
            variant="danger"
            title={request?.title ?? ''}
            description={request?.description}
            confirmLabel={request?.confirmLabel ?? 'Ya, hapus'}
            processing={processing}
            onConfirm={run}
        />
    );

    return { confirmDelete, dialog };
}
