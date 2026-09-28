import { router } from '@inertiajs/react';
import { PenLine, Trash2, Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from '@/Components/ui/button';
import { Spinner } from '@/Components/ui/spinner';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import { toastFirstError } from '@/lib/toast-errors';

type Props = {
    /** URL gambar tanda tangan saat ini (null = belum ada). */
    url: string | null;
    /** Endpoint POST (unggah) sekaligus DELETE (hapus). */
    endpoint: string;
    hint?: string;
};

/**
 * Unggah/hapus gambar tanda tangan (PNG transparan disarankan) — dipakai PDF raport.
 */
export function SignatureField({ url, endpoint, hint }: Props) {
    const input = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const { confirmDelete, dialog } = useConfirmDelete();

    const upload = (file: File) =>
        router.post(
            endpoint,
            { signature: file },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => setUploading(true),
                onFinish: () => setUploading(false),
                onError: (errors) => toastFirstError(errors),
            },
        );

    return (
        <div className="flex flex-wrap items-center gap-4">
            <div className="flex h-20 w-48 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50">
                {url ? (
                    <img
                        src={url}
                        alt="Tanda tangan"
                        className="max-h-16 max-w-44 object-contain"
                    />
                ) : (
                    <span className="flex items-center gap-1.5 text-xs text-slate-400">
                        <PenLine className="size-4" /> Belum ada
                    </span>
                )}
            </div>
            <div className="space-y-2">
                <div className="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => input.current?.click()}
                        disabled={uploading}
                    >
                        {uploading ? <Spinner /> : <Upload />}
                        {url ? 'Ganti' : 'Unggah'}
                    </Button>
                    {url && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                            onClick={() =>
                                confirmDelete({
                                    url: endpoint,
                                    title: 'Hapus tanda tangan?',
                                    confirmLabel: 'Ya, hapus',
                                })
                            }
                        >
                            <Trash2 />
                            Hapus
                        </Button>
                    )}
                </div>
                <p className="text-xs text-slate-500">
                    {hint ??
                        'PNG/JPG/WebP, maks 1 MB. Latar transparan paling rapi.'}
                </p>
            </div>
            <input
                ref={input}
                type="file"
                accept="image/png,image/jpeg,image/webp"
                className="hidden"
                onChange={(event) => {
                    const file = event.target.files?.[0];

                    if (file) {
                        upload(file);
                    }

                    event.target.value = '';
                }}
            />
            {dialog}
        </div>
    );
}
